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

use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Core\Traits\Service\ConfigServiceTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\GeofenceLocation;
use OrangeHRM\Entity\Subunit;

/**
 * BR: Geofence validation for attendance punches (multi-company).
 *
 * Locations live in ohrm_attendance_geofence_location. Each row belongs to a
 * company-structure unit (subunit_id) — the employee's unit decides which
 * locations apply. Rows with subunit_id NULL are the default set, used when
 * the employee's unit has none (or the employee has no unit).
 *
 * Global on/off switch: hs_hr_config attendance.br.geofence.enabled.
 */
class GeofenceService
{
    use ConfigServiceTrait;
    use EntityManagerHelperTrait;

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
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->getConfigService()->setAttendanceBrGeofenceEnabled($enabled);
    }

    /**
     * Locations registered for a unit; falls back to the default set
     * (subunit_id NULL) when the unit has none.
     *
     * @param Subunit|null $subunit
     * @return GeofenceLocation[]
     */
    public function getLocationsForSubunit(?Subunit $subunit): array
    {
        $locations = $this->getLocationsForScope($subunit);

        if (!empty($locations) || $subunit === null) {
            return $locations;
        }

        // Fall back to default locations
        return $this->getLocationsForScope(null);
    }

    /**
     * Exact locations registered for a scope (unit, or the default set when
     * null). No fallback — used by the admin configuration API.
     *
     * @param Subunit|null $subunit
     * @return GeofenceLocation[]
     */
    public function getLocationsForScope(?Subunit $subunit): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('gl')
            ->from(GeofenceLocation::class, 'gl')
            ->orderBy('gl.name', 'ASC');

        if ($subunit === null) {
            $qb->where($qb->expr()->isNull('gl.subunit'));
        } else {
            $qb->where($qb->expr()->eq('gl.subunit', ':subunit'))
                ->setParameter('subunit', $subunit->getId());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Replace the locations of a scope (unit, or the default set when null).
     * Locations carrying an existing row id are updated, new ones inserted,
     * and rows of the scope missing from the payload are deleted.
     *
     * @param Subunit|null $subunit
     * @param array[] $locations Each: id?, name, latitude, longitude, radius
     * @return GeofenceLocation[] Persisted locations
     */
    public function replaceLocationsForScope(?Subunit $subunit, array $locations): array
    {
        $em = $this->getEntityManager();
        $existing = [];
        foreach ($this->getLocationsForScope($subunit) as $location) {
            $existing[$location->getId()] = $location;
        }

        $keptIds = [];
        $result = [];
        foreach ($locations as $data) {
            $id = isset($data['id']) ? (int)$data['id'] : null;
            $isNew = !($id !== null && isset($existing[$id]));
            $location = $isNew ? new GeofenceLocation() : $existing[$id];

            $location->setSubunit($subunit);
            $location->setName((string)$data['name']);
            $location->setLatitude(sprintf('%.8f', (float)$data['latitude']));
            $location->setLongitude(sprintf('%.8f', (float)$data['longitude']));
            $location->setRadius((int)round((float)$data['radius']));

            if ($isNew) {
                $em->persist($location);
            } else {
                $keptIds[] = $id;
            }
            $result[] = $location;
        }

        foreach ($existing as $id => $location) {
            if (!in_array($id, $keptIds, true)) {
                $em->remove($location);
            }
        }

        $em->flush();

        return $result;
    }

    /**
     * Effective locations for an employee (own unit, with default fallback).
     *
     * @param Employee $employee
     * @return GeofenceLocation[]
     */
    public function getEffectiveLocationsForEmployee(Employee $employee): array
    {
        return $this->getLocationsForSubunit($employee->getSubDivision());
    }

    /**
     * Validate coordinates against the configured geofence for an employee.
     *
     * Returns an array:
     *   ['valid' => bool, 'reason' => string|null, 'nearestDistance' => float|null]
     *
     * When geofence is disabled, always returns valid.
     * When enabled but no coordinates were captured, returns invalid
     * with reason 'missing_coordinates'.
     * When enabled but no locations are configured anywhere, punches pass.
     *
     * @param Employee $employee
     * @param float|null $latitude
     * @param float|null $longitude
     * @return array
     */
    public function validateForEmployee(Employee $employee, ?float $latitude, ?float $longitude): array
    {
        if (!$this->isEnabled()) {
            return ['valid' => true, 'reason' => null, 'nearestDistance' => null];
        }

        if ($latitude === null || $longitude === null) {
            return ['valid' => false, 'reason' => 'missing_coordinates', 'nearestDistance' => null];
        }

        $locations = $this->getEffectiveLocationsForEmployee($employee);
        if (empty($locations)) {
            return ['valid' => true, 'reason' => null, 'nearestDistance' => null];
        }

        $nearestDistance = null;
        foreach ($locations as $location) {
            $distance = $this->haversineDistance(
                $latitude,
                $longitude,
                (float)$location->getLatitude(),
                (float)$location->getLongitude()
            );
            if ($nearestDistance === null || $distance < $nearestDistance) {
                $nearestDistance = $distance;
            }
            if ($distance <= (float)$location->getRadius()) {
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
