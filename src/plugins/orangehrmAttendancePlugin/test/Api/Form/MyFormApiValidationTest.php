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

use OrangeHRM\Attendance\Api\FormSubmissionAPI;
use OrangeHRM\Attendance\Api\MyFormAPI;
use OrangeHRM\Core\Api\V2\Exception\InvalidParamException;
use OrangeHRM\Core\Api\V2\Request;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Validator;
use OrangeHRM\Framework\Http\Request as HttpRequest;
use OrangeHRM\Tests\Util\TestCase;

/**
 * What the phone sends to list, open and answer a form.
 *
 * @group Attendance
 * @group Forms
 * @group Validation
 */
class MyFormApiValidationTest extends TestCase
{
    private function rules(string $class, string $method): ParamRuleCollection
    {
        return (new $class(new Request(new HttpRequest())))->$method();
    }

    private function isAccepted(array $payload, ParamRuleCollection $rules): bool
    {
        try {
            Validator::validate($payload, $rules);
            return true;
        } catch (InvalidParamException $e) {
            return false;
        }
    }

    public function testListingTakesNoParameters(): void
    {
        $this->assertTrue($this->isAccepted([], $this->rules(MyFormAPI::class, 'getValidationRuleForGetAll')));
    }

    public function testOpeningOneTakesItsId(): void
    {
        $this->assertTrue($this->isAccepted(['id' => 3], $this->rules(MyFormAPI::class, 'getValidationRuleForGetOne')));
        $this->assertFalse($this->isAccepted(['id' => 0], $this->rules(MyFormAPI::class, 'getValidationRuleForGetOne')));
    }

    public function testASubmissionIsAccepted(): void
    {
        $this->assertTrue($this->isAccepted([
            'formId' => 3,
            'answers' => [
                ['itemId' => 1, 'optionIds' => [11]],
                ['itemId' => 2, 'text' => 'Isolar a area'],
                ['itemId' => 3, 'scale' => 4],
                ['itemId' => 4, 'yesNo' => false],
            ],
        ], $this->rules(FormSubmissionAPI::class, 'getValidationRuleForCreate')));
    }

    public function testAnEmptySubmissionPassesValidation(): void
    {
        // Whether blank is allowed is the form's call (required questions), not the shape's.
        $this->assertTrue($this->isAccepted(
            ['formId' => 3, 'answers' => []],
            $this->rules(FormSubmissionAPI::class, 'getValidationRuleForCreate')
        ));
    }

    public function testASubmissionNeedsItsForm(): void
    {
        $this->assertFalse($this->isAccepted(
            ['answers' => []],
            $this->rules(FormSubmissionAPI::class, 'getValidationRuleForCreate')
        ));
    }

    public function testAnswersMustBeAList(): void
    {
        $this->assertFalse($this->isAccepted(
            ['formId' => 3, 'answers' => 'tudo certo'],
            $this->rules(FormSubmissionAPI::class, 'getValidationRuleForCreate')
        ));
    }
}
