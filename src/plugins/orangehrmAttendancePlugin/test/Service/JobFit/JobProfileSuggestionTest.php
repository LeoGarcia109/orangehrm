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
use OrangeHRM\Attendance\Service\JobFit\JobProfileSuggestion;
use OrangeHRM\Tests\Util\TestCase;

/**
 * Ranges drawn from reference employees: mean plus and minus one sample SD,
 * snapped outwards to fives, never narrower than ten points.
 *
 * @group Attendance
 * @group JobFit
 */
class JobProfileSuggestionTest extends TestCase
{
    private function person(float $e): array
    {
        return [
            'BIG5' => ['E' => $e, 'A' => 50.0, 'C' => 50.0, 'N' => 50.0, 'O' => 50.0],
            'DISC' => ['D' => 50.0, 'I' => 50.0, 'S' => 50.0, 'C' => 50.0],
        ];
    }

    private function range(array $rows, string $instrument, string $factor): array
    {
        foreach ($rows as $row) {
            if ($row['instrument'] === $instrument && $row['factor'] === $factor) {
                return [$row['min'], $row['max']];
            }
        }
        $this->fail("$instrument $factor missing");
    }

    public function testAllNineFactorsInCatalogueOrder(): void
    {
        $rows = JobProfileSuggestion::suggest([$this->person(60)]);
        $this->assertSame(
            ['BIG5E', 'BIG5A', 'BIG5C', 'BIG5N', 'BIG5O', 'DISCD', 'DISCI', 'DISCS', 'DISCC'],
            array_map(static fn (array $r) => $r['instrument'] . $r['factor'], $rows)
        );
    }

    public function testOneReferenceIsPlusMinusTen(): void
    {
        $this->assertSame([50, 75], $this->range(JobProfileSuggestion::suggest([$this->person(62)]), 'BIG5', 'E'));
    }

    public function testMeanPlusMinusSampleDeviation(): void
    {
        $rows = JobProfileSuggestion::suggest([$this->person(60), $this->person(70), $this->person(80)]);
        $this->assertSame([60, 80], $this->range($rows, 'BIG5', 'E'));
    }

    public function testNeverNarrowerThanTenNorPastTheScale(): void
    {
        $rows = JobProfileSuggestion::suggest([$this->person(98), $this->person(98), $this->person(98)]);
        $this->assertSame([90, 100], $this->range($rows, 'BIG5', 'E'));
        $this->assertSame([45, 55], $this->range($rows, 'DISC', 'D'));
    }

    public function testNeedsOneToTwentyReferences(): void
    {
        $this->expectException(JobFitRuleException::class);
        JobProfileSuggestion::suggest([]);
    }

    public function testTooManyReferences(): void
    {
        $this->expectException(JobFitRuleException::class);
        JobProfileSuggestion::suggest(array_fill(0, 21, $this->person(50)));
    }
}
