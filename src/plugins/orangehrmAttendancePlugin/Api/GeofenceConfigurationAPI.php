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

namespace OrangeHRM\Attendance\Api;

use OrangeHRM\Attendance\Api\Model\GeofenceConfigurationModel;
use OrangeHRM\Attendance\Dto\GeofenceConfiguration;
use OrangeHRM\Attendance\Service\GeofenceService;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Exception\RecordNotFoundException;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Core\Traits\Service\ConfigServiceTrait;
use OrangeHRM\Entity\GeofenceLocation;
use OrangeHRM\Entity\Subunit;

/**
 * BR: Geofence configuration for attendance punches (Portaria 673/2021),
 * multi-company aware.
 *
 * Locations are stored per company-structure unit (ohrm_attendance_geofence_
 * location). scope = subunitId; null means the legacy default set, which no
 * longer takes part in enforcement.
 *
 * `enabled` is the global master switch. `geofenceRequired` is per unit and
 * turns enforcement on for that unit and everything below it -- it only
 * applies to a real scope, so it is ignored when subunitId is null.
 *
 * GET  - any authenticated user (the mobile punch page reads `enabled`)
 * PUT  - Admin only (enforced by data-group grants in the migration)
 */
class GeofenceConfigurationAPI extends Endpoint implements ResourceEndpoint
{
    use ConfigServiceTrait;
    use EntityManagerHelperTrait;

    public const PARAMETER_ENABLED = 'enabled';
    public const PARAMETER_SUBUNIT_ID = 'subunitId';
    public const PARAMETER_LOCATIONS = 'locations';
    public const PARAMETER_GEOFENCE_REQUIRED = 'geofenceRequired';
    public const PARAMETER_LOCATION_ID = 'id';
    public const PARAMETER_LOCATION_NAME = 'name';
    public const PARAMETER_LOCATION_LATITUDE = 'latitude';
    public const PARAMETER_LOCATION_LONGITUDE = 'longitude';
    public const PARAMETER_LOCATION_RADIUS = 'radius';

    public const PARAM_RULE_LOCATION_NAME_MAX_LENGTH = 100;
    public const PARAM_RULE_LOCATION_RADIUS_MAX = 50000; // 50km — beyond this a geofence is meaningless
    public const PARAM_RULE_LOCATIONS_MAX_COUNT = 20;

    /**
     * @OA\Get(
     *     path="/api/v2/attendance/geofence",
     *     tags={"Attendance/Geofence"},
     *     summary="Get Attendance Geofence Configuration",
     *     operationId="get-attendance-geofence-configuration",
     *     @OA\Parameter(
     *         name="subunitId",
     *         in="query",
     *         required=false,
     *         description="Company structure unit scope; omit for the default location set",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/components/schemas/Attendance-GeofenceConfigurationModel"
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     *
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $subunitId = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_SUBUNIT_ID
        );
        $subunit = $this->resolveSubunit($subunitId);

        $geofenceService = new GeofenceService();

        $geofenceConfiguration = new GeofenceConfiguration();
        $geofenceConfiguration->setEnabled($geofenceService->isEnabled());
        $geofenceConfiguration->setSubunitId($subunitId);
        $geofenceConfiguration->setGeofenceRequired($subunit !== null && $subunit->isGeofenceRequired());
        $geofenceConfiguration->setLocations(
            $this->locationsToArray($geofenceService->getLocationsForScope($subunit))
        );

        return new EndpointResourceResult(GeofenceConfigurationModel::class, $geofenceConfiguration);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        $paramRules = new ParamRuleCollection();
        $paramRules->addExcludedParamKey(CommonParams::PARAMETER_ID);
        $paramRules->addParamValidation(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_SUBUNIT_ID,
                    new Rule(Rules::POSITIVE)
                )
            )
        );
        return $paramRules;
    }

    /**
     * @OA\Put(
     *     path="/api/v2/attendance/geofence",
     *     tags={"Attendance/Geofence"},
     *     summary="Update Attendance Geofence Configuration",
     *     operationId="update-attendance-geofence-configuration",
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="enabled", type="boolean", example="true"),
     *             @OA\Property(
     *                 property="subunitId",
     *                 type="integer",
     *                 nullable=true,
     *                 description="Company structure unit scope; null = default location set"
     *             ),
     *             @OA\Property(
     *                 property="geofenceRequired",
     *                 type="boolean",
     *                 description="Unit (and everything below it) enforces geofence; ignored for the default scope"
     *             ),
     *             @OA\Property(
     *                 property="locations",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", description="Present on update"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="latitude", type="number", format="float"),
     *                     @OA\Property(property="longitude", type="number", format="float"),
     *                     @OA\Property(property="radius", type="number", format="float")
     *                 )
     *             ),
     *             required={"enabled", "locations"}
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/components/schemas/Attendance-GeofenceConfigurationModel"
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     *
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $enabled = $this->getRequestParams()->getBoolean(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_ENABLED
        );
        $subunitId = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_SUBUNIT_ID
        );
        $subunit = $this->resolveSubunit($subunitId);

        $locations = $this->getRequestParams()->getArray(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_LOCATIONS
        );

        $normalizedLocations = [];
        foreach ($locations as $location) {
            $normalizedLocations[] = [
                'id' => isset($location[self::PARAMETER_LOCATION_ID])
                    ? (int)$location[self::PARAMETER_LOCATION_ID]
                    : null,
                'name' => (string)$location[self::PARAMETER_LOCATION_NAME],
                'latitude' => (float)$location[self::PARAMETER_LOCATION_LATITUDE],
                'longitude' => (float)$location[self::PARAMETER_LOCATION_LONGITUDE],
                'radius' => (float)$location[self::PARAMETER_LOCATION_RADIUS],
            ];
        }

        $geofenceRequired = $this->getRequestParams()->getBooleanOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_GEOFENCE_REQUIRED
        );

        $geofenceService = new GeofenceService();
        $geofenceService->setEnabled($enabled);
        $persisted = $geofenceService->replaceLocationsForScope($subunit, $normalizedLocations);

        // The flag lives on the unit, so the default scope (no unit) has none.
        if ($subunit !== null && $geofenceRequired !== null) {
            $subunit->setGeofenceRequired($geofenceRequired);
            $this->getEntityManager()->persist($subunit);
            $this->getEntityManager()->flush();
        }

        $geofenceConfiguration = new GeofenceConfiguration();
        $geofenceConfiguration->setEnabled($enabled);
        $geofenceConfiguration->setSubunitId($subunitId);
        $geofenceConfiguration->setGeofenceRequired($subunit !== null && $subunit->isGeofenceRequired());
        $geofenceConfiguration->setLocations($this->locationsToArray($persisted));

        return new EndpointResourceResult(GeofenceConfigurationModel::class, $geofenceConfiguration);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        $paramRules = new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_ENABLED,
                new Rule(Rules::BOOL_TYPE)
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_SUBUNIT_ID,
                    new Rule(Rules::POSITIVE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_GEOFENCE_REQUIRED,
                    new Rule(Rules::BOOL_TYPE)
                )
            ),
            new ParamRule(
                self::PARAMETER_LOCATIONS,
                new Rule(Rules::ARRAY_TYPE),
                new Rule(Rules::LENGTH, [0, self::PARAM_RULE_LOCATIONS_MAX_COUNT]),
                new Rule(
                    Rules::EACH,
                    [
                        new Rules\Composite\AllOf(
                            new Rule(
                                Rules::KEY,
                                [
                                    self::PARAMETER_LOCATION_ID,
                                    new Rules\Composite\OneOf(
                                        new Rule(Rules::NULL_TYPE),
                                        new Rule(Rules::POSITIVE)
                                    ),
                                    false, // optional on create
                                ]
                            ),
                            new Rule(
                                Rules::KEY,
                                [
                                    self::PARAMETER_LOCATION_NAME,
                                    new Rules\Composite\AllOf(
                                        new Rule(Rules::STRING_TYPE),
                                        new Rule(Rules::LENGTH, [1, self::PARAM_RULE_LOCATION_NAME_MAX_LENGTH])
                                    )
                                ]
                            ),
                            new Rule(
                                Rules::KEY,
                                [
                                    self::PARAMETER_LOCATION_LATITUDE,
                                    new Rules\Composite\AllOf(new Rule(Rules::BETWEEN, [-90, 90]))
                                ]
                            ),
                            new Rule(
                                Rules::KEY,
                                [
                                    self::PARAMETER_LOCATION_LONGITUDE,
                                    new Rules\Composite\AllOf(new Rule(Rules::BETWEEN, [-180, 180]))
                                ]
                            ),
                            new Rule(
                                Rules::KEY,
                                [
                                    self::PARAMETER_LOCATION_RADIUS,
                                    new Rules\Composite\AllOf(
                                        new Rule(Rules::BETWEEN, [1, self::PARAM_RULE_LOCATION_RADIUS_MAX])
                                    )
                                ]
                            )
                        )
                    ]
                )
            )
        );
        // This is a singleton resource: the route supplies `id: 0`, which strict
        // validation would otherwise reject as an unexpected parameter.
        $paramRules->addExcludedParamKey(CommonParams::PARAMETER_ID);
        return $paramRules;
    }

    /**
     * Resolve the scope subunit; null id = default location scope.
     *
     * @param int|null $subunitId
     * @return Subunit|null
     * @throws RecordNotFoundException
     */
    private function resolveSubunit(?int $subunitId): ?Subunit
    {
        if ($subunitId === null) {
            return null;
        }
        $subunit = $this->getEntityManager()->find(Subunit::class, $subunitId);
        if (!$subunit instanceof Subunit) {
            throw new RecordNotFoundException();
        }
        return $subunit;
    }

    /**
     * @param GeofenceLocation[] $locations
     * @return array[]
     */
    private function locationsToArray(array $locations): array
    {
        return array_map(
            static function (GeofenceLocation $location): array {
                return [
                    'id' => $location->getId(),
                    'name' => $location->getName(),
                    'latitude' => (float)$location->getLatitude(),
                    'longitude' => (float)$location->getLongitude(),
                    'radius' => (float)$location->getRadius(),
                ];
            },
            array_values($locations)
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
