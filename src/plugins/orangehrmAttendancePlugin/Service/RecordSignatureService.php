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
use OrangeHRM\Attendance\Exception\AttendanceServiceException;
use OrangeHRM\Entity\AttendanceRecord;

/**
 * Computes and verifies SHA-256 integrity hashes for attendance records.
 *
 * Per Portaria 673/2021, electronic time records must be tamper-evident.
 * Each record receives a hash computed over its immutable fields. Any
 * subsequent modification to those fields will cause verification to fail,
 * providing cryptographic proof of tampering.
 *
 * Signed payload: NSR | employee_id | punch_in_utc_time | punch_out_utc_time | state,
 * keyed with the secret from hs_hr_config (`attendance.br.signature_secret`).
 * The key is what makes the hash evidence: without it the digest covers only
 * public columns, so whoever edited them could recompute a matching hash.
 * Signing therefore refuses to run when the secret is absent, rather than
 * quietly producing a forgeable value.
 */
class RecordSignatureService
{
    private const HASH_ALGORITHM = 'sha256';
    private const CONFIG_KEY_SECRET = 'attendance.br.signature_secret';

    private EntityManagerInterface $em;
    private ?string $secretKey = null;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
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
        $hash = self::computeHash($record, $this->secret());

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
        $expected = self::computeHash($record, $this->secret());
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
            if ($record !== null && self::isSignable($record)) {
                $this->signRecord($record);
                $count++;
            }
        }

        return $count;
    }

    /**
     * The keyed hash over the fields a fraud would have to change.
     *
     * Static and secret-in-hand so the rule can be exercised without a
     * database, and so no caller can accidentally sign with an empty key.
     *
     * @throws AttendanceServiceException when the secret is missing
     */
    public static function computeHash(AttendanceRecord $record, string $secret): string
    {
        if ($secret === '') {
            throw AttendanceServiceException::signatureSecretNotConfigured();
        }

        $payload = implode('|', [
            $record->getNsr() ?? 0,
            $record->getEmployee()->getEmpNumber(),
            $record->getPunchInUtcTime()?->format('Y-m-d H:i:s') ?? '',
            $record->getPunchOutUtcTime()?->format('Y-m-d H:i:s') ?? '',
            $record->getState(),
        ]);

        return hash_hmac(self::HASH_ALGORITHM, $payload, $secret);
    }

    /**
     * Whether the record is final enough to sign.
     *
     * OrangeHRM keeps both punches on one row, so the row only stops changing
     * legitimately at punch-out. Signing earlier would flag the punch-out
     * itself as tampering.
     */
    public static function isSignable(AttendanceRecord $record): bool
    {
        return $record->getState() === AttendanceRecord::STATE_PUNCHED_OUT
            && $record->getPunchOutUtcTime() !== null;
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
     * The signature secret from hs_hr_config, read once per request.
     *
     * There is deliberately no fallback key: a derived one would let signing
     * succeed while producing hashes anybody could reproduce, and would change
     * silently on the next deploy, turning every past record into a false
     * "VIOLATED".
     *
     * @throws AttendanceServiceException when the secret is missing
     */
    private function secret(): string
    {
        if ($this->secretKey !== null) {
            return $this->secretKey;
        }

        $value = $this->em->getConnection()->executeQuery(
            "SELECT `value` FROM hs_hr_config WHERE `name` = ?",
            [self::CONFIG_KEY_SECRET]
        )->fetchOne();

        if ($value === false || (string)$value === '') {
            throw AttendanceServiceException::signatureSecretNotConfigured();
        }

        return $this->secretKey = (string)$value;
    }
}
