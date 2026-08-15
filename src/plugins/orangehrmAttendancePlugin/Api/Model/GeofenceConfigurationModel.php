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

namespace OrangeHRM\Attendance\Api\Model;

use OrangeHRM\Attendance\Dto\GeofenceConfiguration;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;

/**
 * @OA\Schema(
 *     schema="Attendance-GeofenceConfigurationModel",
 *     type="object",
 *     @OA\Property(property="enabled", type="boolean"),
 *     @OA\Property(property="subunitId", type="integer", nullable=true),
 *     @OA\Property(
 *         property="locations",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer"),
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="latitude", type="number", format="float"),
 *             @OA\Property(property="longitude", type="number", format="float"),
 *             @OA\Property(property="radius", type="number", format="float")
 *         )
 *     )
 * )
 */
class GeofenceConfigurationModel implements Normalizable
{
    private GeofenceConfiguration $geofenceConfiguration;

    /**
     * @param GeofenceConfiguration $geofenceConfiguration
     */
    public function __construct(GeofenceConfiguration $geofenceConfiguration)
    {
        $this->geofenceConfiguration = $geofenceConfiguration;
    }

    /**
     * @return GeofenceConfiguration
     */
    public function getGeofenceConfiguration(): GeofenceConfiguration
    {
        return $this->geofenceConfiguration;
    }

    public function toArray(): array
    {
        $config = $this->getGeofenceConfiguration();
        return [
            'enabled' => $config->isEnabled(),
            'subunitId' => $config->getSubunitId(),
            'locations' => array_map(
                static function (array $location): array {
                    return [
                        'id' => $location['id'] ?? null,
                        'name' => $location['name'] ?? '',
                        'latitude' => (float)($location['latitude'] ?? 0),
                        'longitude' => (float)($location['longitude'] ?? 0),
                        'radius' => (float)($location['radius'] ?? 0),
                    ];
                },
                $config->getLocations()
            ),
        ];
    }
}
