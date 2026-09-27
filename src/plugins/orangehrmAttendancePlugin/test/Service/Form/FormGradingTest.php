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

use OrangeHRM\Attendance\Exception\FormRuleException;
use OrangeHRM\Attendance\Service\Form\FormGrading;
use OrangeHRM\Tests\Util\TestCase;

/**
 * How a quiz is scored.
 *
 * The grade is what HR shows as proof somebody was trained, so it has to be
 * boringly predictable: all or nothing per question, a scale never counts, and
 * a written answer waits for a person instead of being guessed at.
 *
 * @group Attendance
 * @group Forms
 */
class FormGradingTest extends TestCase
{
    private function choice(int $id, string $type, array $correctIds, float $points = 1.0): array
    {
        $options = [];
        foreach ([1, 2, 3] as $n) {
            $optionId = $id * 10 + $n;
            $options[] = ['id' => $optionId, 'label' => "O{$n}", 'isCorrect' => in_array($optionId, $correctIds, true)];
        }
        return ['id' => $id, 'type' => $type, 'points' => $points, 'correctYesNo' => null, 'options' => $options];
    }

    private function plain(int $id, string $type, float $points = 1.0, ?bool $correctYesNo = null): array
    {
        return ['id' => $id, 'type' => $type, 'points' => $points, 'correctYesNo' => $correctYesNo, 'options' => []];
    }

    public function testASurveyIsRecordedWithoutAScore(): void
    {
        $result = FormGrading::grade('SURVEY', [$this->choice(1, 'SINGLE', [11])], [1 => ['optionIds' => [11]]]);

        $this->assertSame('RECORDED', $result['status']);
        $this->assertNull($result['scorePoints']);
        $this->assertNull($result['maxPoints']);
    }

    public function testTheRightSingleChoiceScoresItsPoints(): void
    {
        $result = FormGrading::grade('QUIZ', [$this->choice(1, 'SINGLE', [12], 2.0)], [1 => ['optionIds' => [12]]]);

        $this->assertSame(2.0, $result['scorePoints']);
        $this->assertSame(2.0, $result['maxPoints']);
        $this->assertSame('GRADED', $result['status']);
        $this->assertSame([1 => 2.0], $result['awarded']);
    }

    public function testTheWrongSingleChoiceScoresNothing(): void
    {
        $result = FormGrading::grade('QUIZ', [$this->choice(1, 'SINGLE', [12], 2.0)], [1 => ['optionIds' => [11]]]);

        $this->assertSame(0.0, $result['scorePoints']);
    }

    public function testMultipleChoiceScoresOnlyTheExactSet(): void
    {
        $items = [$this->choice(1, 'MULTIPLE', [11, 13])];
        $score = fn (array $ticked) => FormGrading::grade('QUIZ', $items, [1 => ['optionIds' => $ticked]])['scorePoints'];

        $this->assertSame(1.0, $score([11, 13]));
        $this->assertSame(1.0, $score([13, 11]));
        $this->assertSame(1.0, $score(['11', '13']));
        $this->assertSame(0.0, $score([11]));
        $this->assertSame(0.0, $score([11, 12, 13]));
    }

    public function testYesNoScoresTheRightAnswer(): void
    {
        $items = [$this->plain(1, 'YES_NO', 1.0, false)];

        $this->assertSame(1.0, FormGrading::grade('QUIZ', $items, [1 => ['yesNo' => false]])['scorePoints']);
        $this->assertSame(0.0, FormGrading::grade('QUIZ', $items, [1 => ['yesNo' => true]])['scorePoints']);
    }

    public function testAnUnansweredOptionalQuestionScoresZero(): void
    {
        $result = FormGrading::grade('QUIZ', [$this->choice(1, 'SINGLE', [11]), $this->choice(2, 'SINGLE', [21])], [
            1 => ['optionIds' => [11]],
        ]);

        $this->assertSame(1.0, $result['scorePoints']);
        $this->assertSame(2.0, $result['maxPoints']);
    }

    public function testScaleNeverCounts(): void
    {
        $result = FormGrading::grade('QUIZ', [$this->choice(1, 'SINGLE', [11]), $this->plain(2, 'SCALE', 5.0)], [
            1 => ['optionIds' => [11]],
            2 => ['scale' => 5],
        ]);

        $this->assertSame(1.0, $result['maxPoints']);
        $this->assertArrayNotHasKey(2, $result['awarded']);
    }

    public function testATextAnswerLeavesTheQuizPendingReview(): void
    {
        $result = FormGrading::grade('QUIZ', [$this->choice(1, 'SINGLE', [11]), $this->plain(2, 'SHORT_TEXT', 3.0)], [
            1 => ['optionIds' => [11]],
            2 => ['text' => 'Isolar a area e chamar o gerente'],
        ]);

        $this->assertSame('PENDING_REVIEW', $result['status']);
        $this->assertNull($result['awarded'][2]);
        $this->assertSame(4.0, $result['maxPoints']);
        $this->assertSame(1.0, $result['scorePoints']);
    }

    /**
     * Nothing written, nothing to read: waiting for a review would hold the
     * grade back for no reason.
     */
    public function testAnUnansweredTextIsZeroNotPending(): void
    {
        $result = FormGrading::grade('QUIZ', [$this->plain(1, 'LONG_TEXT', 2.0)], [1 => ['text' => '   ']]);

        $this->assertSame('GRADED', $result['status']);
        $this->assertSame(0.0, $result['awarded'][1]);
    }

    public function testAQuizWithNothingScoredIsRecorded(): void
    {
        $result = FormGrading::grade('QUIZ', [$this->plain(1, 'SCALE')], [1 => ['scale' => 3]]);

        $this->assertSame('RECORDED', $result['status']);
        $this->assertNull($result['scorePoints']);
    }

    public function testTotalsAfterReview(): void
    {
        $this->assertSame(
            ['scorePoints' => 3.5, 'status' => 'GRADED'],
            FormGrading::totals([1 => 2.0, 2 => 1.5], 5.0)
        );
        $this->assertSame(
            ['scorePoints' => 2.0, 'status' => 'PENDING_REVIEW'],
            FormGrading::totals([1 => 2.0, 2 => null], 5.0)
        );
    }

    public function testPassMarkIsInclusive(): void
    {
        $this->assertTrue(FormGrading::passed(7.0, 10.0, 70));
        $this->assertFalse(FormGrading::passed(6.9, 10.0, 70));
        $this->assertTrue(FormGrading::passed(0.7, 1.0, 70));
        $this->assertTrue(FormGrading::passed(0.0, 3.0, 0));
    }

    public function testPercentRoundsToOneDecimal(): void
    {
        $this->assertSame(66.7, FormGrading::percent(2.0, 3.0));
        $this->assertSame(100.0, FormGrading::percent(3.0, 3.0));
    }

    public function testReviewPointsMustFitTheQuestion(): void
    {
        FormGrading::assertReviewPoints(3.0, 3.0, 2);
        FormGrading::assertReviewPoints(0.0, 3.0, 2);

        foreach ([4.0, -1.0] as $given) {
            try {
                FormGrading::assertReviewPoints($given, 3.0, 2);
                $this->fail("{$given} deveria ser recusado");
            } catch (FormRuleException $e) {
                $this->assertSame(2, $e->getItemPosition());
            }
        }
    }
}
