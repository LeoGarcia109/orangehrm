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
 * BR: a CSV that Excel in Portuguese opens as it is -- UTF-8 with BOM, ";"
 * between cells, decimal comma.
 *
 * Cells hold what employees typed and are opened by HR in a spreadsheet, so
 * anything that starts like a formula is kept as text.
 */
final class FormCsv
{
    private const FORMULA_START = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * @param string[] $header
     * @param array[] $rows
     */
    public static function build(array $header, array $rows): string
    {
        $lines = [implode(';', array_map([self::class, 'escapeCell'], $header))];
        foreach ($rows as $row) {
            $lines[] = implode(';', array_map([self::class, 'escapeCell'], $row));
        }
        return "\xEF\xBB\xBF" . implode("\r\n", $lines) . "\r\n";
    }

    /**
     * @param string|int|float|null $value
     */
    public static function escapeCell($value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_float($value) || is_int($value)) {
            return str_replace('.', ',', (string)$value);
        }
        $text = (string)$value;
        if ($text !== '' && in_array($text[0], self::FORMULA_START, true)) {
            $text = "'" . $text;
        }
        if (strpbrk($text, ";\"\r\n") !== false) {
            $text = '"' . str_replace('"', '""', $text) . '"';
        }
        return $text;
    }
}
