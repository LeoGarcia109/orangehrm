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

use OrangeHRM\Attendance\Traits\Service\SubunitChainTrait;
use OrangeHRM\Core\Utility\BrazilianDocument;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\Organization;
use OrangeHRM\Entity\Subunit;

/**
 * BR: which company signs an employee's AFD, AFDT, e-Social event or receipt.
 *
 * The CNPJ lives on the company unit of the organization structure, but
 * employees sit in departments below it, so resolution climbs the same chain
 * the geofence walks and stops at the nearest unit that declared a CNPJ.
 *
 * A CNPJ that fails its check digits is reported as absent rather than passed
 * along: naming the wrong company in the header is worse than naming none, and
 * the caller can only refuse if it is told null instead of a padded zero.
 */
class EmployerResolverService
{
    use SubunitChainTrait;

    /**
     * Resolve employer identity for an employee.
     *
     * @param Employee|null $employee
     * @param Organization|null $organization
     * @return array{cnpj: string|null, cei: string|null, name: string|null}
     *             cnpj/cei contain digits only; cnpj is null when not informed
     *             or when the check digits do not close.
     */
    public function resolveForEmployee(?Employee $employee, ?Organization $organization): array
    {
        $unit = $employee?->getSubDivision();
        $chain = $unit instanceof Subunit ? $this->getSubunitChain($unit) : [];

        return $this->resolveFromChain($chain, $organization);
    }

    /**
     * The decision, isolated from the database.
     *
     * @param Subunit[] $chain nearest unit first, ending at the root
     * @param Organization|null $organization
     * @return array{cnpj: string|null, cei: string|null, name: string|null}
     */
    public function resolveFromChain(array $chain, ?Organization $organization): array
    {
        $name = $organization?->getName();
        $cnpj = null;
        $declared = false;

        foreach ($chain as $unit) {
            if (BrazilianDocument::digits($unit->getCnpj()) === null) {
                continue;
            }
            // The nearest unit that declared a CNPJ is the employer, even when
            // what it declared turns out to be invalid: climbing past it would
            // silently file the employee under the company above.
            $declared = true;
            $name = $unit->getName();
            if (BrazilianDocument::isValidCnpj($unit->getCnpj())) {
                $cnpj = BrazilianDocument::digits($unit->getCnpj());
            }
            break;
        }

        if (!$declared && BrazilianDocument::isValidCnpj($organization?->getTaxId())) {
            $cnpj = BrazilianDocument::digits($organization->getTaxId());
        }

        return [
            'cnpj' => $cnpj,
            'cei' => $this->resolveCei($chain, $organization),
            'name' => $name,
        ];
    }

    /**
     * CEI/CNO identifies a worksite rather than a company and carries no check
     * digit here, so it travels alongside without gating the export.
     *
     * @param Subunit[] $chain
     * @param Organization|null $organization
     * @return string|null
     */
    private function resolveCei(array $chain, ?Organization $organization): ?string
    {
        foreach ($chain as $unit) {
            $cei = BrazilianDocument::digits($unit->getCei());
            if ($cei !== null) {
                return $cei;
            }
        }

        return BrazilianDocument::digits($organization?->getRegistrationNumber());
    }

    /**
     * Digits only; null when nothing meaningful is left.
     *
     * @param string $value
     * @return string|null
     */
    public function sanitizeDigits(string $value): ?string
    {
        return BrazilianDocument::digits($value);
    }
}
