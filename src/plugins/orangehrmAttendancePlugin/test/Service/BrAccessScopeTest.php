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

use OrangeHRM\Attendance\Service\BrAccessScope;
use OrangeHRM\Tests\Util\TestCase;

/**
 * Who sees whose records in the HR views.
 *
 * The same endpoints serve an employee's own inbox and HR's queue of everyone,
 * and OrangeHRM authorises per endpoint and verb, not per query flag. So the
 * queue has to be cut down in code to the employees the caller may see:
 * everyone for an admin, their team for a supervisor, nobody for a plain
 * employee. Otherwise anyone could list everyone's medical certificates.
 *
 * @group Attendance
 * @group Access
 */
class BrAccessScopeTest extends TestCase
{
    private function items(): array
    {
        return [
            ['id' => 10, 'employeeId' => 1],
            ['id' => 11, 'employeeId' => 2],
            ['id' => 12, 'employeeId' => 3],
        ];
    }

    public function testASupervisorSeesOnlyTheirTeam(): void
    {
        $visible = BrAccessScope::restrictToEmployees($this->items(), [1, 3]);

        $this->assertSame([10, 12], array_column($visible, 'id'));
    }

    public function testAPlainEmployeeSeesNobodyInTheQueue(): void
    {
        $this->assertSame([], BrAccessScope::restrictToEmployees($this->items(), []));
    }

    public function testAnAdminSeesEveryone(): void
    {
        $this->assertCount(3, BrAccessScope::restrictToEmployees($this->items(), [1, 2, 3]));
    }

    /**
     * array_filter keeps the original keys, and a list with holes in it is
     * serialised as a JSON object instead of an array -- the same family of bug
     * that blanked the work time report in August.
     */
    public function testTheResultIsAPlainListSoItSerialisesAsAnArray(): void
    {
        $visible = BrAccessScope::restrictToEmployees($this->items(), [2, 3]);

        $this->assertSame([0, 1], array_keys($visible));
    }

    /**
     * The role manager hands ids back as strings; the records carry ints.
     */
    public function testIdsAsStringsStillMatch(): void
    {
        $visible = BrAccessScope::restrictToEmployees($this->items(), ['2']);

        $this->assertSame([11], array_column($visible, 'id'));
    }

    public function testASingleRecordIsVisibleOnlyWhenItsEmployeeIs(): void
    {
        $this->assertTrue(BrAccessScope::canSee(2, [1, 2]));
        $this->assertFalse(BrAccessScope::canSee(3, [1, 2]));
        $this->assertTrue(BrAccessScope::canSee(2, ['2']));
    }
}
