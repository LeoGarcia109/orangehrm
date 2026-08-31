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

use OrangeHRM\Attendance\Exception\AttendanceServiceException;

/**
 * BR: what a punch recorded on somebody else's behalf has to carry.
 *
 * The geofence cannot be evaluated for a proxy punch -- the coordinates belong
 * to whoever is operating the screen, not to the worker being recorded -- so
 * these punches are the one route around a fence the company made mandatory.
 * They stay allowed, because a forgotten punch has to be fixable, but at a
 * geofenced company they have to state a reason, which lands in the audit
 * trail next to who recorded it.
 */
class ProxyPunchGuard
{
    /**
     * Shorter than this is not a reason, it is a keystroke to get past a
     * required field.
     */
    public const MIN_JUSTIFICATION_LENGTH = 5;

    /**
     * @param bool $isSelfPunch whether the punching user is the worker
     * @param bool $geofenceRequired whether the worker's company demands geofence
     * @param string|null $justification the note carried by the punch
     * @throws AttendanceServiceException when a reason is required and absent
     */
    public static function assertJustified(
        bool $isSelfPunch,
        bool $geofenceRequired,
        ?string $justification
    ): void {
        if ($isSelfPunch || !$geofenceRequired) {
            return;
        }

        if (mb_strlen(trim((string)$justification)) < self::MIN_JUSTIFICATION_LENGTH) {
            throw AttendanceServiceException::proxyPunchNeedsJustification();
        }
    }
}
