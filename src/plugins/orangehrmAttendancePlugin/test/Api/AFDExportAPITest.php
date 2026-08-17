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

use OrangeHRM\Attendance\Api\AFDExportAPI;
use OrangeHRM\Authentication\Auth\User;
use OrangeHRM\Core\Api\V2\Request;
use OrangeHRM\Core\Api\V2\Validator\Rules\Composite\OneOf;
use OrangeHRM\Core\Api\V2\Validator\Validator;
use OrangeHRM\Framework\Http\Request as HttpRequest;
use OrangeHRM\Framework\ServiceContainer;
use OrangeHRM\Framework\Services;
use OrangeHRM\Tests\Util\TestCase;

/**
 * @group Attendance
 * @group APIv2
 */
class AFDExportAPITest extends TestCase
{
    private const EMP_NUMBER = 1;

    protected function setUp(): void
    {
        parent::setUp();
        // InAccessibleEmpNumbers short-circuits to true for the logged-in
        // employee, so the auth user is the only service these rules need.
        // The real User is session-backed, hence the mock.
        $authUser = $this->createMock(User::class);
        $authUser->method('getEmpNumber')->willReturn(self::EMP_NUMBER);
        ServiceContainer::getContainer()->set(Services::AUTH_USER, $authUser);
    }

    private function getApi(): AFDExportAPI
    {
        return new AFDExportAPI(new Request(new HttpRequest()));
    }

    /**
     * ParamRule composes its rules with AllOf, and NotRequired only accepts empty
     * values -- so listing NOT_REQUIRED beside the real rule makes the parameter
     * impossible to satisfy. Optional params must go through notRequiredParamRule().
     */
    public function testEmpNumberRuleIsOptionalNotUnsatisfiable(): void
    {
        $empRule = $this->getApi()->getValidationRuleForGetOne()->getMap()['empNumber'];

        $this->assertSame(OneOf::class, $empRule->getCompositeClass());
    }

    public function testGetOneValidationAcceptsAnEmpNumber(): void
    {
        $values = [
            'fromDate' => '2026-08-01',
            'toDate' => '2026-08-17',
            'empNumber' => (string)self::EMP_NUMBER,
        ];

        $this->assertTrue(Validator::validate($values, $this->getApi()->getValidationRuleForGetOne()));
    }

    public function testGetOneValidationAcceptsOmittedEmpNumber(): void
    {
        $values = ['fromDate' => '2026-08-01', 'toDate' => '2026-08-17'];

        $this->assertTrue(Validator::validate($values, $this->getApi()->getValidationRuleForGetOne()));
    }

    public function testGetAllReusesTheGetOneRules(): void
    {
        $values = [
            'fromDate' => '2026-08-01',
            'toDate' => '2026-08-17',
            'empNumber' => (string)self::EMP_NUMBER,
        ];

        $this->assertTrue(Validator::validate($values, $this->getApi()->getValidationRuleForGetAll()));
    }
}
