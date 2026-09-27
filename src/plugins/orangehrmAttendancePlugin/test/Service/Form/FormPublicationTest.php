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
use OrangeHRM\Attendance\Service\Form\FormPublication;
use OrangeHRM\Tests\Util\TestCase;

/**
 * What a form must carry before it reaches anybody.
 *
 * Once published, a form is locked -- answers are graded against it -- so
 * whatever is wrong has to be caught here, and pointed at: HR fixes a card,
 * not a form.
 *
 * @group Attendance
 * @group Forms
 */
class FormPublicationTest extends TestCase
{
    private function now(): DateTime
    {
        return new DateTime('2026-09-27 10:00:00');
    }

    private function option(string $label, bool $isCorrect = false): array
    {
        return ['id' => null, 'label' => $label, 'isCorrect' => $isCorrect];
    }

    private function item(string $type, array $overrides = []): array
    {
        return $overrides + [
            'id' => null,
            'type' => $type,
            'prompt' => 'Enunciado',
            'helpText' => null,
            'required' => true,
            'points' => 1.0,
            'imageId' => null,
            'youtubeId' => null,
            'correctYesNo' => null,
            'options' => [],
        ];
    }

    private function single(bool $withAnswer = true): array
    {
        return $this->item('SINGLE', [
            'options' => [$this->option('A', $withAnswer), $this->option('B')],
        ]);
    }

    private function definition(string $kind, array $items, array $overrides = []): array
    {
        return $overrides + [
            'kind' => $kind,
            'anonymous' => false,
            'passPercent' => $kind === 'QUIZ' ? 70 : null,
            'scope' => 'NETWORK',
            'subunitId' => null,
            'employeeId' => null,
            'dueAt' => null,
            'isTemplate' => false,
            'items' => $items,
        ];
    }

    private function assertRefused(array $definition, ?int $position = null): void
    {
        try {
            FormPublication::assertPublishable($definition, $this->now());
            $this->fail('Deveria ter recusado');
        } catch (FormRuleException $e) {
            $this->assertSame($position, $e->getItemPosition(), $e->getMessage());
        }
    }

    private function assertPublishable(array $definition): void
    {
        FormPublication::assertPublishable($definition, $this->now());
        $this->addToAssertionCount(1);
    }

    public function testAQuizWithAnAnswerKeyIsPublishable(): void
    {
        $this->assertPublishable($this->definition('QUIZ', [
            $this->item('CONTENT', ['prompt' => 'Leia antes']),
            $this->single(),
            $this->item('MULTIPLE', ['options' => [
                $this->option('A', true), $this->option('B', true), $this->option('C'),
            ]]),
            $this->item('YES_NO', ['correctYesNo' => false]),
            $this->item('LONG_TEXT'),
            $this->item('SCALE'),
        ]));
    }

    public function testASurveyNeedsNoAnswerKey(): void
    {
        $this->assertPublishable($this->definition('SURVEY', [
            $this->single(false),
            $this->item('YES_NO'),
        ]));
    }

    public function testAFormWithOnlyContentIsRefused(): void
    {
        $this->assertRefused($this->definition('SURVEY', [$this->item('CONTENT')]));
    }

    public function testAnEmptyFormIsRefused(): void
    {
        $this->assertRefused($this->definition('SURVEY', []));
    }

    public function testAChoiceNeedsTwoOptions(): void
    {
        $this->assertRefused($this->definition('SURVEY', [
            $this->item('SINGLE', ['options' => [$this->option('A')]]),
        ]), 1);
    }

    public function testAnEmptyOptionLabelIsRefused(): void
    {
        $this->assertRefused($this->definition('SURVEY', [
            $this->item('SINGLE', ['options' => [$this->option('A'), $this->option('  ')]]),
        ]), 1);
    }

    public function testAQuizSingleChoiceNeedsExactlyOneCorrect(): void
    {
        $this->assertRefused($this->definition('QUIZ', [$this->single(), $this->single(false)]), 2);
        $this->assertRefused($this->definition('QUIZ', [
            $this->item('SINGLE', ['options' => [$this->option('A', true), $this->option('B', true)]]),
        ]), 1);
    }

    public function testAQuizMultipleChoiceNeedsAtLeastOneCorrect(): void
    {
        $this->assertRefused($this->definition('QUIZ', [
            $this->item('MULTIPLE', ['options' => [$this->option('A'), $this->option('B')]]),
        ]), 1);
    }

    public function testAQuizYesNoNeedsItsAnswer(): void
    {
        $this->assertRefused($this->definition('QUIZ', [$this->item('YES_NO')]), 1);
    }

    public function testAQuizNeedsAPassMark(): void
    {
        $this->assertRefused($this->definition('QUIZ', [$this->single()], ['passPercent' => null]));
        $this->assertRefused($this->definition('QUIZ', [$this->single()], ['passPercent' => 101]));
        $this->assertPublishable($this->definition('QUIZ', [$this->single()], ['passPercent' => 0]));
    }

    public function testAQuizCannotBeAnonymous(): void
    {
        $this->assertRefused($this->definition('QUIZ', [$this->single()], ['anonymous' => true]));
    }

    /**
     * Anonymous to one person is anonymous in name only: whoever reads the
     * answers knows exactly who wrote them.
     */
    public function testAnAnonymousSurveyForOnePersonIsRefused(): void
    {
        $this->assertRefused($this->definition('SURVEY', [$this->single(false)], [
            'anonymous' => true, 'scope' => 'EMPLOYEE', 'employeeId' => 7,
        ]));
    }

    public function testATargetedFormNeedsItsTarget(): void
    {
        $this->assertRefused($this->definition('SURVEY', [$this->single(false)], ['scope' => 'SUBUNIT']));
        $this->assertRefused($this->definition('SURVEY', [$this->single(false)], ['scope' => 'EMPLOYEE']));
        $this->assertPublishable($this->definition('SURVEY', [$this->single(false)], [
            'scope' => 'SUBUNIT', 'subunitId' => 2,
        ]));
    }

    public function testAnUnknownScopeIsRefused(): void
    {
        $this->assertRefused($this->definition('SURVEY', [$this->single(false)], ['scope' => 'WORLD']));
    }

    public function testADeadlineInThePastIsRefused(): void
    {
        $this->assertRefused($this->definition('SURVEY', [$this->single(false)], [
            'dueAt' => new DateTime('2026-09-26 23:59:59'),
        ]));
        $this->assertPublishable($this->definition('SURVEY', [$this->single(false)], [
            'dueAt' => new DateTime('2026-09-27 23:59:59'),
        ]));
    }

    public function testATemplateIsNeverPublished(): void
    {
        $this->assertRefused($this->definition('SURVEY', [$this->single(false)], ['isTemplate' => true]));
    }

    public function testAnEmptyPromptIsRefused(): void
    {
        $this->assertRefused($this->definition('SURVEY', [$this->item('SCALE', ['prompt' => ' '])]), 1);
    }

    public function testAnUnknownItemTypeIsRefused(): void
    {
        $this->assertRefused($this->definition('SURVEY', [$this->single(false), $this->item('DATE')]), 2);
    }

    public function testTheMessageNamesTheBlock(): void
    {
        try {
            FormPublication::assertPublishable(
                $this->definition('QUIZ', [$this->single(), $this->single(false)]),
                $this->now()
            );
            $this->fail('Deveria ter recusado');
        } catch (FormRuleException $e) {
            $this->assertStringStartsWith('Bloco 2:', $e->getMessage());
        }
    }
}
