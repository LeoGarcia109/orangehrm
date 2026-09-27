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
 * BR: whether a set of answers may be recorded.
 *
 * The phone checks the same things to be helpful; this is the check that
 * counts. Everything in the request is untrusted, down to the option ids.
 */
final class FormSubmissionRules
{
    /**
     * @throws FormRuleException
     */
    public static function assertAccepting(string $status, ?DateTime $dueAt, DateTime $now): void
    {
        if ($status !== FormTypes::STATUS_PUBLISHED) {
            throw FormRuleException::form('Este formulario nao esta aberto para respostas.');
        }
        if ($dueAt instanceof DateTime && $now > $dueAt) {
            throw FormRuleException::form(
                'O prazo deste formulario terminou em ' . $dueAt->format('d/m/Y') . '.'
            );
        }
    }

    public static function allowedAttempts(int $retakesGranted): int
    {
        return 1 + $retakesGranted;
    }

    /**
     * @throws FormRuleException
     */
    public static function assertAttemptLeft(int $used, int $retakesGranted): void
    {
        if ($used >= self::allowedAttempts($retakesGranted)) {
            throw FormRuleException::form('Voce ja respondeu este formulario.');
        }
    }

    /**
     * @param array $items the definition's items
     * @param array<int, array> $answers keyed by item id
     * @throws FormRuleException
     */
    public static function assertAnswers(array $items, array $answers): void
    {
        $positions = [];
        foreach (array_values($items) as $index => $item) {
            $positions[(int)$item['id']] = $index + 1;
        }
        foreach (array_keys($answers) as $itemId) {
            if (!isset($positions[(int)$itemId])) {
                throw FormRuleException::form('Resposta para uma questao que nao esta no formulario.');
            }
        }

        foreach (array_values($items) as $index => $item) {
            $position = $index + 1;
            $answer = $answers[(int)$item['id']] ?? null;

            if ($item['type'] === FormTypes::TYPE_CONTENT) {
                if ($answer !== null) {
                    throw FormRuleException::at($position, 'este bloco nao recebe resposta.');
                }
                continue;
            }

            if (self::isBlank($item['type'], $answer)) {
                if (!empty($item['required'])) {
                    throw FormRuleException::at($position, 'esta questao e obrigatoria.');
                }
                continue;
            }

            self::assertShape($item, $answer, $position);
        }
    }

    private static function isBlank(string $type, ?array $answer): bool
    {
        if ($answer === null) {
            return true;
        }
        switch ($type) {
            case FormTypes::TYPE_SINGLE:
            case FormTypes::TYPE_MULTIPLE:
                return empty($answer['optionIds']);
            case FormTypes::TYPE_SHORT_TEXT:
            case FormTypes::TYPE_LONG_TEXT:
                return !isset($answer['text']) || (is_string($answer['text']) && trim($answer['text']) === '');
            case FormTypes::TYPE_SCALE:
                return !isset($answer['scale']);
            default:
                return !isset($answer['yesNo']);
        }
    }

    private static function assertShape(array $item, array $answer, int $position): void
    {
        switch ($item['type']) {
            case FormTypes::TYPE_SINGLE:
            case FormTypes::TYPE_MULTIPLE:
                $given = $answer['optionIds'];
                if (!is_array($given)) {
                    throw FormRuleException::at($position, 'opcao invalida.');
                }
                $valid = array_map('intval', array_column($item['options'], 'id'));
                $ids = [];
                foreach ($given as $id) {
                    if (!is_numeric($id) || !in_array((int)$id, $valid, true)) {
                        throw FormRuleException::at($position, 'opcao invalida.');
                    }
                    $ids[] = (int)$id;
                }
                if (count($ids) !== count(array_unique($ids))) {
                    throw FormRuleException::at($position, 'opcao repetida.');
                }
                if ($item['type'] === FormTypes::TYPE_SINGLE && count($ids) > 1) {
                    throw FormRuleException::at($position, 'marque so uma opcao.');
                }
                return;

            case FormTypes::TYPE_SHORT_TEXT:
            case FormTypes::TYPE_LONG_TEXT:
                $max = $item['type'] === FormTypes::TYPE_SHORT_TEXT
                    ? FormTypes::SHORT_TEXT_MAX
                    : FormTypes::LONG_TEXT_MAX;
                if (!is_string($answer['text']) || mb_strlen($answer['text']) > $max) {
                    throw FormRuleException::at($position, "resposta com no maximo {$max} caracteres.");
                }
                return;

            case FormTypes::TYPE_SCALE:
                $scale = $answer['scale'];
                if (!is_int($scale) || $scale < FormTypes::SCALE_MIN || $scale > FormTypes::SCALE_MAX) {
                    throw FormRuleException::at($position, 'escolha uma nota de 1 a 5.');
                }
                return;

            default:
                if (!is_bool($answer['yesNo'])) {
                    throw FormRuleException::at($position, 'responda Sim ou Nao.');
                }
        }
    }
}
