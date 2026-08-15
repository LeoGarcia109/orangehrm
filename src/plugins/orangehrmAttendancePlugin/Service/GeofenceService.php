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

use OrangeHRM\Core\Traits\Service\ConfigServiceTrait;

/**
 * BR: Geofence validation for attendance punches.
 *
 * When geofence is enabled, punches must include GPS coordinates that fall
 * within at least one configured allowed location (center + radius in
 * meters). Distance is computed with the haversine formula.
 *
 * Configuration lives in hs_hr_config:
 *   attendance.br.geofence.enabled   => 'true' | 'false'
 *   attendance.br.geofence.locations => JSON array of locations:
 *     [{"name": "Escritório", "latitude": -23.55, "longitude": -46.63, "radius": 300}]
 */
class GeofenceService
{
    use ConfigServiceTrait;

    /**
     * Earth radius in meters (mean value used by haversine).
     */
    private const EARTH_RADIUS_METERS = 6371008.8;

    /**
     * @return bool Whether geofence enforcement is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->getConfigService()->getAttendanceBrGeofenceEnabled();
    }

    /**
     * @return array List of allowed locations (name, latitude, longitude, radius).
     */
    public function getLocations(): array
    {
        return $this->getConfigService()->getAttendanceBrGeofenceLocations();
    }

    /**
     * Validate coordinates against the configured geofence.
     *
     * Returns an array:
     *   ['valid' => bool, 'reason' => string|null, 'nearestDistance' => float|null]
     *
     * When geofence is disabled, always returns valid.
     * When enabled but no coordinates were captured, returns invalid
     * with reason 'missing_coordinates'.
     *
     * @param float|null $latitude
     * @param float|null $longitude
     * @return array
     */
    public function validate(?float $latitude, ?float $longitude): array
    {
        if (!$this->isEnabled()) {
            return ['valid' => true, 'reason' => null, 'nearestDistance' => null];
        }

        if ($latitude === null || $longitude === null) {
            return ['valid' => false, 'reason' => 'missing_coordinates', 'nearestDistance' => null];
        }

        $locations = $this->getLocations();
        if (empty($locations)) {
            // Geofence enabled without any configured location: do not block punches.
            return ['valid' => true, 'reason' => null, 'nearestDistance' => null];
        }

        $nearestDistance = null;
        foreach ($locations as $location) {
            if (!isset($location['latitude'], $location['longitude'], $location['radius'])) {
                continue;
            }
            $distance = $this->haversineDistance(
                $latitude,
                $longitude,
                (float)$location['latitude'],
                (float)$location['longitude']
            );
            if ($nearestDistance === null || $distance < $nearestDistance) {
                $nearestDistance = $distance;
            }
            if ($distance <= (float)$location['radius']) {
                return ['valid' => true, 'reason' => null, 'nearestDistance' => $distance];
            }
        }

        return ['valid' => false, 'reason' => 'outside_allowed_area', 'nearestDistance' => $nearestDistance];
    }

    /**
     * Haversine distance between two coordinates, in meters.
     *
     * @param float $lat1
     * @param float $lng1
     * @param float $lat2
     * @param float $lng2
     * @return float
     */
    public function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);
        $a = sin($deltaLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($deltaLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return self::EARTH_RADIUS_METERS * $c;
    }
}
