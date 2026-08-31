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

namespace OrangeHRM\Core\Utility;

use Respect\Validation\Rules\Cnpj;
use Respect\Validation\Rules\Pis;

/**
 * BR: check digits for the documents the AFD is keyed by.
 *
 * The AFD header identifies the employer by CNPJ and every punch line
 * identifies the worker by PIS/NIS. Neither is verifiable downstream, so a
 * typo only surfaces when an auditor rejects the file -- these checks exist to
 * catch it at the registration screen instead.
 *
 * The arithmetic comes from Respect\Validation, which the API layer already
 * exposes as `Rules::CNPJ` and `Rules::PIS`. This class exists so services
 * outside that layer -- the exporters -- share the same answer, and so callers
 * can tell "not informed" from "informed as zero".
 */
class BrazilianDocument
{
    /**
     * Digits only, or null when nothing meaningful is left.
     *
     * Callers need the difference between "not informed" and "informed as
     * zero": a blank CNPJ padded with zeros would ship a valid-looking header
     * naming no company at all.
     */
    public static function digits(?string $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string)$value);
        return ($digits === null || $digits === '') ? null : $digits;
    }

    /**
     * CNPJ: 14 digits closed by two check digits (Receita Federal).
     */
    public static function isValidCnpj(?string $value): bool
    {
        return $value !== null && (new Cnpj())->validate($value);
    }

    /**
     * PIS/PASEP/NIS: 11 digits closed by one check digit.
     */
    public static function isValidPis(?string $value): bool
    {
        return $value !== null && (new Pis())->validate($value);
    }
}
