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
 * BR: who a notice reaches.
 *
 * Reach follows the same chain the geofence and the employer CNPJ walk: a
 * notice aimed at a company finds the people in the departments under it, and
 * stops at the company next door. Aiming at the root therefore reaches
 * everybody who has a unit at all.
 *
 * Every uncertainty resolves to "does not reach": a notice whose target was
 * deleted has to fall silent rather than become a broadcast to the network.
 */
class AnnouncementAudience
{
    public const SCOPE_NETWORK = 'NETWORK';
    public const SCOPE_SUBUNIT = 'SUBUNIT';
    public const SCOPE_EMPLOYEE = 'EMPLOYEE';

    /**
     * @param string $scope NETWORK, SUBUNIT or EMPLOYEE
     * @param int|null $subunitId the unit aimed at, when scope is SUBUNIT
     * @param int|null $employeeId the person aimed at, when scope is EMPLOYEE
     * @param int[] $chainIds the reader's unit and its ancestors, nearest first
     * @param int $empNumber the reader
     */
    public static function reaches(
        string $scope,
        ?int $subunitId,
        ?int $employeeId,
        array $chainIds,
        int $empNumber
    ): bool {
        switch ($scope) {
            case self::SCOPE_NETWORK:
                return true;

            case self::SCOPE_SUBUNIT:
                return $subunitId !== null && in_array($subunitId, $chainIds, true);

            case self::SCOPE_EMPLOYEE:
                return $employeeId !== null && $employeeId === $empNumber;

            default:
                return false;
        }
    }
}
