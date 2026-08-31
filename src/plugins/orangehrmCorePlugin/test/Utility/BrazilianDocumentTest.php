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

namespace OrangeHRM\Tests\Core\Utility;

use OrangeHRM\Core\Utility\BrazilianDocument;
use OrangeHRM\Tests\Util\TestCase;

/**
 * Check digits for the two documents the AFD identifies people and companies by.
 *
 * A wrong CNPJ in the file header, or a wrong PIS on a punch line, makes the
 * whole export useless to the auditor -- and nothing downstream can tell a
 * typo from a real number, so it has to be caught at the registration screen.
 *
 * @group Core
 * @group Utility
 */
class BrazilianDocumentTest extends TestCase
{
    /**
     * Real, publicly known CNPJs: Acacia do Sul (the unit already registered
     * here), Banco do Brasil, Petrobras and Bradesco.
     *
     * @return string[][]
     */
    public function validCnpjProvider(): array
    {
        return [
            ['17493799000106'],
            ['00000000000191'],
            ['33000167000101'],
            ['60746948000112'],
        ];
    }

    /**
     * @dataProvider validCnpjProvider
     */
    public function testARealCnpjIsAccepted(string $cnpj): void
    {
        $this->assertTrue(BrazilianDocument::isValidCnpj($cnpj));
    }

    public function testACnpjIsAcceptedWithTheUsualPunctuation(): void
    {
        $this->assertTrue(BrazilianDocument::isValidCnpj('17.493.799/0001-06'));
    }

    /**
     * A single mistyped digit has to fail, which is the whole reason for
     * checking the digits instead of only counting them.
     */
    public function testASingleMistypedDigitIsRejected(): void
    {
        $this->assertFalse(BrazilianDocument::isValidCnpj('17493799000107'));
        $this->assertFalse(BrazilianDocument::isValidCnpj('17493798000106'));
    }

    public function testACnpjWithTheWrongLengthIsRejected(): void
    {
        $this->assertFalse(BrazilianDocument::isValidCnpj('1749379900010'));
        $this->assertFalse(BrazilianDocument::isValidCnpj('174937990001060'));
    }

    /**
     * Repeated digits satisfy the check-digit arithmetic but are not issued to
     * anyone -- they are what people type to get past a required field.
     */
    public function testARepeatedDigitCnpjIsRejected(): void
    {
        $this->assertFalse(BrazilianDocument::isValidCnpj('00000000000000'));
        $this->assertFalse(BrazilianDocument::isValidCnpj('11111111111111'));
    }

    public function testAnEmptyCnpjIsNotValid(): void
    {
        $this->assertFalse(BrazilianDocument::isValidCnpj(''));
        $this->assertFalse(BrazilianDocument::isValidCnpj(null));
    }

    /**
     * @return string[][]
     */
    public function validPisProvider(): array
    {
        return [
            ['12064487893'],
            ['12345678900'],
            ['17000040449'],
        ];
    }

    /**
     * @dataProvider validPisProvider
     */
    public function testAPisWithACorrectCheckDigitIsAccepted(string $pis): void
    {
        $this->assertTrue(BrazilianDocument::isValidPis($pis));
    }

    public function testAPisIsAcceptedWithTheUsualPunctuation(): void
    {
        $this->assertTrue(BrazilianDocument::isValidPis('120.64487.89-3'));
    }

    public function testAPisWithAWrongCheckDigitIsRejected(): void
    {
        $this->assertFalse(BrazilianDocument::isValidPis('12064487894'));
    }

    public function testAPisWithTheWrongLengthIsRejected(): void
    {
        $this->assertFalse(BrazilianDocument::isValidPis('1206448789'));
        $this->assertFalse(BrazilianDocument::isValidPis('120644878931'));
    }

    public function testARepeatedDigitPisIsRejected(): void
    {
        $this->assertFalse(BrazilianDocument::isValidPis('00000000000'));
        $this->assertFalse(BrazilianDocument::isValidPis('11111111111'));
    }

    public function testAnEmptyPisIsNotValid(): void
    {
        $this->assertFalse(BrazilianDocument::isValidPis(''));
        $this->assertFalse(BrazilianDocument::isValidPis(null));
    }

    /**
     * The AFD carries digits only, so callers need the stripped form and must
     * be able to tell "not informed" from "informed as zero".
     */
    public function testDigitsAreExtractedAndBlankBecomesNull(): void
    {
        $this->assertSame('17493799000106', BrazilianDocument::digits('17.493.799/0001-06'));
        $this->assertNull(BrazilianDocument::digits('   '));
        $this->assertNull(BrazilianDocument::digits(null));
    }
}
