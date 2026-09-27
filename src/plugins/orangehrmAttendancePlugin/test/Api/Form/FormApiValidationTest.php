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

use OrangeHRM\Attendance\Api\FormAPI;
use OrangeHRM\Core\Api\V2\Exception\InvalidParamException;
use OrangeHRM\Core\Api\V2\Request;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Validator;
use OrangeHRM\Framework\Http\Request as HttpRequest;
use OrangeHRM\Tests\Util\TestCase;

/**
 * The request shapes the HR screens send, run through the endpoints' real
 * rule collections -- a rule that rejects what the builder sends takes the
 * whole screen down, as the optional flags once did to the punch.
 *
 * @group Attendance
 * @group Forms
 * @group Validation
 */
class FormApiValidationTest extends TestCase
{
    private function rules(string $class, string $method): ParamRuleCollection
    {
        return (new $class(new Request(new HttpRequest())))->$method();
    }

    private function assertAccepted(array $payload, ParamRuleCollection $rules): void
    {
        try {
            Validator::validate($payload, $rules);
            $this->addToAssertionCount(1);
        } catch (InvalidParamException $e) {
            $this->fail('Recusado: ' . implode(', ', array_keys($e->getErrorBag())));
        }
    }

    private function assertRejected(array $payload, ParamRuleCollection $rules): void
    {
        try {
            Validator::validate($payload, $rules);
            $this->fail('Deveria ter recusado');
        } catch (InvalidParamException $e) {
            $this->addToAssertionCount(1);
        }
    }

    private function definition(): array
    {
        return ['kind' => 'QUIZ', 'passPercent' => 70, 'scope' => 'NETWORK', 'items' => []];
    }

    public function testANewDraftIsAccepted(): void
    {
        $rules = $this->rules(FormAPI::class, 'getValidationRuleForCreate');
        $this->assertAccepted(['title' => 'Prova', 'description' => null, 'definition' => $this->definition()], $rules);
        $this->assertAccepted(['title' => 'Prova', 'definition' => $this->definition()], $rules);
    }

    public function testUsingATemplateSendsOnlyTheSource(): void
    {
        $this->assertAccepted(['sourceId' => 3], $this->rules(FormAPI::class, 'getValidationRuleForCreate'));
    }

    public function testATitleOverTheLimitIsRejected(): void
    {
        $this->assertRejected(
            ['title' => str_repeat('a', 151), 'definition' => $this->definition()],
            $this->rules(FormAPI::class, 'getValidationRuleForCreate')
        );
    }

    public function testADefinitionMustBeAnObject(): void
    {
        $this->assertRejected(
            ['title' => 'Prova', 'definition' => 'x'],
            $this->rules(FormAPI::class, 'getValidationRuleForCreate')
        );
    }

    public function testSavingADraftIsAccepted(): void
    {
        $this->assertAccepted(
            ['id' => 4, 'action' => 'save', 'title' => 'Prova', 'description' => 'Leia', 'definition' => $this->definition()],
            $this->rules(FormAPI::class, 'getValidationRuleForUpdate')
        );
    }

    public function testPublishingAndClosingNeedOnlyTheAction(): void
    {
        $rules = $this->rules(FormAPI::class, 'getValidationRuleForUpdate');
        $this->assertAccepted(['id' => 4, 'action' => 'publish'], $rules);
        $this->assertAccepted(['id' => 4, 'action' => 'close'], $rules);
    }

    public function testAnUnknownActionIsRejected(): void
    {
        $this->assertRejected(['id' => 4, 'action' => 'delete'], $this->rules(FormAPI::class, 'getValidationRuleForUpdate'));
    }

    public function testListingTakesNoParameters(): void
    {
        $this->assertAccepted([], $this->rules(FormAPI::class, 'getValidationRuleForGetAll'));
    }
}
