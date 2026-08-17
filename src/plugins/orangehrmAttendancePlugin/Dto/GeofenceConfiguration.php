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

namespace OrangeHRM\Attendance\Dto;

/**
 * BR: Geofence configuration for attendance punches (Portaria 673/2021).
 * Multi-company: locations are stored per company-structure unit; the
 * employee's unit decides which locations apply.
 */
class GeofenceConfiguration
{
    private bool $enabled;

    /**
     * @var int|null Unit the locations belong to (null = default locations)
     */
    private ?int $subunitId = null;

    /**
     * @var bool Whether this unit (and everything below it) enforces geofence
     */
    private bool $geofenceRequired = false;

    /**
     * @var array[] Each: id, name, latitude, longitude, radius (meters)
     */
    private array $locations = [];

    /**
     * @return bool
     */
    public function isGeofenceRequired(): bool
    {
        return $this->geofenceRequired;
    }

    /**
     * @param bool $geofenceRequired
     */
    public function setGeofenceRequired(bool $geofenceRequired): void
    {
        $this->geofenceRequired = $geofenceRequired;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @return int|null
     */
    public function getSubunitId(): ?int
    {
        return $this->subunitId;
    }

    /**
     * @param int|null $subunitId
     */
    public function setSubunitId(?int $subunitId): void
    {
        $this->subunitId = $subunitId;
    }

    /**
     * @return array[]
     */
    public function getLocations(): array
    {
        return $this->locations;
    }

    /**
     * @param array[] $locations
     */
    public function setLocations(array $locations): void
    {
        $this->locations = $locations;
    }
}
