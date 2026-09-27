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

namespace OrangeHRM\Tests\Attendance\Service\Assessment;

use OrangeHRM\Attendance\Exception\AssessmentRuleException;
use OrangeHRM\Attendance\Service\Assessment\InventoryCatalog;
use OrangeHRM\Attendance\Service\Assessment\InventoryScoring;
use OrangeHRM\Tests\Util\TestCase;

/**
 * How answers become a profile. A reverse-keyed item scored the wrong way
 * round quietly turns part of a trait upside down, so the extremes are
 * checked against answers built from the key itself.
 *
 * @group Attendance
 * @group Assessment
 */
class InventoryScoringTest extends TestCase
{
    /**
     * Answers that express the most (or least) of every factor: 5 on the
     * straight items and 1 on the reversed ones, or the opposite.
     */
    private function extreme(string $instrument, bool $high): array
    {
        $answers = [];
        foreach (InventoryCatalog::items($instrument) as $item) {
            $agree = $high !== $item['reverse'];
            $answers[$item['code']] = $agree ? 5 : 1;
        }
        return $answers;
    }

    private function all(string $instrument, int $value): array
    {
        return array_fill_keys(array_column(InventoryCatalog::items($instrument), 'code'), $value);
    }

    public function testTheHighestExpressionOfEveryBigFiveFactorScoresOneHundred(): void
    {
        foreach (InventoryScoring::score('BIG5', $this->extreme('BIG5', true)) as $factor => $result) {
            $this->assertSame(50, $result['raw'], $factor);
            $this->assertSame(100.0, $result['score'], $factor);
        }
    }

    public function testTheLowestExpressionScoresZero(): void
    {
        foreach (InventoryScoring::score('BIG5', $this->extreme('BIG5', false)) as $factor => $result) {
            $this->assertSame(10, $result['raw'], $factor);
            $this->assertSame(0.0, $result['score'], $factor);
        }
    }

    /**
     * Agreeing with everything is not "high on everything": reversed items
     * pull each factor back towards the middle.
     */
    public function testAgreeingWithEverythingIsNotTheTop(): void
    {
        $scores = InventoryScoring::score('BIG5', $this->all('BIG5', 5));

        // E has 5 straight and 5 reversed: 5*5 + 5*1 = 30 -> 50.0
        $this->assertSame(30, $scores['E']['raw']);
        $this->assertSame(50.0, $scores['E']['score']);
        // N (emotional stability) has 2 straight and 8 reversed: 2*5 + 8*1 = 18 -> 20.0
        $this->assertSame(20.0, $scores['N']['score']);
    }

    public function testFactorsComeInTheirOrder(): void
    {
        $this->assertSame(['E', 'A', 'C', 'N', 'O'], array_keys(InventoryScoring::score('BIG5', $this->all('BIG5', 3))));
        $this->assertSame(['D', 'I', 'S', 'C'], array_keys(InventoryScoring::score('DISC', $this->all('DISC', 3))));
    }

    public function testDiscScale(): void
    {
        $answers = $this->all('DISC', 1);
        foreach (InventoryCatalog::items('DISC') as $item) {
            if ($item['factor'] === 'S') {
                $answers[$item['code']] = 5;
            }
        }
        $scores = InventoryScoring::score('DISC', $answers);

        $this->assertSame(['raw' => 30, 'score' => 100.0], $scores['S']);
        $this->assertSame(['raw' => 6, 'score' => 0.0], $scores['D']);
    }

    public function testScoresHaveOneDecimal(): void
    {
        $answers = $this->all('DISC', 3);
        $answers['DISC-D01'] = 4;

        $this->assertSame(54.2, InventoryScoring::score('DISC', $answers)['D']['score']);
    }

    public function testDiscStylesPickTheTwoHighest(): void
    {
        $this->assertSame(
            ['primary' => 'S', 'secondary' => 'C'],
            InventoryScoring::discStyles(['D' => 20.0, 'I' => 40.0, 'S' => 90.0, 'C' => 70.0])
        );
    }

    /**
     * A tie must not flip from one screen to the next.
     */
    public function testDiscTiesFollowTheModelOrder(): void
    {
        $this->assertSame(
            ['primary' => 'I', 'secondary' => 'C'],
            InventoryScoring::discStyles(['D' => 10.0, 'I' => 80.0, 'S' => 50.0, 'C' => 80.0])
        );
    }

    public function testBands(): void
    {
        $this->assertSame('LOW', InventoryScoring::band(0.0));
        $this->assertSame('LOW', InventoryScoring::band(33.3));
        $this->assertSame('MID', InventoryScoring::band(33.4));
        $this->assertSame('MID', InventoryScoring::band(66.6));
        $this->assertSame('HIGH', InventoryScoring::band(66.7));
        $this->assertSame('HIGH', InventoryScoring::band(100.0));
    }

    public function testEveryStatementMustBeAnswered(): void
    {
        $answers = $this->all('BIG5', 3);
        unset($answers['B5-O10']);

        $this->expectException(AssessmentRuleException::class);
        InventoryScoring::score('BIG5', $answers);
    }

    public function testValuesMustBeOneToFive(): void
    {
        $answers = $this->all('DISC', 3);
        $answers['DISC-I02'] = 6;

        $this->expectException(AssessmentRuleException::class);
        InventoryScoring::score('DISC', $answers);
    }

    public function testAnswersOfOtherInstrumentsAreIgnored(): void
    {
        $answers = $this->all('DISC', 3) + $this->all('BIG5', 2);

        $this->assertSame(50.0, InventoryScoring::score('DISC', $answers)['D']['score']);
    }

    public function testAnUnknownInstrumentIsRefused(): void
    {
        $this->expectException(AssessmentRuleException::class);
        InventoryScoring::score('ENNEAGRAM', []);
    }
}
