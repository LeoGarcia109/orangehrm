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
use OrangeHRM\Attendance\Service\TimesheetSignatureRules;
use OrangeHRM\Tests\Util\TestCase;

/**
 * The monthly timesheet signature.
 *
 * The signature is meant to stand up in a labour dispute, so it has to say
 * more than "the employee pressed a button": it has to bind to exactly the
 * records that were on screen. It is computed over the individual record
 * hashes, so any punch changed, added or removed afterwards breaks it -- a
 * signature that survived the sheet being edited would prove nothing.
 *
 * @group Attendance
 * @group Timesheet
 */
class TimesheetSignatureRulesTest extends TestCase
{
    private const SECRET = 'b7c0d1e2f3a4b5c6d7e8f90112233445566778899aabbccddeeff0011223344';
    private const HASHES = ['aaa111', 'bbb222', 'ccc333'];

    private function hash(int $empNumber = 1, string $month = '2026-08', ?array $hashes = null): string
    {
        return TimesheetSignatureRules::computeHash(
            $empNumber,
            $month,
            $hashes ?? self::HASHES,
            self::SECRET
        );
    }

    public function testTheSameSheetAlwaysProducesTheSameSignature(): void
    {
        $this->assertSame($this->hash(), $this->hash());
    }

    public function testSigningWithoutASecretIsRefused(): void
    {
        $this->expectException(AttendanceServiceException::class);
        TimesheetSignatureRules::computeHash(1, '2026-08', self::HASHES, '');
    }

    public function testADifferentSecretProducesADifferentSignature(): void
    {
        $this->assertNotSame(
            $this->hash(),
            TimesheetSignatureRules::computeHash(1, '2026-08', self::HASHES, 'outro-segredo')
        );
    }

    /**
     * The point of the whole thing: editing a punch after the employee signed
     * has to be visible.
     */
    public function testChangingOnePunchBreaksTheSignature(): void
    {
        $this->assertNotSame(
            $this->hash(),
            $this->hash(1, '2026-08', ['aaa111', 'bbb999', 'ccc333'])
        );
    }

    public function testRemovingAPunchBreaksTheSignature(): void
    {
        $this->assertNotSame(
            $this->hash(),
            $this->hash(1, '2026-08', ['aaa111', 'ccc333'])
        );
    }

    public function testAddingAPunchBreaksTheSignature(): void
    {
        $this->assertNotSame(
            $this->hash(),
            $this->hash(1, '2026-08', ['aaa111', 'bbb222', 'ccc333', 'ddd444'])
        );
    }

    /**
     * Reordering is not a reshuffle of equals: the NSR sequence is part of
     * what the sheet says happened.
     */
    public function testReorderingThePunchesBreaksTheSignature(): void
    {
        $this->assertNotSame(
            $this->hash(),
            $this->hash(1, '2026-08', ['bbb222', 'aaa111', 'ccc333'])
        );
    }

    public function testAnotherEmployeesSheetHasADifferentSignature(): void
    {
        $this->assertNotSame($this->hash(), $this->hash(2));
    }

    public function testAnotherMonthHasADifferentSignature(): void
    {
        $this->assertNotSame($this->hash(), $this->hash(1, '2026-07'));
    }

    /**
     * An employee with no punches in the month is still signing something --
     * the statement that there were none.
     */
    public function testAnEmptySheetCanStillBeSigned(): void
    {
        $this->expectNotToPerformAssertions();
        TimesheetSignatureRules::assertSignable([]);
    }

    public function testASheetWhereEveryPunchIsSignedCanBeSigned(): void
    {
        $this->expectNotToPerformAssertions();
        TimesheetSignatureRules::assertSignable(self::HASHES);
    }

    /**
     * A record still open has no hash of its own yet, so it cannot be bound
     * into the sheet -- and it is about to change legitimately anyway.
     */
    public function testASheetWithAPunchStillOpenCannotBeSigned(): void
    {
        $this->expectException(AttendanceServiceException::class);
        TimesheetSignatureRules::assertSignable(['aaa111', null, 'ccc333']);
    }

    /**
     * The signature is computed over the stored record hashes, and an edit made
     * straight in the database does not touch that column -- the record's own
     * hash simply stops matching its fields. So comparing the stored hashes to
     * each other is not enough: the sheet is only intact while every record
     * still hashes to what is stored against it.
     *
     * Without this check the likeliest tampering -- an UPDATE on a punch --
     * would leave the month reading as signed and unchanged.
     */
    public function testASheetWhoseRecordsStillHashToTheStoredValuesIsIntact(): void
    {
        $this->assertTrue(
            TimesheetSignatureRules::everyRecordStillMatches(
                ['aaa111', 'bbb222'],
                ['aaa111', 'bbb222']
            )
        );
    }

    public function testAPunchEditedInTheDatabaseBreaksTheSheet(): void
    {
        $this->assertFalse(
            TimesheetSignatureRules::everyRecordStillMatches(
                ['aaa111', 'bbb222'],
                ['aaa111', 'bbb999']
            )
        );
    }

    public function testAPunchAddedToTheMonthBreaksTheSheet(): void
    {
        $this->assertFalse(
            TimesheetSignatureRules::everyRecordStillMatches(
                ['aaa111'],
                ['aaa111', 'bbb222']
            )
        );
    }

    public function testAPunchDeletedFromTheMonthBreaksTheSheet(): void
    {
        $this->assertFalse(
            TimesheetSignatureRules::everyRecordStillMatches(
                ['aaa111', 'bbb222'],
                ['aaa111']
            )
        );
    }

    /**
     * A record whose hash was cleared cannot vouch for itself either.
     */
    public function testAPunchThatLostItsHashBreaksTheSheet(): void
    {
        $this->assertFalse(
            TimesheetSignatureRules::everyRecordStillMatches(
                ['aaa111', null],
                ['aaa111', 'bbb222']
            )
        );
    }

    public function testSigningTwiceIsRefused(): void
    {
        $this->expectException(AttendanceServiceException::class);
        TimesheetSignatureRules::assertNotSigned(new DateTime('2026-09-01 10:00:00'));
    }

    public function testAnUnsignedSheetPassesThatCheck(): void
    {
        $this->expectNotToPerformAssertions();
        TimesheetSignatureRules::assertNotSigned(null);
    }

    public function testAMonthThatHasEndedCanBeSigned(): void
    {
        $this->expectNotToPerformAssertions();
        TimesheetSignatureRules::assertPeriodClosed('2026-08', new DateTime('2026-09-01 00:00:01'));
    }

    /**
     * Signing a month still running would bind a sheet that the next punch is
     * about to change, so the signature would be broken by normal work.
     */
    public function testAMonthStillRunningCannotBeSigned(): void
    {
        $this->expectException(AttendanceServiceException::class);
        TimesheetSignatureRules::assertPeriodClosed('2026-09', new DateTime('2026-09-15 12:00:00'));
    }

    public function testAMonthThatHasNotStartedCannotBeSigned(): void
    {
        $this->expectException(AttendanceServiceException::class);
        TimesheetSignatureRules::assertPeriodClosed('2026-12', new DateTime('2026-09-15 12:00:00'));
    }

    public function testAMalformedMonthIsRefused(): void
    {
        $this->expectException(AttendanceServiceException::class);
        TimesheetSignatureRules::assertPeriodClosed('agosto', new DateTime('2026-09-15 12:00:00'));
    }
}
