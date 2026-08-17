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

namespace OrangeHRM\Attendance\Exception;

use Exception;

class AttendanceServiceException extends Exception
{
    /**
     * @return static
     */
    public static function punchOutAlreadyExist(): self
    {
        return new self('Cannot Proceed Punch Out Employee Already Punched Out');
    }

    /**
     * @return static
     */
    public static function punchInAlreadyExist(): self
    {
        return new self('Cannot Proceed Punch In Employee Already Punched In');
    }

    /**
     * @return static
     */
    public static function punchOutTimeBehindThanPunchInTime(): self
    {
        return new self('Punch Out Time Should Be Later Than Punch In Time');
    }

    /**
     * @return static
     */
    public static function punchInOverlapFound(): self
    {
        return new self('Punch-In Overlap Found');
    }

    /**
     * @return static
     */
    public static function punchOutOverlapFound(): self
    {
        return new self('Punch-Out Overlap Found');
    }

    /**
     * @return static
     */
    public static function invalidDateTime(): self
    {
        return new self('Provided Date And Time Invalid');
    }

    /**
     * @return static
     */
    public static function punchOutDateTimeNull(): self
    {
        return new self('Punch Out Date And Time Should Not Be Null');
    }

    /**
     * @return static
     */
    public static function deletableAttendanceRecordIdsEmpty(): self
    {
        return new self('No IDs Found');
    }

    /**
     * @return static
     */
    public static function invalidTimezoneDetails(): self
    {
        return new self('Valid Timezone Offset and Timezone Name Must Be Provided');
    }

    /**
     * BR: Geofence is enabled but the punch was sent without GPS coordinates.
     *
     * @return static
     */
    public static function geofenceCoordinatesMissing(): self
    {
        return new self('Geofence Validation Failed - Location Coordinates Required');
    }

    /**
     * BR: Geofence is enabled and the punch coordinates are outside every allowed area.
     *
     * @return static
     */
    public static function geofenceOutsideAllowedArea(): self
    {
        return new self('Geofence Validation Failed - Location Outside Allowed Area');
    }

    /**
     * BR: the employee is not posted to any company-structure unit, so there is
     * no way to tell which company's fence applies.
     *
     * @return static
     */
    public static function geofenceMissingSubunit(): self
    {
        return new self('Geofence Validation Failed - Employee Has No Company Unit');
    }

    /**
     * BR: the employee's company requires geofence but has no location
     * registered anywhere up its chain. Refused rather than waved through: an
     * unconfigured company must not accept punches from anywhere.
     *
     * @return static
     */
    public static function geofenceNotConfigured(): self
    {
        return new self('Geofence Validation Failed - No Location Registered For This Company');
    }
}
