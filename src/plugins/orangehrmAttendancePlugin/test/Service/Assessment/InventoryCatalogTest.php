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

use OrangeHRM\Attendance\Service\Assessment\InventoryCatalog;
use OrangeHRM\Tests\Util\TestCase;

/**
 * The item banks. They are fixed and versioned on purpose: a validated
 * statement that somebody "improves" stops measuring what it measured, and
 * old results stop being comparable with new ones.
 *
 * @group Attendance
 * @group Assessment
 */
class InventoryCatalogTest extends TestCase
{
    public function testTheBigFiveHasTheFiftyIpipMarkers(): void
    {
        $items = InventoryCatalog::items('BIG5');

        $this->assertCount(50, $items);
        foreach (['E', 'A', 'C', 'N', 'O'] as $factor) {
            $this->assertCount(10, array_filter($items, fn ($i) => $i['factor'] === $factor), $factor);
        }
    }

    /**
     * The reverse-keyed items per factor in Goldberg's 50-item key. Getting one
     * wrong silently flips part of a score.
     */
    public function testReverseKeyingFollowsTheOfficialKey(): void
    {
        $reversed = [];
        foreach (InventoryCatalog::items('BIG5') as $item) {
            $reversed[$item['factor']] = ($reversed[$item['factor']] ?? 0) + ($item['reverse'] ? 1 : 0);
        }

        $this->assertSame(['E' => 5, 'A' => 4, 'C' => 4, 'N' => 8, 'O' => 3], $reversed);
    }

    public function testDiscHasSixStatementsPerStyle(): void
    {
        $items = InventoryCatalog::items('DISC');

        $this->assertCount(24, $items);
        foreach (['D', 'I', 'S', 'C'] as $factor) {
            $this->assertCount(6, array_filter($items, fn ($i) => $i['factor'] === $factor), $factor);
        }
        $this->assertEmpty(array_filter($items, fn ($i) => $i['reverse']));
    }

    public function testCodesAreUniqueAndTextsFilled(): void
    {
        $all = array_merge(InventoryCatalog::items('BIG5'), InventoryCatalog::items('DISC'));
        $codes = array_column($all, 'code');

        $this->assertSame(count($codes), count(array_unique($codes)));
        foreach ($all as $item) {
            $this->assertNotSame('', trim($item['text']), $item['code']);
            $this->assertSame($item, InventoryCatalog::item($item['code']));
        }
        $this->assertNull(InventoryCatalog::item('B5-X99'));
    }

    public function testTheSequenceCoversEveryItemOnceAndIsStable(): void
    {
        $sequence = InventoryCatalog::sequence(['BIG5', 'DISC']);

        $this->assertCount(74, $sequence);
        $this->assertSame(74, count(array_unique($sequence)));
        $this->assertSame($sequence, InventoryCatalog::sequence(['DISC', 'BIG5']));
    }

    /**
     * Items of the same factor are not bunched together, which invites
     * answering by block instead of statement by statement.
     */
    public function testTheSequenceInterleavesFactors(): void
    {
        $sequence = InventoryCatalog::sequence(['BIG5', 'DISC']);
        for ($i = 1, $n = count($sequence); $i < $n; $i++) {
            $this->assertNotSame(
                InventoryCatalog::item($sequence[$i - 1])['factor'] . InventoryCatalog::item($sequence[$i - 1])['instrument'],
                InventoryCatalog::item($sequence[$i])['factor'] . InventoryCatalog::item($sequence[$i])['instrument'],
                "posicoes {$i}"
            );
        }
    }

    public function testASingleInstrumentSequence(): void
    {
        $this->assertCount(24, InventoryCatalog::sequence(['DISC']));
    }

    public function testPagesOfTen(): void
    {
        $pages = InventoryCatalog::pages(['BIG5', 'DISC']);

        $this->assertCount(8, $pages);
        $this->assertCount(10, $pages[0]);
        $this->assertCount(4, $pages[7]);
    }

    public function testVersionsAndFactors(): void
    {
        $this->assertSame('IPIP50-PT-v1', InventoryCatalog::version('BIG5'));
        $this->assertSame('DISC-HRR-v1', InventoryCatalog::version('DISC'));
        $this->assertSame(['E', 'A', 'C', 'N', 'O'], InventoryCatalog::factors('BIG5'));
        $this->assertSame(['D', 'I', 'S', 'C'], InventoryCatalog::factors('DISC'));
    }
}
