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

namespace OrangeHRM\Tests\Attendance\Service\Form;

use DateTime;
use OrangeHRM\Attendance\Exception\FormRuleException;
use OrangeHRM\Attendance\Service\Form\FormSubmissionRules;
use OrangeHRM\Tests\Util\TestCase;

/**
 * Whether a set of answers may be recorded.
 *
 * The phone checks too, but the server is the one that counts: whatever
 * arrives is taken as untrusted, from the deadline to the option ids.
 *
 * @group Attendance
 * @group Forms
 */
class FormSubmissionRulesTest extends TestCase
{
    private function items(): array
    {
        return [
            ['id' => 1, 'type' => 'CONTENT', 'required' => false, 'options' => []],
            ['id' => 2, 'type' => 'SINGLE', 'required' => true, 'options' => [
                ['id' => 21, 'label' => 'A', 'isCorrect' => true],
                ['id' => 22, 'label' => 'B', 'isCorrect' => false],
            ]],
            ['id' => 3, 'type' => 'MULTIPLE', 'required' => false, 'options' => [
                ['id' => 31, 'label' => 'A', 'isCorrect' => true],
                ['id' => 32, 'label' => 'B', 'isCorrect' => true],
            ]],
            ['id' => 4, 'type' => 'SHORT_TEXT', 'required' => false, 'options' => []],
            ['id' => 5, 'type' => 'LONG_TEXT', 'required' => false, 'options' => []],
            ['id' => 6, 'type' => 'SCALE', 'required' => false, 'options' => []],
            ['id' => 7, 'type' => 'YES_NO', 'required' => false, 'options' => []],
        ];
    }

    private function assertRefused(array $answers, ?int $position = null): void
    {
        try {
            FormSubmissionRules::assertAnswers($this->items(), $answers);
            $this->fail('Deveria ter recusado');
        } catch (FormRuleException $e) {
            $this->assertSame($position, $e->getItemPosition(), $e->getMessage());
        }
    }

    private function assertAccepted(array $answers): void
    {
        FormSubmissionRules::assertAnswers($this->items(), $answers);
        $this->addToAssertionCount(1);
    }

    public function testAPublishedFormWithinItsDeadlineAcceptsAnswers(): void
    {
        FormSubmissionRules::assertAccepting('PUBLISHED', new DateTime('2026-09-27 23:59:59'), new DateTime('2026-09-27 18:00'));
        FormSubmissionRules::assertAccepting('PUBLISHED', null, new DateTime('2026-09-27 18:00'));
        $this->addToAssertionCount(2);
    }

    public function testADraftOrClosedFormRefuses(): void
    {
        foreach (['DRAFT', 'CLOSED'] as $status) {
            try {
                FormSubmissionRules::assertAccepting($status, null, new DateTime());
                $this->fail("{$status} deveria recusar");
            } catch (FormRuleException $e) {
                $this->assertSame('Este formulario nao esta aberto para respostas.', $e->getMessage());
            }
        }
    }

    public function testAfterTheDeadlineNothingIsAccepted(): void
    {
        $this->expectExceptionMessage('O prazo deste formulario terminou em 27/09/2026.');
        FormSubmissionRules::assertAccepting(
            'PUBLISHED',
            new DateTime('2026-09-27 23:59:59'),
            new DateTime('2026-09-28 00:00:01')
        );
    }

    public function testOneAttemptByDefault(): void
    {
        FormSubmissionRules::assertAttemptLeft(0, 0);
        FormSubmissionRules::assertAttemptLeft(1, 1);
        $this->assertSame(3, FormSubmissionRules::allowedAttempts(2));

        $this->expectExceptionMessage('Voce ja respondeu este formulario.');
        FormSubmissionRules::assertAttemptLeft(1, 0);
    }

    public function testAFullAnswerIsAccepted(): void
    {
        $this->assertAccepted([
            2 => ['optionIds' => [21]],
            3 => ['optionIds' => [31, 32]],
            4 => ['text' => 'curto'],
            5 => ['text' => str_repeat('a', 5000)],
            6 => ['scale' => 5],
            7 => ['yesNo' => false],
        ]);
    }

    public function testARequiredQuestionLeftBlankIsRefused(): void
    {
        $this->assertRefused([], 2);
        $this->assertRefused([2 => ['optionIds' => []]], 2);
    }

    public function testAnOptionalQuestionMayBeBlank(): void
    {
        $this->assertAccepted([2 => ['optionIds' => [22]], 4 => ['text' => ''], 6 => ['scale' => null]]);
    }

    public function testContentBlocksNeedNoAnswer(): void
    {
        $this->assertAccepted([2 => ['optionIds' => [21]]]);
    }

    public function testAnOptionFromAnotherQuestionIsRefused(): void
    {
        $this->assertRefused([2 => ['optionIds' => [31]]], 2);
    }

    public function testAnOptionTickedTwiceIsRefused(): void
    {
        $this->assertRefused([2 => ['optionIds' => [21]], 3 => ['optionIds' => [31, 31]]], 3);
    }

    public function testSingleChoiceTakesOneOption(): void
    {
        $this->assertRefused([2 => ['optionIds' => [21, 22]]], 2);
    }

    public function testScaleMustBeOneToFive(): void
    {
        $this->assertRefused([2 => ['optionIds' => [21]], 6 => ['scale' => 0]], 6);
        $this->assertRefused([2 => ['optionIds' => [21]], 6 => ['scale' => 6]], 6);
    }

    public function testTextLimits(): void
    {
        $this->assertRefused([2 => ['optionIds' => [21]], 4 => ['text' => str_repeat('a', 256)]], 4);
        $this->assertRefused([2 => ['optionIds' => [21]], 5 => ['text' => str_repeat('a', 5001)]], 5);
    }

    public function testTextLimitsCountCharactersNotBytes(): void
    {
        $this->assertAccepted([2 => ['optionIds' => [21]], 4 => ['text' => str_repeat('ç', 255)]]);
    }

    public function testYesNoMustBeABoolean(): void
    {
        $this->assertRefused([2 => ['optionIds' => [21]], 7 => ['yesNo' => 'sim']], 7);
    }

    public function testAnswerForAnUnknownQuestionIsRefused(): void
    {
        $this->expectExceptionMessage('Resposta para uma questao que nao esta no formulario.');
        FormSubmissionRules::assertAnswers($this->items(), [2 => ['optionIds' => [21]], 99 => ['text' => 'x']]);
    }

    public function testAnAnswerToAContentBlockIsRefused(): void
    {
        $this->assertRefused([1 => ['text' => 'x'], 2 => ['optionIds' => [21]]], 1);
    }
}
