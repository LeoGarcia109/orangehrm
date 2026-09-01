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

/**
 * BR: the employee signing their month.
 *
 * The signature is meant to stand up in a labour dispute, so it has to say
 * more than "somebody pressed a button": it binds to exactly the records that
 * were on screen. It is computed over the individual record hashes, in NSR
 * order, so a punch changed, added, removed or reordered afterwards breaks it.
 * A signature that survived the sheet being edited would prove nothing about
 * what was agreed to.
 */
class TimesheetSignatureRules
{
    private const HASH_ALGORITHM = 'sha256';

    /**
     * The signature over a month's records.
     *
     * @param int $empNumber whose sheet it is
     * @param string $referenceMonth YYYY-MM
     * @param string[] $recordHashes the month's record hashes, in NSR order
     * @param string $secret the same key that signs individual records
     * @throws AttendanceServiceException when the secret is missing
     */
    public static function computeHash(
        int $empNumber,
        string $referenceMonth,
        array $recordHashes,
        string $secret
    ): string {
        if ($secret === '') {
            throw AttendanceServiceException::signatureSecretNotConfigured();
        }

        // The employee and the month are bound in as well, so one month's
        // signature cannot be presented as another's.
        $payload = implode('|', [
            $empNumber,
            $referenceMonth,
            count($recordHashes),
            implode(':', $recordHashes),
        ]);

        return hash_hmac(self::HASH_ALGORITHM, $payload, $secret);
    }

    /**
     * A record still open has no hash of its own, so it cannot be bound into
     * the sheet -- and it is about to change legitimately anyway.
     *
     * @param array<string|null> $recordHashes
     * @throws AttendanceServiceException when some punch is still open
     */
    public static function assertSignable(array $recordHashes): void
    {
        foreach ($recordHashes as $hash) {
            if ($hash === null || $hash === '') {
                throw AttendanceServiceException::timesheetHasOpenRecord();
            }
        }
    }

    /**
     * Whether every record in the month still hashes to the value stored
     * against it.
     *
     * The month signature is computed over the stored `record_hash` column,
     * and an UPDATE straight in the database does not touch that column -- the
     * record simply stops hashing to it. So comparing stored hashes among
     * themselves cannot see the likeliest tampering; each record has to be
     * re-hashed from its current fields and compared.
     *
     * @param array<string|null> $stored the hashes the records carry
     * @param array<string|null> $recomputed the hashes their fields produce now
     */
    public static function everyRecordStillMatches(array $stored, array $recomputed): bool
    {
        if (count($stored) !== count($recomputed)) {
            return false;
        }

        foreach ($stored as $index => $hash) {
            if ($hash === null || $recomputed[$index] === null) {
                return false;
            }
            if (!hash_equals((string)$hash, (string)$recomputed[$index])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws AttendanceServiceException when the month was already signed
     */
    public static function assertNotSigned(?DateTime $signedAt): void
    {
        if ($signedAt !== null) {
            throw AttendanceServiceException::timesheetAlreadySigned(
                $signedAt->format('d/m/Y H:i')
            );
        }
    }

    /**
     * Signing a month still running would bind a sheet that the next punch is
     * about to change, so normal work would break the signature.
     *
     * @param string $referenceMonth YYYY-MM
     * @throws AttendanceServiceException when the month has not ended
     */
    public static function assertPeriodClosed(string $referenceMonth, DateTime $now): void
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $referenceMonth) !== 1) {
            throw AttendanceServiceException::timesheetMonthInvalid($referenceMonth);
        }

        $monthEnd = DateTime::createFromFormat('Y-m-d H:i:s', $referenceMonth . '-01 00:00:00');
        $monthEnd->modify('first day of next month')->setTime(0, 0, 0);

        if ($now < $monthEnd) {
            throw AttendanceServiceException::timesheetMonthNotClosed($referenceMonth);
        }
    }
}
