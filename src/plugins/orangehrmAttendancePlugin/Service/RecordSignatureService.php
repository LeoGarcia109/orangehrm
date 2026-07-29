<?php

/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */

namespace OrangeHRM\Attendance\Service;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use OrangeHRM\Entity\AttendanceRecord;

/**
 * Computes and verifies SHA-256 integrity hashes for attendance records.
 *
 * Per Portaria 673/2021, electronic time records must be tamper-evident.
 * Each record receives a hash computed over its immutable fields. Any
 * subsequent modification to those fields will cause verification to fail,
 * providing cryptographic proof of tampering.
 *
 * Hash input: NSR | employee_id | punch_in_utc_time | punch_out_utc_time | state | secret_key
 * The secret_key is stored in the application config (hs_hr_config).
 */
class RecordSignatureService
{
    private const HASH_ALGORITHM = 'sha256';
    private const CONFIG_KEY_SECRET = 'attendance.br.signature_secret';

    private EntityManagerInterface $em;
    private ?string $secretKey;

    public function __construct(EntityManagerInterface $em, ?string $secretKey = null)
    {
        $this->em = $em;
        $this->secretKey = $secretKey ?? $this->loadSecretFromConfig();
    }

    /**
     * Sign an attendance record: compute and store its SHA-256 hash.
     * Should be called after the record is fully populated (punch-out complete).
     *
     * @param AttendanceRecord $record
     * @return string The computed hash
     */
    public function signRecord(AttendanceRecord $record): string
    {
        $hash = $this->computeHash($record);

        // Use direct SQL to avoid triggering Doctrine change tracking issues
        $conn = $this->em->getConnection();
        $conn->executeStatement(
            'UPDATE ohrm_attendance_record SET record_hash = ?, hash_created_at = NOW() WHERE id = ?',
            [$hash, $record->getId()]
        );

        return $hash;
    }

    /**
     * Verify the integrity of a record's hash.
     *
     * @param AttendanceRecord $record
     * @return array{valid: bool, expected: string, actual: string|null}
     */
    public function verifyRecord(AttendanceRecord $record): array
    {
        $expected = $this->computeHash($record);
        $actual = $this->getStoredHash($record->getId());

        return [
            'valid' => ($actual !== null && hash_equals($expected, $actual)),
            'expected' => $expected,
            'actual' => $actual,
        ];
    }

    /**
     * Verify all records in a period. Returns a report of valid/invalid records.
     *
     * @param DateTime $from
     * @param DateTime $to
     * @param int|null $employeeNumber
     * @return array{total: int, valid: int, invalid: int, unsigned: int, invalidIds: int[]}
     */
    public function verifyPeriod(DateTime $from, DateTime $to, ?int $employeeNumber = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('ar')
            ->from(AttendanceRecord::class, 'ar')
            ->where('ar.punchInUtcTime >= :from')
            ->andWhere('ar.punchInUtcTime <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        if ($employeeNumber !== null) {
            $qb->andWhere('ar.employee = :empNumber')
                ->setParameter('empNumber', $employeeNumber);
        }

        $records = $qb->getQuery()->getResult();

        $total = count($records);
        $valid = 0;
        $invalid = 0;
        $unsigned = 0;
        $invalidIds = [];

        foreach ($records as $record) {
            $storedHash = $this->getStoredHash($record->getId());
            if ($storedHash === null) {
                $unsigned++;
                continue;
            }
            $result = $this->verifyRecord($record);
            if ($result['valid']) {
                $valid++;
            } else {
                $invalid++;
                $invalidIds[] = $record->getId();
            }
        }

        return [
            'total' => $total,
            'valid' => $valid,
            'invalid' => $invalid,
            'unsigned' => $unsigned,
            'invalidIds' => $invalidIds,
        ];
    }

    /**
     * Batch-sign all unsigned records in a period.
     *
     * @param DateTime $from
     * @param DateTime $to
     * @return int Number of records signed
     */
    public function signUnsignedRecords(DateTime $from, DateTime $to): int
    {
        $conn = $this->em->getConnection();
        $result = $conn->executeQuery(
            'SELECT id FROM ohrm_attendance_record
             WHERE punch_in_utc_time >= ? AND punch_in_utc_time <= ?
             AND record_hash IS NULL
             ORDER BY nsr ASC',
            [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')]
        );

        $count = 0;
        while ($id = $result->fetchOne()) {
            $record = $this->em->find(AttendanceRecord::class, (int)$id);
            if ($record !== null) {
                $this->signRecord($record);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Compute the SHA-256 hash for a record's immutable fields.
     */
    private function computeHash(AttendanceRecord $record): string
    {
        $payload = implode('|', [
            $record->getNsr() ?? 0,
            $record->getEmployee()->getEmpNumber(),
            $record->getPunchInUtcTime()?->format('Y-m-d H:i:s') ?? '',
            $record->getPunchOutUtcTime()?->format('Y-m-d H:i:s') ?? '',
            $record->getState(),
            $this->secretKey ?? '',
        ]);

        return hash(self::HASH_ALGORITHM, $payload);
    }

    /**
     * Get the stored hash for a record ID.
     */
    private function getStoredHash(int $recordId): ?string
    {
        $conn = $this->em->getConnection();
        $result = $conn->executeQuery(
            'SELECT record_hash FROM ohrm_attendance_record WHERE id = ?',
            [$recordId]
        );
        $hash = $result->fetchOne();
        return $hash !== false ? (string)$hash : null;
    }

    /**
     * Load the signature secret from hs_hr_config.
     * Falls back to a derived key if not configured.
     */
    private function loadSecretFromConfig(): ?string
    {
        try {
            $conn = $this->em->getConnection();
            $result = $conn->executeQuery(
                "SELECT `value` FROM hs_hr_config WHERE `name` = ?",
                [self::CONFIG_KEY_SECRET]
            );
            $value = $result->fetchOne();
            if ($value !== false && !empty($value)) {
                return (string)$value;
            }
        } catch (\Throwable $e) {
            // Table might not exist yet or config not set
        }

        // Fallback: derive from a fixed salt (less secure, but functional)
        // In production, set attendance.br.signature_secret in hs_hr_config
        return 'ohrm-br-attendance-' . php_uname('n');
    }
}
