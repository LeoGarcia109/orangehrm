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

use OrangeHRM\Attendance\Exception\JobFitRuleException;
use OrangeHRM\Attendance\Service\JobFit\JobProfileRules;
use OrangeHRM\Tests\Util\TestCase;

/**
 * What a job title's profile may hold, and who may be compared.
 *
 * @group Attendance
 * @group JobFit
 */
class JobProfileRulesTest extends TestCase
{
    private function valid(): array
    {
        $profile = JobProfileRules::defaultProfile();
        $profile['factors'][2]['min'] = 60;
        $profile['factors'][2]['max'] = 90;
        $profile['factors'][2]['weight'] = 2;
        $profile['competencies'] = [
            ['name' => ' Atendimento ', 'weight' => 2, 'minLevel' => 4],
            ['id' => 7, 'name' => 'Caixa', 'weight' => 1, 'minLevel' => 3],
        ];
        return $profile;
    }

    private function rejects(array $profile): void
    {
        try {
            JobProfileRules::normalizeProfile($profile);
            $this->fail('should be rejected');
        } catch (JobFitRuleException $e) {
            $this->assertNotSame('', $e->getMessage());
        }
    }

    public function testDefaultProfileIsOpenAndDesirable(): void
    {
        $profile = JobProfileRules::defaultProfile();
        $this->assertSame(50, $profile['behaviorWeight']);
        $this->assertCount(9, $profile['factors']);
        foreach ($profile['factors'] as $factor) {
            $this->assertSame([0, 100, 1], [$factor['min'], $factor['max'], $factor['weight']]);
        }
        $this->assertSame([], $profile['competencies']);
    }

    public function testValidProfileIsNormalized(): void
    {
        $profile = JobProfileRules::normalizeProfile($this->valid());
        $this->assertSame('Atendimento', $profile['competencies'][0]['name']);
        $this->assertNull($profile['competencies'][0]['id']);
        $this->assertSame(7, $profile['competencies'][1]['id']);
        $this->assertSame([0, 1], array_column($profile['competencies'], 'sortOrder'));
        $this->assertSame(60, $profile['factors'][2]['min']);
    }

    public function testNumericStringsFromTheBrowserAreAccepted(): void
    {
        $payload = $this->valid();
        $payload['behaviorWeight'] = '70';
        $payload['factors'][0]['min'] = '10';
        $this->assertSame(70, JobProfileRules::normalizeProfile($payload)['behaviorWeight']);
    }

    public function testRejections(): void
    {
        $p = $this->valid();
        array_pop($p['factors']);
        $this->rejects($p);

        $p = $this->valid();
        $p['factors'][0]['min'] = 12;
        $this->rejects($p);

        $p = $this->valid();
        $p['factors'][0]['min'] = 80;
        $p['factors'][0]['max'] = 40;
        $this->rejects($p);

        $p = $this->valid();
        foreach ($p['factors'] as $i => $f) {
            $p['factors'][$i]['weight'] = 0;
        }
        $this->rejects($p);

        $p = $this->valid();
        $p['factors'][0]['weight'] = 3;
        $this->rejects($p);

        $p = $this->valid();
        $p['behaviorWeight'] = 101;
        $this->rejects($p);

        $p = $this->valid();
        $p['competencies'][0]['name'] = '  ';
        $this->rejects($p);

        $p = $this->valid();
        $p['competencies'][1]['name'] = 'atendimento';
        $this->rejects($p);

        $p = $this->valid();
        $p['competencies'][0]['minLevel'] = 6;
        $this->rejects($p);

        $p = $this->valid();
        $p['competencies'][0]['weight'] = 0;
        $this->rejects($p);

        $p = $this->valid();
        $p['competencies'] = array_map(
            static fn (int $i) => ['name' => "C$i", 'weight' => 1, 'minLevel' => 3],
            range(1, 31)
        );
        $this->rejects($p);
    }

    public function testSubjects(): void
    {
        $this->assertSame(
            [['type' => 'c', 'id' => 1], ['type' => 'e', 'id' => 22]],
            JobProfileRules::parseSubjects('c1, e22')
        );
        foreach (['', 'x1', 'c0', 'c1,c1', implode(',', array_map(static fn ($i) => "c$i", range(1, 21)))] as $bad) {
            try {
                JobProfileRules::parseSubjects($bad);
                $this->fail("accepted '$bad'");
            } catch (JobFitRuleException $e) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testIds(): void
    {
        $this->assertSame([3, 9], JobProfileRules::parseIds('3,9'));
        $this->expectException(JobFitRuleException::class);
        JobProfileRules::parseIds('3,x');
    }
}
