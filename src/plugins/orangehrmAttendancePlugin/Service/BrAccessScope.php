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

/**
 * BR: who sees whose records in the HR views.
 *
 * The inbox endpoints serve both an employee's own records and HR's queue of
 * everyone, and OrangeHRM authorises per endpoint and verb, not per query
 * flag. So the queue is cut down here to the employees the caller may see --
 * the role manager's accessible ids: everyone for an admin, the team for a
 * supervisor, nobody for a plain employee.
 */
class BrAccessScope
{
    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<int|string> $accessibleEmpNumbers from getAccessibleEntityIds()
     * @return array<int, array<string, mixed>> a plain list, so it serialises as
     *     a JSON array rather than an object keyed by the surviving indexes
     */
    public static function restrictToEmployees(
        array $items,
        array $accessibleEmpNumbers,
        string $key = 'employeeId'
    ): array {
        return array_values(array_filter(
            $items,
            static fn (array $item) => self::canSee($item[$key], $accessibleEmpNumbers)
        ));
    }

    /**
     * @param int $empNumber the record's employee
     * @param array<int|string> $accessibleEmpNumbers ids come back as strings
     */
    public static function canSee(int $empNumber, array $accessibleEmpNumbers): bool
    {
        return in_array($empNumber, array_map('intval', $accessibleEmpNumbers), true);
    }
}
