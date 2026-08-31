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
 * BR: how far back a punch synced from the offline queue may reach.
 *
 * A self punch is normally pinned to the server clock within three minutes
 * (`MyAttendanceRecordAPI::isCurrantDateTimeValid`), which is what stops an
 * employee from choosing their own punch time. A punch taken without signal
 * cannot satisfy that -- only the device witnessed it -- so accepting the
 * queue at all means loosening the control.
 *
 * The loosening is bounded and opt-in: the employer switches it on, the window
 * says how far back it reaches, and every punch that arrives this way is
 * marked in the audit trail. Past the window it stops being a sync and becomes
 * a correction, which belongs to the retification flow and its approvals.
 */
class OfflinePunchWindow
{
    /**
     * Device clocks drift; the same margin the online path already tolerates.
     */
    public const FUTURE_TOLERANCE_SECONDS = 180;

    /**
     * @param bool $featureEnabled whether the employer switched the queue on
     * @param DateTime $punchedAt the time the device recorded
     * @param DateTime $now server time
     * @param int $maxHours how far back the window reaches
     * @throws AttendanceServiceException when the punch may not be synced
     */
    public static function assertAcceptable(
        bool $featureEnabled,
        DateTime $punchedAt,
        DateTime $now,
        int $maxHours
    ): void {
        if (!$featureEnabled) {
            throw AttendanceServiceException::offlinePunchDisabled();
        }

        $secondsLate = $now->getTimestamp() - $punchedAt->getTimestamp();

        if ($secondsLate < -self::FUTURE_TOLERANCE_SECONDS) {
            throw AttendanceServiceException::offlinePunchInTheFuture();
        }

        if ($secondsLate > $maxHours * 3600) {
            throw AttendanceServiceException::offlinePunchTooOld($maxHours);
        }
    }
}
