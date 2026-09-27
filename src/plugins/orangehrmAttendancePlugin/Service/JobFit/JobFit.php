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

/**
 * BR: how close a person's profile test is to a job title's profile.
 *
 * Every factor is on 0-100. Inside the job's range a factor is worth 100;
 * outside, it loses 2.5 per point of distance to the nearest edge, reaching 0
 * forty points away -- so being "more" is not better, being in range is.
 * Factors and competencies each make a weighted block, and the job title says
 * how much of the fit the behavioural block carries.
 */
final class JobFit
{
    public const WEIGHT_IGNORE = 0;
    public const WEIGHT_DESIRABLE = 1;
    public const WEIGHT_ESSENTIAL = 2;

    public const IN = 'IN';
    public const NEAR = 'NEAR';
    public const FAR = 'FAR';

    public const ALERT_FACTOR = 'ESSENTIAL_FACTOR';
    public const ALERT_COMPETENCY = 'ESSENTIAL_COMPETENCY';

    private const LOSS_PER_POINT = 2.5;
    private const NEAR_DISTANCE = 10;

    public static function distance(float $score, int $min, int $max): float
    {
        if ($score < $min) {
            return round($min - $score, 1);
        }
        return $score > $max ? round($score - $max, 1) : 0.0;
    }

    public static function factorFit(float $score, int $min, int $max): float
    {
        return round(max(0.0, 100 - self::LOSS_PER_POINT * self::distance($score, $min, $max)), 1);
    }

    public static function color(float $distance): string
    {
        if ($distance <= 0) {
            return self::IN;
        }
        return $distance <= self::NEAR_DISTANCE ? self::NEAR : self::FAR;
    }

    public static function ratingFit(int $rating): float
    {
        return round(($rating - 1) / 4 * 100, 1);
    }

    /**
     * @param array $profile ['behaviorWeight', 'factors' => [[instrument, factor, min, max, weight]],
     *                        'competencies' => [[id, name, weight, minLevel]]]
     * @param array $scores ['BIG5' => [factor => score], 'DISC' => [factor => score]]
     * @param array<int, int> $ratings competency id => 1..5
     */
    public static function evaluate(array $profile, array $scores, array $ratings): array
    {
        $alerts = [];

        $factors = [];
        $weighted = 0.0;
        $weights = 0;
        $essentialMissed = false;
        foreach ($profile['factors'] as $target) {
            $score = $scores[$target['instrument']][$target['factor']] ?? null;
            if ($score === null) {
                continue;
            }
            $score = (float)$score;
            $distance = self::distance($score, $target['min'], $target['max']);
            $fit = self::factorFit($score, $target['min'], $target['max']);
            $factors[] = [
                'instrument' => $target['instrument'],
                'factor' => $target['factor'],
                'score' => $score,
                'distance' => $distance,
                'fit' => $fit,
                'color' => self::color($distance),
            ];
            if ($target['weight'] > 0) {
                $weighted += $target['weight'] * $fit;
                $weights += $target['weight'];
            }
            if ($target['weight'] === self::WEIGHT_ESSENTIAL && $distance > 0) {
                $essentialMissed = true;
            }
        }
        $behavior = $weights > 0 ? $weighted / $weights : 0.0;
        if ($essentialMissed) {
            $alerts[] = self::ALERT_FACTOR;
        }

        $competencies = [];
        $weighted = 0.0;
        $weights = 0;
        $unrated = false;
        $belowMin = false;
        foreach ($profile['competencies'] as $competency) {
            $rating = $ratings[$competency['id']] ?? null;
            $row = ['id' => $competency['id'], 'rating' => $rating, 'fit' => null, 'belowMin' => false];
            if ($rating === null) {
                $unrated = true;
            } else {
                $row['fit'] = self::ratingFit($rating);
                $row['belowMin'] = $rating < $competency['minLevel'];
                $weighted += $competency['weight'] * $row['fit'];
                $weights += $competency['weight'];
                if ($row['belowMin'] && $competency['weight'] === self::WEIGHT_ESSENTIAL) {
                    $belowMin = true;
                }
            }
            $competencies[] = $row;
        }
        $competency = $weights > 0 ? $weighted / $weights : null;
        if ($belowMin) {
            $alerts[] = self::ALERT_COMPETENCY;
        }

        $share = $profile['behaviorWeight'] / 100;
        $overall = $competency === null ? $behavior : $share * $behavior + (1 - $share) * $competency;

        return [
            'factors' => $factors,
            'competencies' => $competencies,
            'behavior' => round($behavior, 1),
            'competency' => $competency === null ? null : round($competency, 1),
            'overall' => round($overall, 1),
            'partial' => $unrated,
            'alerts' => $alerts,
        ];
    }

    /**
     * Best fit first; a tie goes by name, so the order never flickers.
     *
     * @param array[] $rows each with 'name' and 'overall'
     */
    public static function rank(array $rows): array
    {
        usort($rows, static function (array $a, array $b): int {
            return [$b['overall'], mb_strtolower((string)$a['name'])]
                <=> [$a['overall'], mb_strtolower((string)$b['name'])];
        });
        return $rows;
    }
}
