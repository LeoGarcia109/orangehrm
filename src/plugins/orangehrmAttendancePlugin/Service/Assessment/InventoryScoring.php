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

namespace OrangeHRM\Attendance\Service\Assessment;

use OrangeHRM\Attendance\Exception\AssessmentRuleException;

/**
 * BR: behavioural answers turned into factor scores.
 *
 * Each factor sums its statements on the 1-5 scale, reversed statements
 * counting as 6 - answer, and the sum is laid on 0-100. There are no
 * population norms behind these numbers: 0-100 is the person's position on
 * the scale itself, not a percentile, and the bands are descriptive.
 */
final class InventoryScoring
{
    public const BAND_LOW = 'LOW';
    public const BAND_MID = 'MID';
    public const BAND_HIGH = 'HIGH';

    /**
     * @param array<string, int> $answers item code => 1..5 (other instruments' codes are ignored)
     * @return array<string, array{raw: int, score: float}> factor => result, in the model's order
     * @throws AssessmentRuleException
     */
    public static function score(string $instrument, array $answers): array
    {
        if (!in_array($instrument, InventoryCatalog::INSTRUMENTS, true)) {
            throw AssessmentRuleException::because('Inventario desconhecido.');
        }

        $raw = array_fill_keys(InventoryCatalog::factors($instrument), 0);
        $count = array_fill_keys(InventoryCatalog::factors($instrument), 0);
        foreach (InventoryCatalog::items($instrument) as $item) {
            $value = $answers[$item['code']] ?? null;
            if ($value === null) {
                throw AssessmentRuleException::because('Ha frases sem resposta.');
            }
            if (!is_int($value) || $value < 1 || $value > 5) {
                throw AssessmentRuleException::because('Resposta fora da escala de 1 a 5.');
            }
            $raw[$item['factor']] += $item['reverse'] ? 6 - $value : $value;
            $count[$item['factor']]++;
        }

        $result = [];
        foreach ($raw as $factor => $sum) {
            $min = $count[$factor];
            $max = 5 * $count[$factor];
            $result[$factor] = [
                'raw' => $sum,
                'score' => round(($sum - $min) / ($max - $min) * 100, 1),
            ];
        }
        return $result;
    }

    /**
     * The two strongest DISC styles. Ties follow the model's own order
     * (D, I, S, C), so the same answers always name the same style.
     *
     * @param array<string, float> $scores factor => score
     * @return array{primary: string, secondary: string}
     */
    public static function discStyles(array $scores): array
    {
        $order = array_flip(InventoryCatalog::factors(InventoryCatalog::DISC));
        $factors = array_keys($scores);
        usort($factors, static function (string $a, string $b) use ($scores, $order) {
            return [$scores[$b], $order[$a]] <=> [$scores[$a], $order[$b]];
        });
        return ['primary' => $factors[0], 'secondary' => $factors[1]];
    }

    public static function band(float $score): string
    {
        if ($score <= 33.3) {
            return self::BAND_LOW;
        }
        return $score <= 66.6 ? self::BAND_MID : self::BAND_HIGH;
    }
}
