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

namespace OrangeHRM\Tests\Attendance\Api\Assessment;

use OrangeHRM\Attendance\Api\AssessmentAPI;
use OrangeHRM\Attendance\Api\AssessmentResultAPI;
use OrangeHRM\Attendance\Api\MyAssessmentAPI;
use OrangeHRM\Core\Api\V2\Exception\InvalidParamException;
use OrangeHRM\Core\Api\V2\Request;
use OrangeHRM\Core\Api\V2\Validator\Validator;
use OrangeHRM\Framework\Http\Request as HttpRequest;
use OrangeHRM\Tests\Util\TestCase;

/**
 * What the HR screens and the employee's app send.
 *
 * @group Attendance
 * @group Assessment
 * @group Validation
 */
class AssessmentApiValidationTest extends TestCase
{
    private function accepted(string $class, string $method, array $payload): bool
    {
        try {
            Validator::validate($payload, (new $class(new Request(new HttpRequest())))->$method());
            return true;
        } catch (InvalidParamException $e) {
            return false;
        }
    }

    public function testListingTakesOptionalFilters(): void
    {
        $this->assertTrue($this->accepted(AssessmentAPI::class, 'getValidationRuleForGetAll', []));
        $this->assertTrue($this->accepted(AssessmentAPI::class, 'getValidationRuleForGetAll', [
            'subjectType' => 'CANDIDATE', 'status' => 'COMPLETED', 'vacancyId' => 2,
        ]));
        $this->assertFalse($this->accepted(AssessmentAPI::class, 'getValidationRuleForGetAll', ['status' => 'LIXO']));
    }

    public function testInvitingACandidate(): void
    {
        $this->assertTrue($this->accepted(AssessmentAPI::class, 'getValidationRuleForCreate', ['candidateId' => 5]));
        $this->assertTrue($this->accepted(AssessmentAPI::class, 'getValidationRuleForCreate', ['candidateId' => 5, 'vacancyId' => 2]));
    }

    public function testInvitingEmployees(): void
    {
        $this->assertTrue($this->accepted(AssessmentAPI::class, 'getValidationRuleForCreate', ['scope' => 'NETWORK']));
        $this->assertTrue($this->accepted(AssessmentAPI::class, 'getValidationRuleForCreate', ['scope' => 'SUBUNIT', 'subunitId' => 2]));
        $this->assertFalse($this->accepted(AssessmentAPI::class, 'getValidationRuleForCreate', ['scope' => 'MUNDO']));
    }

    public function testResendAndCancel(): void
    {
        $this->assertTrue($this->accepted(AssessmentAPI::class, 'getValidationRuleForUpdate', ['id' => 3, 'action' => 'resend']));
        $this->assertTrue($this->accepted(AssessmentAPI::class, 'getValidationRuleForUpdate', ['id' => 3, 'action' => 'cancel']));
        $this->assertFalse($this->accepted(AssessmentAPI::class, 'getValidationRuleForUpdate', ['id' => 3, 'action' => 'delete']));
    }

    public function testProfileTakesTheAssessment(): void
    {
        $this->assertTrue($this->accepted(AssessmentResultAPI::class, 'getValidationRuleForGetAll', ['assessmentId' => 3]));
        $this->assertFalse($this->accepted(AssessmentResultAPI::class, 'getValidationRuleForGetAll', []));
    }

    public function testTheEmployeeAnswersAPageOrFinishes(): void
    {
        $this->assertTrue($this->accepted(MyAssessmentAPI::class, 'getValidationRuleForUpdate', ['id' => 3, 'answers' => ['B5-E01' => 4]]));
        $this->assertTrue($this->accepted(MyAssessmentAPI::class, 'getValidationRuleForUpdate', ['id' => 3, 'complete' => true]));
        $this->assertFalse($this->accepted(MyAssessmentAPI::class, 'getValidationRuleForUpdate', ['id' => 3, 'answers' => 'x']));
    }
}
