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
use OrangeHRM\Attendance\Exception\AttendanceServiceException;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\AttendanceRecord;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\TimesheetSignature;

/**
 * BR: the employee signing their own month.
 *
 * Reuses the record signature secret, so one key covers both: a punch edited
 * after the fact breaks its own hash, and breaking that hash breaks the
 * month's signature too.
 */
class TimesheetSignatureService
{
    use EntityManagerHelperTrait;

    private const CONFIG_KEY_SECRET = 'attendance.br.signature_secret';

    /**
     * The month's records as the sheet shows them, in NSR order.
     *
     * Read through SQL rather than the ORM because the record hash is written
     * with SQL as well and is not mapped on the entity.
     *
     * @return array<int, array{id: int, nsr: int|null, punchIn: string|null,
     *     punchOut: string|null, hash: string|null, seconds: int}>
     */
    public function getMonthRecords(Employee $employee, string $referenceMonth): array
    {
        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            "SELECT id, nsr, punch_in_user_time, punch_out_user_time, record_hash,
                    TIMESTAMPDIFF(SECOND, punch_in_utc_time, punch_out_utc_time) AS seconds
               FROM ohrm_attendance_record
              WHERE employee_id = ?
                AND DATE_FORMAT(punch_in_user_time, '%Y-%m') = ?
              ORDER BY nsr ASC",
            [$employee->getEmpNumber(), $referenceMonth]
        )->fetchAllAssociative();

        return array_map(static fn (array $row) => [
            'id' => (int)$row['id'],
            'nsr' => $row['nsr'] === null ? null : (int)$row['nsr'],
            'punchIn' => $row['punch_in_user_time'],
            'punchOut' => $row['punch_out_user_time'],
            'hash' => $row['record_hash'],
            'seconds' => (int)($row['seconds'] ?? 0),
        ], $rows);
    }

    public function getSignature(Employee $employee, string $referenceMonth): ?TimesheetSignature
    {
        return $this->getEntityManager()
            ->getRepository(TimesheetSignature::class)
            ->findOneBy(['employee' => $employee, 'referenceMonth' => $referenceMonth]);
    }

    /**
     * Sign the month. The caller has already confirmed the password.
     *
     * @throws AttendanceServiceException
     */
    public function sign(
        Employee $employee,
        string $referenceMonth,
        ?string $ipAddress,
        ?string $userAgent
    ): TimesheetSignature {
        TimesheetSignatureRules::assertPeriodClosed($referenceMonth, new DateTime());
        TimesheetSignatureRules::assertNotSigned(
            $this->getSignature($employee, $referenceMonth)?->getSignedAt()
        );

        $records = $this->getMonthRecords($employee, $referenceMonth);
        $hashes = array_column($records, 'hash');
        TimesheetSignatureRules::assertSignable($hashes);

        $signature = new TimesheetSignature();
        $signature->setEmployee($employee);
        $signature->setReferenceMonth($referenceMonth);
        $signature->setSignatureHash(TimesheetSignatureRules::computeHash(
            $employee->getEmpNumber(),
            $referenceMonth,
            $hashes,
            $this->secret()
        ));
        $signature->setRecordCount(count($records));
        $signature->setTotalSeconds((int)array_sum(array_column($records, 'seconds')));
        $signature->setIpAddress($ipAddress);
        $signature->setUserAgent($userAgent === null ? null : substr($userAgent, 0, 255));

        $this->getEntityManager()->persist($signature);
        $this->getEntityManager()->flush();

        return $signature;
    }

    /**
     * Whether the sheet still matches what was signed.
     *
     * This is the question the whole feature exists to answer: a signature
     * that no longer matches means the records moved after the employee
     * agreed to them.
     */
    public function isIntact(TimesheetSignature $signature): bool
    {
        $employee = $signature->getEmployee();
        $month = $signature->getReferenceMonth();
        $secret = $this->secret();
        $records = $this->getMonthRecords($employee, $month);
        $stored = array_column($records, 'hash');

        // Two different tamperings, two different checks.
        //
        // An UPDATE straight in the database does not touch `record_hash`, so
        // the aggregate below would still match -- the record simply stops
        // hashing to what is stored against it. That is the likeliest
        // tampering, so it is checked first.
        $recomputed = [];
        foreach ($records as $row) {
            $record = $this->getEntityManager()->find(AttendanceRecord::class, $row['id']);
            $recomputed[] = $record === null
                ? null
                : RecordSignatureService::computeHash($record, $secret);
        }
        if (!TimesheetSignatureRules::everyRecordStillMatches($stored, $recomputed)) {
            return false;
        }

        // And whoever re-signed the record to cover their tracks changes the
        // stored hash instead, which the aggregate catches.
        $expected = TimesheetSignatureRules::computeHash(
            $employee->getEmpNumber(),
            $month,
            array_map(static fn ($hash) => (string)$hash, $stored),
            $secret
        );

        return hash_equals($expected, $signature->getSignatureHash());
    }

    /**
     * Signatures for a month, for the HR side.
     *
     * @return TimesheetSignature[]
     */
    public function getSignaturesForMonth(string $referenceMonth): array
    {
        return $this->getEntityManager()
            ->getRepository(TimesheetSignature::class)
            ->findBy(['referenceMonth' => $referenceMonth], ['signedAt' => 'DESC']);
    }

    /**
     * @throws AttendanceServiceException when the secret is missing
     */
    private function secret(): string
    {
        $value = $this->getEntityManager()->getConnection()->executeQuery(
            "SELECT `value` FROM hs_hr_config WHERE `name` = ?",
            [self::CONFIG_KEY_SECRET]
        )->fetchOne();

        if ($value === false || (string)$value === '') {
            throw AttendanceServiceException::signatureSecretNotConfigured();
        }

        return (string)$value;
    }
}
