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

namespace OrangeHRM\Tests\Attendance\Service\Form;

use OrangeHRM\Attendance\Service\Form\FormFillSerializer;
use OrangeHRM\Tests\Util\TestCase;

/**
 * What the employee's browser receives to answer a form.
 *
 * Anything sent can be read in the browser's inspector, so the answer key
 * must never be in it -- grading happens only on the server.
 *
 * @group Attendance
 * @group Forms
 */
class FormFillSerializerTest extends TestCase
{
    private function definition(string $kind): array
    {
        return [
            'kind' => $kind,
            'anonymous' => false,
            'passPercent' => 70,
            'scope' => 'NETWORK',
            'subunitId' => null,
            'employeeId' => null,
            'dueAt' => null,
            'isTemplate' => false,
            'items' => [
                ['id' => 1, 'type' => 'CONTENT', 'prompt' => 'Leia', 'helpText' => 'texto', 'required' => false,
                    'points' => 0.0, 'imageId' => 5, 'youtubeId' => 'dQw4w9WgXcQ', 'correctYesNo' => null, 'options' => []],
                ['id' => 2, 'type' => 'SINGLE', 'prompt' => 'Extintor?', 'helpText' => null, 'required' => true,
                    'points' => 2.0, 'imageId' => null, 'youtubeId' => null, 'correctYesNo' => null, 'options' => [
                        ['id' => 21, 'label' => 'Po quimico', 'isCorrect' => true],
                        ['id' => 22, 'label' => 'Agua', 'isCorrect' => false],
                    ]],
                ['id' => 3, 'type' => 'YES_NO', 'prompt' => 'Completar?', 'helpText' => null, 'required' => true,
                    'points' => 1.0, 'imageId' => null, 'youtubeId' => null, 'correctYesNo' => false, 'options' => []],
                ['id' => 4, 'type' => 'SCALE', 'prompt' => 'Nota', 'helpText' => null, 'required' => false,
                    'points' => 0.0, 'imageId' => null, 'youtubeId' => null, 'correctYesNo' => null, 'options' => []],
            ],
        ];
    }

    public function testTheAnswerKeyNeverLeavesTheServer(): void
    {
        $json = json_encode(FormFillSerializer::forFilling($this->definition('QUIZ')));

        $this->assertStringNotContainsString('isCorrect', $json);
        $this->assertStringNotContainsString('correctYesNo', $json);
        $this->assertStringNotContainsString('passPercent', $json);
    }

    public function testOptionsKeepWhatIsNeededToAnswer(): void
    {
        $filled = FormFillSerializer::forFilling($this->definition('QUIZ'));

        $this->assertSame(
            [['id' => 21, 'label' => 'Po quimico'], ['id' => 22, 'label' => 'Agua']],
            $filled['items'][1]['options']
        );
        $this->assertSame(5, $filled['items'][0]['imageId']);
        $this->assertSame('dQw4w9WgXcQ', $filled['items'][0]['youtubeId']);
    }

    public function testPointsAreShownOnScoredQuizQuestions(): void
    {
        $items = FormFillSerializer::forFilling($this->definition('QUIZ'))['items'];

        $this->assertSame(2.0, $items[1]['points']);
        $this->assertArrayNotHasKey('points', $items[0]);
        $this->assertArrayNotHasKey('points', $items[3]);
    }

    public function testSurveysCarryNoPoints(): void
    {
        foreach (FormFillSerializer::forFilling($this->definition('SURVEY'))['items'] as $item) {
            $this->assertArrayNotHasKey('points', $item);
        }
    }
}
