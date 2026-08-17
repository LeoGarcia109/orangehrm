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

namespace OrangeHRM\Tests\Attendance\Api;

use OrangeHRM\Attendance\Api\GeofenceConfigurationAPI;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\Request;
use OrangeHRM\Core\Api\V2\Validator\Validator;
use OrangeHRM\Framework\Http\Request as HttpRequest;
use OrangeHRM\Tests\Util\TestCase;

/**
 * @group Attendance
 * @group APIv2
 */
class GeofenceConfigurationAPITest extends TestCase
{
    private function getApi(): GeofenceConfigurationAPI
    {
        return new GeofenceConfigurationAPI(new Request(new HttpRequest()));
    }

    private function validLocation(): array
    {
        return [
            'id' => null,
            'name' => 'Matriz',
            'latitude' => -14.678,
            'longitude' => -39.378,
            'radius' => 300,
        ];
    }

    /**
     * `/api/v2/attendance/geofence` is a singleton resource, so its route supplies
     * a fixed `id: 0` default. Validator::validate() reads it via
     * Request::getAllParameters(), and strict collections reject any key without a
     * rule -- so the update rules must exclude it or every PUT returns 422.
     */
    public function testUpdateValidationAcceptsRouteSuppliedId(): void
    {
        $values = [
            CommonParams::PARAMETER_ID => 0,
            'enabled' => true,
            'subunitId' => 2,
            'locations' => [$this->validLocation()],
        ];

        $this->assertTrue(Validator::validate($values, $this->getApi()->getValidationRuleForUpdate()));
    }

    public function testUpdateValidationAcceptsNullSubunitScope(): void
    {
        $values = [
            CommonParams::PARAMETER_ID => 0,
            'enabled' => true,
            'subunitId' => null,
            'locations' => [$this->validLocation()],
        ];

        $this->assertTrue(Validator::validate($values, $this->getApi()->getValidationRuleForUpdate()));
    }

    public function testUpdateValidationAcceptsEmptyLocationList(): void
    {
        $values = [
            CommonParams::PARAMETER_ID => 0,
            'enabled' => true,
            'subunitId' => 2,
            'locations' => [],
        ];

        $this->assertTrue(Validator::validate($values, $this->getApi()->getValidationRuleForUpdate()));
    }

    public function testGetOneValidationAcceptsRouteSuppliedId(): void
    {
        $values = [CommonParams::PARAMETER_ID => 0, 'subunitId' => 2];

        $this->assertTrue(Validator::validate($values, $this->getApi()->getValidationRuleForGetOne()));
    }
}
