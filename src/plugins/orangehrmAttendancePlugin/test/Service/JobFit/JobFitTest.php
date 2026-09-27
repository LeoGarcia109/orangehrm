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

namespace OrangeHRM\Tests\Attendance\Service\JobFit;

use OrangeHRM\Attendance\Service\JobFit\JobFit;
use OrangeHRM\Tests\Util\TestCase;

/**
 * How close a person is to a job title's profile: the range decides, not the
 * size of the score, and the blocks and flags follow the spec to the decimal.
 *
 * @group Attendance
 * @group JobFit
 */
class JobFitTest extends TestCase
{
    private function profile(int $behaviorWeight = 50, bool $competencies = true): array
    {
        return [
            'behaviorWeight' => $behaviorWeight,
            'factors' => [
                ['instrument' => 'BIG5', 'factor' => 'C', 'min' => 60, 'max' => 90, 'weight' => 2],
                ['instrument' => 'BIG5', 'factor' => 'E', 'min' => 50, 'max' => 85, 'weight' => 1],
                ['instrument' => 'BIG5', 'factor' => 'O', 'min' => 0, 'max' => 100, 'weight' => 0],
            ],
            'competencies' => $competencies ? [
                ['id' => 1, 'name' => 'Atendimento', 'weight' => 2, 'minLevel' => 4],
                ['id' => 2, 'name' => 'Caixa', 'weight' => 1, 'minLevel' => 3],
            ] : [],
        ];
    }

    private function scores(): array
    {
        return ['BIG5' => ['C' => 50.0, 'E' => 70.0, 'O' => 10.0]];
    }

    public function testDistanceIsToTheNearestEdge(): void
    {
        $this->assertSame(0.0, JobFit::distance(70, 60, 90));
        $this->assertSame(10.0, JobFit::distance(50, 60, 90));
        $this->assertSame(5.0, JobFit::distance(95, 60, 90));
    }

    public function testFactorFitFallsTwoAndAHalfPerPoint(): void
    {
        $this->assertSame(100.0, JobFit::factorFit(75, 60, 90));
        $this->assertSame(75.0, JobFit::factorFit(50, 60, 90));
        $this->assertSame(0.0, JobFit::factorFit(20, 60, 90));
        $this->assertSame(0.0, JobFit::factorFit(5, 60, 90));
    }

    public function testColours(): void
    {
        $this->assertSame(JobFit::IN, JobFit::color(0));
        $this->assertSame(JobFit::NEAR, JobFit::color(10));
        $this->assertSame(JobFit::FAR, JobFit::color(10.5));
    }

    public function testRatingFit(): void
    {
        $this->assertSame(0.0, JobFit::ratingFit(1));
        $this->assertSame(50.0, JobFit::ratingFit(3));
        $this->assertSame(100.0, JobFit::ratingFit(5));
    }

    public function testBehaviourOnlyWhileNoCompetencyIsRated(): void
    {
        $result = JobFit::evaluate($this->profile(), $this->scores(), []);

        $this->assertSame(83.3, $result['behavior']);
        $this->assertNull($result['competency']);
        $this->assertSame(83.3, $result['overall']);
        $this->assertTrue($result['partial']);
        $this->assertSame([JobFit::ALERT_FACTOR], $result['alerts']);

        $byFactor = array_column($result['factors'], null, 'factor');
        $this->assertSame(75.0, $byFactor['C']['fit']);
        $this->assertSame(JobFit::NEAR, $byFactor['C']['color']);
        $this->assertSame(100.0, $byFactor['E']['fit']);
        $this->assertArrayHasKey('O', $byFactor, 'an ignored factor is still shown');
    }

    public function testCompetenciesJoinWithTheJobsWeight(): void
    {
        $result = JobFit::evaluate($this->profile(), $this->scores(), [1 => 3, 2 => 5]);

        $this->assertSame(66.7, $result['competency']);
        $this->assertSame(75.0, $result['overall']);
        $this->assertFalse($result['partial']);
        $this->assertSame([JobFit::ALERT_FACTOR, JobFit::ALERT_COMPETENCY], $result['alerts']);

        $byId = array_column($result['competencies'], null, 'id');
        $this->assertTrue($byId[1]['belowMin']);
        $this->assertFalse($byId[2]['belowMin']);
    }

    public function testSomeRatingsMissingIsPartial(): void
    {
        $result = JobFit::evaluate($this->profile(), $this->scores(), [2 => 5]);

        $this->assertSame(100.0, $result['competency']);
        $this->assertTrue($result['partial']);
        $this->assertSame([JobFit::ALERT_FACTOR], $result['alerts'], 'no rating, no competency alert');
    }

    public function testAllBehaviourWeight(): void
    {
        $result = JobFit::evaluate($this->profile(100), $this->scores(), [1 => 1, 2 => 1]);
        $this->assertSame($result['behavior'], $result['overall']);
    }

    public function testJobWithoutCompetenciesIsNeverPartial(): void
    {
        $result = JobFit::evaluate($this->profile(50, false), $this->scores(), []);
        $this->assertFalse($result['partial']);
        $this->assertSame(83.3, $result['overall']);
    }

    public function testFactorWithoutScoreIsLeftOut(): void
    {
        $result = JobFit::evaluate($this->profile(50, false), ['BIG5' => ['E' => 70.0]], []);
        $this->assertSame(['E'], array_column($result['factors'], 'factor'));
        $this->assertSame(100.0, $result['behavior']);
        $this->assertSame([], $result['alerts']);
    }

    public function testRankingByFitThenName(): void
    {
        $ranked = JobFit::rank([
            ['name' => 'Bruno', 'overall' => 70.0],
            ['name' => 'carla', 'overall' => 91.2],
            ['name' => 'Ana', 'overall' => 70.0],
        ]);
        $this->assertSame(['carla', 'Ana', 'Bruno'], array_column($ranked, 'name'));
    }
}
