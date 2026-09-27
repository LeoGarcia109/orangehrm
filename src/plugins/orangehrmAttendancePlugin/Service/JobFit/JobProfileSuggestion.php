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

namespace OrangeHRM\Attendance\Service\JobFit;

use OrangeHRM\Attendance\Exception\JobFitRuleException;
use OrangeHRM\Attendance\Service\Assessment\InventoryCatalog;

/**
 * BR: a job title's ranges drawn from reference employees -- for each factor,
 * the mean plus and minus one sample standard deviation, snapped outwards to
 * fives and never narrower than ten points. A single reference has no spread,
 * so it gets plus and minus ten. HR reviews the result before saving it.
 */
final class JobProfileSuggestion
{
    private const SINGLE_SPREAD = 10.0;
    private const MIN_WIDTH = 10;

    /**
     * @param array[] $scoreSets one per reference: ['BIG5' => [factor => score], 'DISC' => [...]]
     * @return array[] [instrument, factor, min, max] for the nine factors
     * @throws JobFitRuleException
     */
    public static function suggest(array $scoreSets): array
    {
        if ($scoreSets === [] || count($scoreSets) > JobProfileRules::MAX_PEOPLE) {
            throw JobFitRuleException::because('Escolha de 1 a 20 funcionarios de referencia.');
        }

        $rows = [];
        foreach (InventoryCatalog::INSTRUMENTS as $instrument) {
            foreach (InventoryCatalog::factors($instrument) as $factor) {
                $values = [];
                foreach ($scoreSets as $scores) {
                    if (isset($scores[$instrument][$factor])) {
                        $values[] = (float)$scores[$instrument][$factor];
                    }
                }
                [$min, $max] = $values === [] ? [0, 100] : self::range($values);
                $rows[] = ['instrument' => $instrument, 'factor' => $factor, 'min' => $min, 'max' => $max];
            }
        }
        return $rows;
    }

    /**
     * @param float[] $values
     * @return int[] [min, max]
     */
    private static function range(array $values): array
    {
        $n = count($values);
        $mean = array_sum($values) / $n;
        if ($n === 1) {
            $spread = self::SINGLE_SPREAD;
        } else {
            $squares = array_sum(array_map(static fn (float $v) => ($v - $mean) ** 2, $values));
            $spread = sqrt($squares / ($n - 1));
        }

        $min = max(0, (int)(floor(($mean - $spread) / 5) * 5));
        $max = min(100, (int)(ceil(($mean + $spread) / 5) * 5));
        while ($max - $min < self::MIN_WIDTH) {
            if ($min > 0) {
                $min -= 5;
            }
            if ($max - $min < self::MIN_WIDTH && $max < 100) {
                $max += 5;
            }
        }
        return [$min, $max];
    }
}
