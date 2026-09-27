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

use OrangeHRM\Attendance\Service\Form\FormResultAggregator;
use OrangeHRM\Tests\Util\TestCase;

/**
 * The per-question picture HR gets: how the options split, how often the
 * quiz question was right, the average on a scale, and the written answers --
 * with nothing in it that points back at who wrote what.
 *
 * @group Attendance
 * @group Forms
 */
class FormResultAggregatorTest extends TestCase
{
    private function row(string $submission, int $itemId, array $values): array
    {
        return $values + [
            'submissionId' => $submission,
            'itemId' => $itemId,
            'optionId' => null,
            'text' => null,
            'scale' => null,
            'yesNo' => null,
            'points' => null,
        ];
    }

    private function single(int $id, string $type = 'SINGLE'): array
    {
        return ['id' => $id, 'type' => $type, 'prompt' => "Q{$id}", 'options' => [
            ['id' => $id * 10 + 1, 'label' => 'A', 'isCorrect' => true],
            ['id' => $id * 10 + 2, 'label' => 'B', 'isCorrect' => false],
        ]];
    }

    private function plain(int $id, string $type): array
    {
        return ['id' => $id, 'type' => $type, 'prompt' => "Q{$id}", 'options' => []];
    }

    public function testOptionCountsAndPercentages(): void
    {
        $rows = [
            $this->row('s1', 1, ['optionId' => 11, 'points' => 1.0]),
            $this->row('s2', 1, ['optionId' => 11, 'points' => 1.0]),
            $this->row('s3', 1, ['optionId' => 11, 'points' => 1.0]),
            $this->row('s4', 1, ['optionId' => 12, 'points' => 0.0]),
        ];

        [$result] = FormResultAggregator::perItem('QUIZ', [$this->single(1)], $rows, 4);

        $this->assertSame(4, $result['answered']);
        $this->assertSame(3, $result['options'][0]['count']);
        $this->assertSame(75.0, $result['options'][0]['percent']);
        $this->assertTrue($result['options'][0]['isCorrect']);
        $this->assertSame(25.0, $result['options'][1]['percent']);
        $this->assertSame(75.0, $result['correctPercent']);
    }

    public function testMultipleChoiceCountsEveryTickedOption(): void
    {
        $rows = [
            $this->row('s1', 1, ['optionId' => 11, 'points' => 1.0]),
            $this->row('s1', 1, ['optionId' => 12]),
            $this->row('s2', 1, ['optionId' => 11, 'points' => 0.0]),
        ];

        [$result] = FormResultAggregator::perItem('QUIZ', [$this->single(1, 'MULTIPLE')], $rows, 2);

        $this->assertSame(2, $result['answered']);
        $this->assertSame(2, $result['options'][0]['count']);
        $this->assertSame(1, $result['options'][1]['count']);
        $this->assertSame(50.0, $result['correctPercent']);
    }

    /**
     * Somebody who skipped the question did not get it right: the rate is
     * over everybody who submitted, not only over who answered it.
     */
    public function testCorrectPercentCountsSkippedAsWrong(): void
    {
        $rows = [$this->row('s1', 1, ['optionId' => 11, 'points' => 1.0])];

        [$result] = FormResultAggregator::perItem('QUIZ', [$this->single(1)], $rows, 2);

        $this->assertSame(50.0, $result['correctPercent']);
    }

    public function testSurveysHaveNoCorrectPercent(): void
    {
        $rows = [$this->row('s1', 1, ['optionId' => 11])];

        [$result] = FormResultAggregator::perItem('SURVEY', [$this->single(1)], $rows, 1);

        $this->assertNull($result['correctPercent']);
        $this->assertFalse($result['options'][0]['isCorrect']);
    }

    public function testYesNoCounts(): void
    {
        $rows = [
            $this->row('s1', 1, ['yesNo' => true, 'points' => 0.0]),
            $this->row('s2', 1, ['yesNo' => false, 'points' => 1.0]),
            $this->row('s3', 1, ['yesNo' => false, 'points' => 1.0]),
        ];

        [$result] = FormResultAggregator::perItem('QUIZ', [$this->plain(1, 'YES_NO')], $rows, 3);

        $this->assertSame(1, $result['yes']);
        $this->assertSame(2, $result['no']);
        $this->assertSame(66.7, $result['correctPercent']);
    }

    public function testScaleAverageAndDistribution(): void
    {
        $rows = [];
        foreach ([5, 4, 4, 2] as $n => $scale) {
            $rows[] = $this->row("s{$n}", 1, ['scale' => $scale]);
        }

        [$result] = FormResultAggregator::perItem('SURVEY', [$this->plain(1, 'SCALE')], $rows, 4);

        $this->assertSame(3.8, $result['average']);
        $this->assertSame([1 => 0, 2 => 1, 3 => 0, 4 => 2, 5 => 1], $result['distribution']);
    }

    public function testAScaleNobodyAnsweredHasNoAverage(): void
    {
        [$result] = FormResultAggregator::perItem('SURVEY', [$this->plain(1, 'SCALE')], [], 2);

        $this->assertNull($result['average']);
        $this->assertSame(0, $result['answered']);
    }

    public function testTextsAreListedWithoutOrder(): void
    {
        $rows = [
            $this->row('s1', 1, ['text' => 'primeiro']),
            $this->row('s2', 1, ['text' => 'segundo']),
            $this->row('s3', 1, ['text' => 'terceiro']),
        ];

        [$result] = FormResultAggregator::perItem('SURVEY', [$this->plain(1, 'LONG_TEXT')], $rows, 3);

        $this->assertEqualsCanonicalizing(['primeiro', 'segundo', 'terceiro'], $result['texts']);
    }

    public function testAnAnonymousSurveyIsHiddenBelowThreeResponses(): void
    {
        $this->assertFalse(FormResultAggregator::mayShow(true, 2));
        $this->assertTrue(FormResultAggregator::mayShow(true, 3));
        $this->assertTrue(FormResultAggregator::mayShow(false, 1));
    }

    public function testContentBlocksAreSkipped(): void
    {
        $result = FormResultAggregator::perItem('SURVEY', [$this->plain(1, 'CONTENT'), $this->plain(2, 'SCALE')], [], 0);

        $this->assertCount(1, $result);
        $this->assertSame(2, $result[0]['itemId']);
    }
}
