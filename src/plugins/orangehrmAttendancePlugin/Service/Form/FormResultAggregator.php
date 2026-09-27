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

/**
 * BR: the per-question picture of a form's answers.
 *
 * Rows come in flat -- one per ticked option, one per text, scale or yes/no --
 * and nothing that leaves here points back at who wrote what: written answers
 * are shuffled, and an anonymous survey is not shown at all until it has
 * enough answers to hide in.
 */
final class FormResultAggregator
{
    /**
     * @param string $kind QUIZ or SURVEY
     * @param array $items the definition's items
     * @param array $rows [{submissionId, itemId, optionId, text, scale, yesNo, points}]
     * @param int $submissionCount everybody who submitted, whether they answered this one or not
     * @return array one entry per question, in form order
     */
    public static function perItem(string $kind, array $items, array $rows, int $submissionCount): array
    {
        $quiz = $kind === FormTypes::KIND_QUIZ;
        $byItem = [];
        foreach ($rows as $row) {
            $byItem[(int)$row['itemId']][] = $row;
        }

        $result = [];
        foreach ($items as $item) {
            if (!in_array($item['type'], FormTypes::QUESTION_TYPES, true)) {
                continue;
            }
            $itemRows = $byItem[(int)$item['id']] ?? [];
            $entry = [
                'itemId' => (int)$item['id'],
                'type' => $item['type'],
                'prompt' => $item['prompt'],
                'answered' => count(array_unique(array_column($itemRows, 'submissionId'))),
            ];

            switch ($item['type']) {
                case FormTypes::TYPE_SINGLE:
                case FormTypes::TYPE_MULTIPLE:
                    $entry += self::choices($item, $itemRows, $quiz, $submissionCount);
                    break;
                case FormTypes::TYPE_YES_NO:
                    $entry += [
                        'yes' => count(array_filter($itemRows, static fn ($r) => $r['yesNo'] === true)),
                        'no' => count(array_filter($itemRows, static fn ($r) => $r['yesNo'] === false)),
                        'correctPercent' => $quiz ? self::correctPercent($itemRows, $submissionCount) : null,
                    ];
                    break;
                case FormTypes::TYPE_SCALE:
                    $entry += self::scale($itemRows);
                    break;
                default:
                    $texts = array_values(array_filter(
                        array_map(static fn ($r) => $r['text'], $itemRows),
                        static fn ($t) => $t !== null && trim($t) !== ''
                    ));
                    // Kept in submission order, texts would line up with the list of who answered.
                    shuffle($texts);
                    $entry['texts'] = $texts;
            }
            $result[] = $entry;
        }
        return $result;
    }

    public static function mayShow(bool $anonymous, int $submissionCount): bool
    {
        return !$anonymous || $submissionCount >= FormTypes::ANONYMOUS_MIN_RESPONSES;
    }

    private static function choices(array $item, array $rows, bool $quiz, int $submissionCount): array
    {
        $options = [];
        foreach ($item['options'] as $option) {
            $count = count(array_filter($rows, static fn ($r) => (int)$r['optionId'] === (int)$option['id']));
            $options[] = [
                'id' => (int)$option['id'],
                'label' => $option['label'],
                'count' => $count,
                'percent' => self::share($count, $submissionCount),
                'isCorrect' => $quiz && !empty($option['isCorrect']),
            ];
        }
        return [
            'options' => $options,
            'correctPercent' => $quiz ? self::correctPercent($rows, $submissionCount) : null,
        ];
    }

    /**
     * A question was right for a submission when any of its rows carries
     * points -- a multiple choice keeps the points on its first row only.
     */
    private static function correctPercent(array $rows, int $submissionCount): float
    {
        $right = [];
        foreach ($rows as $row) {
            if ((float)$row['points'] > 0.0) {
                $right[$row['submissionId']] = true;
            }
        }
        return self::share(count($right), $submissionCount);
    }

    private static function scale(array $rows): array
    {
        $distribution = array_fill(FormTypes::SCALE_MIN, FormTypes::SCALE_MAX, 0);
        $values = [];
        foreach ($rows as $row) {
            if ($row['scale'] !== null) {
                $values[] = (int)$row['scale'];
                $distribution[(int)$row['scale']]++;
            }
        }
        return [
            'average' => $values === [] ? null : round(array_sum($values) / count($values), 1),
            'distribution' => $distribution,
        ];
    }

    private static function share(int $count, int $total): float
    {
        return $total === 0 ? 0.0 : round($count / $total * 100, 1);
    }
}
