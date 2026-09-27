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

namespace OrangeHRM\Attendance\Service\Form;

use OrangeHRM\Attendance\Exception\FormRuleException;

/**
 * BR: how a quiz is scored.
 *
 * All or nothing per question -- a multiple choice counts only when exactly
 * the right set is ticked -- a scale never counts, and a written answer waits
 * for somebody to read it instead of being guessed at.
 */
final class FormGrading
{
    /**
     * @param string $kind QUIZ or SURVEY
     * @param array $items the definition's items
     * @param array<int, array> $answers keyed by item id
     * @return array{scorePoints: ?float, maxPoints: ?float, status: string, awarded: array<int, ?float>}
     *     awarded holds only the scored questions; null means "waiting for review"
     */
    public static function grade(string $kind, array $items, array $answers): array
    {
        $recorded = [
            'scorePoints' => null,
            'maxPoints' => null,
            'status' => FormTypes::SUBMISSION_RECORDED,
            'awarded' => [],
        ];
        if ($kind !== FormTypes::KIND_QUIZ) {
            return $recorded;
        }

        $awarded = [];
        $max = 0.0;
        foreach ($items as $item) {
            if (!in_array($item['type'], FormTypes::SCORED_TYPES, true)) {
                continue;
            }
            $points = (float)$item['points'];
            $max += $points;
            $awarded[$item['id']] = self::scoreItem($item, $answers[$item['id']] ?? null, $points);
        }

        // A "quiz" made only of scales has nothing to grade: it is a survey.
        if ($max <= 0.0) {
            return $recorded;
        }

        return ['maxPoints' => $max, 'awarded' => $awarded] + self::totals($awarded, $max);
    }

    private static function scoreItem(array $item, ?array $answer, float $points): ?float
    {
        switch ($item['type']) {
            case FormTypes::TYPE_SINGLE:
            case FormTypes::TYPE_MULTIPLE:
                $correct = array_map('intval', array_column(
                    array_filter($item['options'], static fn (array $o) => !empty($o['isCorrect'])),
                    'id'
                ));
                $given = array_map('intval', $answer['optionIds'] ?? []);
                sort($correct);
                sort($given);
                return $given !== [] && $given === $correct ? $points : 0.0;

            case FormTypes::TYPE_YES_NO:
                return isset($answer['yesNo']) && $answer['yesNo'] === $item['correctYesNo'] ? $points : 0.0;

            default:
                // Somebody has to read it -- unless nothing was written.
                return trim((string)($answer['text'] ?? '')) === '' ? 0.0 : null;
        }
    }

    /**
     * @param array<int, ?float> $awarded
     * @return array{scorePoints: float, status: string}
     */
    public static function totals(array $awarded, float $maxPoints): array
    {
        return [
            'scorePoints' => (float)array_sum(array_filter($awarded, static fn ($p) => $p !== null)),
            'status' => in_array(null, $awarded, true)
                ? FormTypes::SUBMISSION_PENDING_REVIEW
                : FormTypes::SUBMISSION_GRADED,
        ];
    }

    public static function percent(float $score, float $max): float
    {
        return $max <= 0.0 ? 0.0 : round($score / $max * 100, 1);
    }

    public static function passed(float $score, float $max, int $passPercent): bool
    {
        // Compared in hundredths of a percent, so 7/10 against 70% is not lost to float error.
        return $max > 0.0 && (int)round($score / $max * 10000) >= $passPercent * 100;
    }

    /**
     * @param int $position 1-based block, for the error
     * @throws FormRuleException
     */
    public static function assertReviewPoints(float $given, float $max, int $position): void
    {
        if ($given < 0 || $given > $max) {
            throw FormRuleException::at($position, "de 0 a {$max} pontos.");
        }
    }
}
