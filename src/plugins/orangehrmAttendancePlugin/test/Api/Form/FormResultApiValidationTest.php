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

namespace OrangeHRM\Tests\Attendance\Api\Form;

use OrangeHRM\Attendance\Api\FormResultAPI;
use OrangeHRM\Core\Api\V2\Exception\InvalidParamException;
use OrangeHRM\Core\Api\V2\Request;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Validator;
use OrangeHRM\Framework\Http\Request as HttpRequest;
use OrangeHRM\Tests\Util\TestCase;

/**
 * What the results screen sends: reading, grading written answers, and
 * allowing another attempt.
 *
 * @group Attendance
 * @group Forms
 * @group Validation
 */
class FormResultApiValidationTest extends TestCase
{
    private function rules(string $method): ParamRuleCollection
    {
        return (new FormResultAPI(new Request(new HttpRequest())))->$method();
    }

    private function isAccepted(array $payload, string $method): bool
    {
        try {
            Validator::validate($payload, $this->rules($method));
            return true;
        } catch (InvalidParamException $e) {
            return false;
        }
    }

    public function testReadingTakesTheForm(): void
    {
        $this->assertTrue($this->isAccepted(['formId' => 3], 'getValidationRuleForGetAll'));
        $this->assertTrue($this->isAccepted(['formId' => 3, 'submissionId' => str_repeat('a', 32)], 'getValidationRuleForGetAll'));
        $this->assertFalse($this->isAccepted([], 'getValidationRuleForGetAll'));
    }

    public function testGradingSendsPointsPerQuestion(): void
    {
        $this->assertTrue($this->isAccepted(
            ['submissionId' => str_repeat('a', 32), 'points' => ['7' => 1.5, '9' => 0]],
            'getValidationRuleForUpdate'
        ));
        $this->assertFalse($this->isAccepted(['submissionId' => str_repeat('a', 32), 'points' => 3], 'getValidationRuleForUpdate'));
        $this->assertFalse($this->isAccepted(['points' => ['7' => 1]], 'getValidationRuleForUpdate'));
    }

    public function testAnotherAttemptNamesTheFormAndThePerson(): void
    {
        $this->assertTrue($this->isAccepted(['formId' => 3, 'employeeId' => 1], 'getValidationRuleForCreate'));
        $this->assertFalse($this->isAccepted(['formId' => 3], 'getValidationRuleForCreate'));
    }
}
