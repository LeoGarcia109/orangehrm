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

namespace OrangeHRM\Tests\Attendance\Service;

use DateTime;
use OrangeHRM\Attendance\Exception\AttendanceServiceException;
use OrangeHRM\Attendance\Service\RecordSignatureService;
use OrangeHRM\Entity\AttendanceRecord;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Tests\Util\TestCase;

/**
 * The record signature, isolated from the database.
 *
 * Portaria 671/2021 requires a time record to be tamper-evident: whoever
 * changes a punch after the fact must not be able to leave the record looking
 * untouched. That only holds while the hash depends on a secret -- a digest of
 * public fields alone can be recomputed by anyone who edited them.
 *
 * @group Attendance
 * @group Signature
 */
class RecordSignatureTest extends TestCase
{
    private const SECRET = 'b7c0d1e2f3a4b5c6d7e8f90112233445566778899aabbccddeeff0011223344';

    private function completedRecord(): AttendanceRecord
    {
        $employee = new Employee();
        $employee->setEmpNumber(1);

        $record = new AttendanceRecord();
        $record->setEmployee($employee);
        $record->setNsr(42);
        $record->setPunchInUtcTime(new DateTime('2026-08-28 11:00:00'));
        $record->setPunchOutUtcTime(new DateTime('2026-08-28 20:00:00'));
        $record->setState(AttendanceRecord::STATE_PUNCHED_OUT);

        return $record;
    }

    public function testSigningWithoutASecretIsRefused(): void
    {
        $this->expectException(AttendanceServiceException::class);
        RecordSignatureService::computeHash($this->completedRecord(), '');
    }

    public function testTheSameRecordAndSecretAlwaysProduceTheSameHash(): void
    {
        $hash = RecordSignatureService::computeHash($this->completedRecord(), self::SECRET);

        $this->assertSame($hash, RecordSignatureService::computeHash($this->completedRecord(), self::SECRET));
    }

    public function testADifferentSecretProducesADifferentHash(): void
    {
        $record = $this->completedRecord();

        $this->assertNotSame(
            RecordSignatureService::computeHash($record, self::SECRET),
            RecordSignatureService::computeHash($record, 'outro-segredo')
        );
    }

    /**
     * The whole point: someone who edits the punch-out in the database and does
     * not hold the secret cannot produce the matching hash.
     */
    public function testMovingThePunchOutTimeChangesTheHash(): void
    {
        $record = $this->completedRecord();
        $before = RecordSignatureService::computeHash($record, self::SECRET);

        $record->setPunchOutUtcTime(new DateTime('2026-08-28 22:00:00'));

        $this->assertNotSame($before, RecordSignatureService::computeHash($record, self::SECRET));
    }

    public function testMovingThePunchInTimeChangesTheHash(): void
    {
        $record = $this->completedRecord();
        $before = RecordSignatureService::computeHash($record, self::SECRET);

        $record->setPunchInUtcTime(new DateTime('2026-08-28 12:30:00'));

        $this->assertNotSame($before, RecordSignatureService::computeHash($record, self::SECRET));
    }

    public function testReassigningTheRecordToAnotherEmployeeChangesTheHash(): void
    {
        $record = $this->completedRecord();
        $before = RecordSignatureService::computeHash($record, self::SECRET);

        $other = new Employee();
        $other->setEmpNumber(2);
        $record->setEmployee($other);

        $this->assertNotSame($before, RecordSignatureService::computeHash($record, self::SECRET));
    }

    public function testRenumberingTheNsrChangesTheHash(): void
    {
        $record = $this->completedRecord();
        $before = RecordSignatureService::computeHash($record, self::SECRET);

        $record->setNsr(43);

        $this->assertNotSame($before, RecordSignatureService::computeHash($record, self::SECRET));
    }

    public function testChangingTheStateChangesTheHash(): void
    {
        $record = $this->completedRecord();
        $before = RecordSignatureService::computeHash($record, self::SECRET);

        $record->setState(AttendanceRecord::STATE_CREATED);

        $this->assertNotSame($before, RecordSignatureService::computeHash($record, self::SECRET));
    }

    /**
     * Fields are joined into one payload, so a separator that can be swallowed
     * would let two different records share a hash.
     */
    public function testTwoRecordsDifferingOnlyInFieldBoundariesDoNotShareAHash(): void
    {
        $employee = new Employee();
        $employee->setEmpNumber(1);

        $first = new AttendanceRecord();
        $first->setEmployee($employee);
        $first->setNsr(1);
        $first->setPunchInUtcTime(new DateTime('2026-08-28 11:00:00'));
        $first->setPunchOutUtcTime(new DateTime('2026-08-28 20:00:00'));
        $first->setState(AttendanceRecord::STATE_PUNCHED_OUT);

        $second = new AttendanceRecord();
        $second->setEmployee($employee);
        $second->setNsr(11);
        $second->setPunchInUtcTime(new DateTime('2026-08-28 11:00:00'));
        $second->setPunchOutUtcTime(new DateTime('2026-08-28 20:00:00'));
        $second->setState(AttendanceRecord::STATE_PUNCHED_OUT);

        $this->assertNotSame(
            RecordSignatureService::computeHash($first, self::SECRET),
            RecordSignatureService::computeHash($second, self::SECRET)
        );
    }

    public function testACompletedRecordIsSignable(): void
    {
        $this->assertTrue(RecordSignatureService::isSignable($this->completedRecord()));
    }

    /**
     * A row is only final at punch-out: OrangeHRM keeps both punches on the same
     * row, so signing while the employee is still clocked in would sign a state
     * that is about to change legitimately.
     */
    public function testARecordStillPunchedInIsNotSignable(): void
    {
        $record = $this->completedRecord();
        $record->setState(AttendanceRecord::STATE_PUNCHED_IN);
        $record->setPunchOutUtcTime(null);

        $this->assertFalse(RecordSignatureService::isSignable($record));
    }

    public function testARecordMarkedPunchedOutWithoutAPunchOutTimeIsNotSignable(): void
    {
        $record = $this->completedRecord();
        $record->setPunchOutUtcTime(null);

        $this->assertFalse(RecordSignatureService::isSignable($record));
    }
}
