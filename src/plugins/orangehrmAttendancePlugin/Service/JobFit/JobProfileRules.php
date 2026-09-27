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
 * BR: what a job title's profile may hold and who may be compared against it.
 * Everything that reaches the database or the fit goes through here first.
 */
final class JobProfileRules
{
    public const MAX_PEOPLE = 20;
    public const MAX_COMPETENCIES = 30;
    public const DEFAULT_BEHAVIOR_WEIGHT = 50;
    public const DEFAULT_MIN_LEVEL = 3;

    /**
     * A job title without a profile yet: every range open, every factor
     * desirable, no competencies.
     */
    public static function defaultProfile(): array
    {
        $factors = [];
        foreach (InventoryCatalog::INSTRUMENTS as $instrument) {
            foreach (InventoryCatalog::factors($instrument) as $factor) {
                $factors[] = [
                    'instrument' => $instrument,
                    'factor' => $factor,
                    'min' => 0,
                    'max' => 100,
                    'weight' => JobFit::WEIGHT_DESIRABLE,
                ];
            }
        }
        return [
            'behaviorWeight' => self::DEFAULT_BEHAVIOR_WEIGHT,
            'factors' => $factors,
            'competencies' => [],
        ];
    }

    /**
     * @throws JobFitRuleException
     */
    public static function normalizeProfile(array $payload): array
    {
        $behaviorWeight = self::integer($payload['behaviorWeight'] ?? null, 'Peso do perfil comportamental invalido.');
        if ($behaviorWeight < 0 || $behaviorWeight > 100) {
            throw JobFitRuleException::because('O peso do perfil comportamental vai de 0 a 100.');
        }

        $given = [];
        foreach ((array)($payload['factors'] ?? []) as $row) {
            if (!is_array($row)) {
                throw JobFitRuleException::because('Fator invalido.');
            }
            $given[($row['instrument'] ?? '') . '|' . ($row['factor'] ?? '')] = $row;
        }

        $factors = [];
        $anyWeighted = false;
        foreach (InventoryCatalog::INSTRUMENTS as $instrument) {
            foreach (InventoryCatalog::factors($instrument) as $factor) {
                $row = $given[$instrument . '|' . $factor] ?? null;
                if ($row === null) {
                    throw JobFitRuleException::because('O perfil precisa dos 9 fatores.');
                }
                $min = self::integer($row['min'] ?? null, 'Faixa invalida.');
                $max = self::integer($row['max'] ?? null, 'Faixa invalida.');
                $weight = self::integer($row['weight'] ?? null, 'Importancia invalida.');
                if ($min < 0 || $max > 100 || $min % 5 !== 0 || $max % 5 !== 0 || $min > $max) {
                    throw JobFitRuleException::because('A faixa vai de 0 a 100, de 5 em 5, com o minimo ate o maximo.');
                }
                if (!in_array($weight, [JobFit::WEIGHT_IGNORE, JobFit::WEIGHT_DESIRABLE, JobFit::WEIGHT_ESSENTIAL], true)) {
                    throw JobFitRuleException::because('Importancia invalida.');
                }
                $anyWeighted = $anyWeighted || $weight > 0;
                $factors[] = compact('instrument', 'factor', 'min', 'max', 'weight');
            }
        }
        if (count($given) !== count($factors)) {
            throw JobFitRuleException::because('O perfil precisa dos 9 fatores.');
        }
        if (!$anyWeighted) {
            throw JobFitRuleException::because('Marque ao menos um fator como desejavel ou essencial.');
        }

        $rows = array_values((array)($payload['competencies'] ?? []));
        if (count($rows) > self::MAX_COMPETENCIES) {
            throw JobFitRuleException::because('No maximo 30 competencias por cargo.');
        }
        $competencies = [];
        $names = [];
        foreach ($rows as $order => $row) {
            if (!is_array($row)) {
                throw JobFitRuleException::because('Competencia invalida.');
            }
            $name = trim((string)($row['name'] ?? ''));
            if ($name === '' || mb_strlen($name) > 100) {
                throw JobFitRuleException::because('Toda competencia precisa de um nome de ate 100 caracteres.');
            }
            $key = mb_strtolower($name);
            if (isset($names[$key])) {
                throw JobFitRuleException::because('Competencia repetida: ' . $name . '.');
            }
            $names[$key] = true;
            $weight = self::integer($row['weight'] ?? null, 'Importancia invalida.');
            if (!in_array($weight, [JobFit::WEIGHT_DESIRABLE, JobFit::WEIGHT_ESSENTIAL], true)) {
                throw JobFitRuleException::because('Competencia e desejavel ou essencial.');
            }
            $minLevel = self::integer($row['minLevel'] ?? self::DEFAULT_MIN_LEVEL, 'Nivel minimo invalido.');
            if ($minLevel < 1 || $minLevel > 5) {
                throw JobFitRuleException::because('O nivel minimo vai de 1 a 5.');
            }
            $id = isset($row['id']) && $row['id'] !== null && $row['id'] !== ''
                ? self::integer($row['id'], 'Competencia invalida.')
                : null;
            $competencies[] = [
                'id' => $id,
                'name' => $name,
                'weight' => $weight,
                'minLevel' => $minLevel,
                'sortOrder' => $order,
            ];
        }

        return ['behaviorWeight' => $behaviorWeight, 'factors' => $factors, 'competencies' => $competencies];
    }

    /**
     * "c12,e5" -> [['type' => 'c', 'id' => 12], ['type' => 'e', 'id' => 5]]
     *
     * @throws JobFitRuleException
     */
    public static function parseSubjects(string $csv): array
    {
        $subjects = [];
        foreach (array_filter(array_map('trim', explode(',', $csv)), 'strlen') as $token) {
            if (!preg_match('/^([ce])([1-9]\d*)$/', $token, $m)) {
                throw JobFitRuleException::because('Pessoa invalida na comparacao.');
            }
            if (isset($subjects[$token])) {
                throw JobFitRuleException::because('A mesma pessoa aparece duas vezes.');
            }
            $subjects[$token] = ['type' => $m[1], 'id' => (int)$m[2]];
        }
        if ($subjects === [] || count($subjects) > self::MAX_PEOPLE) {
            throw JobFitRuleException::because('Compare de 1 a 20 pessoas.');
        }
        return array_values($subjects);
    }

    /**
     * "3,9" -> [3, 9]
     *
     * @return int[]
     * @throws JobFitRuleException
     */
    public static function parseIds(string $csv): array
    {
        $ids = [];
        foreach (array_filter(array_map('trim', explode(',', $csv)), 'strlen') as $token) {
            if (!preg_match('/^[1-9]\d*$/', $token)) {
                throw JobFitRuleException::because('Lista de funcionarios invalida.');
            }
            $ids[(int)$token] = (int)$token;
        }
        if ($ids === [] || count($ids) > self::MAX_PEOPLE) {
            throw JobFitRuleException::because('Escolha de 1 a 20 funcionarios de referencia.');
        }
        return array_values($ids);
    }

    /**
     * An integer, or a string of digits as a browser form may send it.
     *
     * @param mixed $value
     * @throws JobFitRuleException
     */
    private static function integer($value, string $message): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && preg_match('/^-?\d+$/', trim($value))) {
            return (int)trim($value);
        }
        throw JobFitRuleException::because($message);
    }
}
