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

use OrangeHRM\Attendance\Service\Form\FormCsv;
use OrangeHRM\Tests\Util\TestCase;

/**
 * The results spreadsheet, as Excel in Portuguese opens it.
 *
 * Answers are typed by employees and opened by HR in a spreadsheet, so a cell
 * that starts like a formula must stay text -- otherwise "=HYPERLINK(...)" in
 * a written answer runs on HR's machine.
 *
 * @group Attendance
 * @group Forms
 */
class FormCsvTest extends TestCase
{
    public function testFormulaLikeCellsStayText(): void
    {
        foreach (['=SOMA(A1)', '+5', '-1+1', '@cmd', "\tx", "\rx"] as $value) {
            $this->assertStringStartsWith("'", trim(FormCsv::escapeCell($value), '"'), $value);
        }
    }

    public function testSeparatorsAndQuotesAreQuoted(): void
    {
        $this->assertSame('"a;b"', FormCsv::escapeCell('a;b'));
        $this->assertSame('"diz ""oi"""', FormCsv::escapeCell('diz "oi"'));
        $this->assertSame("\"linha 1\nlinha 2\"", FormCsv::escapeCell("linha 1\nlinha 2"));
        $this->assertSame('simples', FormCsv::escapeCell('simples'));
    }

    public function testNumbersUseTheDecimalComma(): void
    {
        $this->assertSame('87,5', FormCsv::escapeCell(87.5));
        $this->assertSame('', FormCsv::escapeCell(null));
    }

    public function testTheFileOpensAsUtf8InExcel(): void
    {
        $csv = FormCsv::build(['Nome', 'Nota %'], [['João', 87.5], ['Ana', null]]);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertSame("\xEF\xBB\xBFNome;Nota %\r\nJoão;87,5\r\nAna;\r\n", $csv);
    }
}
