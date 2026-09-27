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
 * BR: the form as the employee's browser receives it.
 *
 * Whatever is sent can be read in the browser's inspector, so the answer key
 * is simply never sent: no isCorrect, no correctYesNo, no pass mark. Grading
 * happens on the server only.
 */
final class FormFillSerializer
{
    public static function forFilling(array $definition): array
    {
        $quiz = $definition['kind'] === FormTypes::KIND_QUIZ;

        return [
            'kind' => $definition['kind'],
            'anonymous' => (bool)$definition['anonymous'],
            'items' => array_map(static function (array $item) use ($quiz) {
                $out = [
                    'id' => $item['id'],
                    'type' => $item['type'],
                    'prompt' => $item['prompt'],
                    'helpText' => $item['helpText'],
                    'required' => (bool)$item['required'],
                    'imageId' => $item['imageId'],
                    'youtubeId' => $item['youtubeId'],
                    'options' => array_map(
                        static fn (array $option) => ['id' => $option['id'], 'label' => $option['label']],
                        $item['options']
                    ),
                ];
                if ($quiz && in_array($item['type'], FormTypes::SCORED_TYPES, true)) {
                    $out['points'] = (float)$item['points'];
                }
                return $out;
            }, array_values($definition['items'])),
        ];
    }
}
