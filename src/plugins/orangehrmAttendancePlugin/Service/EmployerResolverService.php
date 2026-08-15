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

namespace OrangeHRM\Attendance\Service;

use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\Organization;

/**
 * BR: Resolves the employer identity (CNPJ/CEI/name) for an employee in a
 * multi-company setup.
 *
 * Priority: the employee's company-structure unit (CNPJ/CEI added in the
 * fase 5 migration) — falling back to the single Organization record
 * (tax_id / registration_number) when the unit has none or the employee has
 * no unit.
 */
class EmployerResolverService
{
    /**
     * Resolve employer identity for an employee.
     *
     * @param Employee|null $employee
     * @param Organization|null $organization
     * @return array{cnpj: string|null, cei: string|null, name: string|null}
     *             cnpj/cei contain digits only; null when not informed.
     */
    public function resolveForEmployee(?Employee $employee, ?Organization $organization): array
    {
        $cnpj = $this->sanitizeDigits($organization?->getTaxId() ?? '');
        $cei = $this->sanitizeDigits($organization?->getRegistrationNumber() ?? '');
        $name = $organization?->getName();

        $unit = $employee?->getSubDivision();
        if ($unit !== null) {
            $unitCnpj = $this->sanitizeDigits($unit->getCnpj() ?? '');
            if ($unitCnpj !== null) {
                $cnpj = $unitCnpj;
                $name = $unit->getName();
            }
            $unitCei = $this->sanitizeDigits($unit->getCei() ?? '');
            if ($unitCei !== null) {
                $cei = $unitCei;
            }
        }

        return ['cnpj' => $cnpj, 'cei' => $cei, 'name' => $name];
    }

    /**
     * Digits only; null when nothing meaningful is left.
     *
     * @param string $value
     * @return string|null
     */
    public function sanitizeDigits(string $value): ?string
    {
        $digits = preg_replace('/\D/', '', $value);
        return ($digits === null || $digits === '') ? null : $digits;
    }
}
