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

use DateTime;
use OrangeHRM\Attendance\Exception\FormRuleException;

/**
 * BR: whether a form may go out.
 *
 * Publishing locks the form -- every answer is graded against it -- so what is
 * wrong has to be caught now, and named by block.
 */
final class FormPublication
{
    private const SCOPES = [
        FormTypes::SCOPE_NETWORK,
        FormTypes::SCOPE_SUBUNIT,
        FormTypes::SCOPE_EMPLOYEE,
    ];

    /**
     * @param array $definition see the plan's "definicao do formulario"
     * @throws FormRuleException
     */
    public static function assertPublishable(array $definition, DateTime $now): void
    {
        $quiz = $definition['kind'] === FormTypes::KIND_QUIZ;

        if (!empty($definition['isTemplate'])) {
            throw FormRuleException::form(
                'Um modelo nao e publicado: use "Usar modelo" para criar uma copia.'
            );
        }

        $questions = array_filter(
            $definition['items'],
            static fn (array $item) => in_array($item['type'], FormTypes::QUESTION_TYPES, true)
        );
        if ($questions === []) {
            throw FormRuleException::form('Inclua ao menos uma questao.');
        }

        if ($quiz && !empty($definition['anonymous'])) {
            throw FormRuleException::form(
                'Prova nao pode ser anonima: a nota precisa ficar ligada a pessoa.'
            );
        }

        self::assertAudience($definition);

        if ($quiz) {
            $pass = $definition['passPercent'];
            if ($pass === null || $pass < 0 || $pass > 100) {
                throw FormRuleException::form('Informe a nota minima (0 a 100%).');
            }
        }

        if ($definition['dueAt'] instanceof DateTime && $definition['dueAt'] < $now) {
            throw FormRuleException::form('O prazo ja passou.');
        }

        foreach (array_values($definition['items']) as $index => $item) {
            self::assertItem($item, $index + 1, $quiz);
        }
    }

    private static function assertAudience(array $definition): void
    {
        $scope = $definition['scope'];
        if (!in_array($scope, self::SCOPES, true)) {
            throw FormRuleException::form('Publico invalido.');
        }

        if (($scope === FormTypes::SCOPE_SUBUNIT && empty($definition['subunitId']))
            || ($scope === FormTypes::SCOPE_EMPLOYEE && empty($definition['employeeId']))) {
            throw FormRuleException::form('Escolha a empresa/posto ou a pessoa que vai receber.');
        }

        // Anonymous to one person is anonymous in name only.
        if (!empty($definition['anonymous']) && $scope === FormTypes::SCOPE_EMPLOYEE) {
            throw FormRuleException::form(
                'Pesquisa anonima para uma pessoa so nao e anonima: '
                . 'escolha outro publico ou desmarque "anonima".'
            );
        }
    }

    private static function assertItem(array $item, int $position, bool $quiz): void
    {
        if (!in_array($item['type'], FormTypes::ITEM_TYPES, true)) {
            throw FormRuleException::at($position, 'tipo de bloco desconhecido.');
        }

        if (trim((string)$item['prompt']) === '') {
            throw FormRuleException::at($position, 'escreva o enunciado.');
        }

        if (in_array($item['type'], FormTypes::CHOICE_TYPES, true)) {
            $options = $item['options'] ?? [];
            if (count($options) < 2) {
                throw FormRuleException::at($position, 'inclua ao menos duas opcoes.');
            }
            foreach ($options as $option) {
                if (trim((string)$option['label']) === '') {
                    throw FormRuleException::at($position, 'ha uma opcao sem texto.');
                }
            }
        }

        if (!$quiz) {
            return;
        }

        $correct = count(array_filter($item['options'] ?? [], static fn (array $o) => !empty($o['isCorrect'])));
        if ($item['type'] === FormTypes::TYPE_SINGLE && $correct !== 1) {
            throw FormRuleException::at($position, 'marque exatamente uma opcao certa.');
        }
        if ($item['type'] === FormTypes::TYPE_MULTIPLE && $correct === 0) {
            throw FormRuleException::at($position, 'marque ao menos uma opcao certa.');
        }
        if ($item['type'] === FormTypes::TYPE_YES_NO && $item['correctYesNo'] === null) {
            throw FormRuleException::at($position, 'marque a resposta certa (Sim ou Nao).');
        }
    }
}
