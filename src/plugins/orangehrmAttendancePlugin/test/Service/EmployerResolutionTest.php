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

namespace OrangeHRM\Tests\Attendance\Service;

use OrangeHRM\Attendance\Service\EmployerResolverService;
use OrangeHRM\Entity\Organization;
use OrangeHRM\Entity\Subunit;
use OrangeHRM\Tests\Util\TestCase;

/**
 * Which company signs an employee's AFD, isolated from the database.
 *
 * The CNPJ lives on the company unit, but employees sit in departments below
 * it, so resolution has to climb the same chain the geofence walks. The root
 * ("Grupo HRR") deliberately has no CNPJ -- it only groups the companies -- so
 * running out of chain means the export has no employer, not employer zero.
 *
 * @group Attendance
 * @group Employer
 */
class EmployerResolutionTest extends TestCase
{
    private EmployerResolverService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EmployerResolverService();
    }

    private function unit(string $name, ?string $cnpj = null, ?string $cei = null): Subunit
    {
        $unit = new Subunit();
        $unit->setName($name);
        $unit->setCnpj($cnpj);
        $unit->setCei($cei);
        return $unit;
    }

    private function organization(?string $taxId, ?string $registrationNumber = null): Organization
    {
        $org = new Organization();
        $org->setName('Grupo HRR');
        $org->setTaxId($taxId);
        // Typed property with no default: Doctrine hydrates it in production,
        // so a hand-built entity has to set it or reading throws.
        $org->setRegistrationNumber($registrationNumber);
        return $org;
    }

    public function testTheEmployeesOwnCompanySignsTheExport(): void
    {
        $chain = [$this->unit('Acacia do Sul', '17.493.799/0001-06')];

        $employer = $this->service->resolveFromChain($chain, $this->organization(null));

        $this->assertSame('17493799000106', $employer['cnpj']);
        $this->assertSame('Acacia do Sul', $employer['name']);
    }

    /**
     * An employee in a department under the company: the department carries no
     * CNPJ, so resolution has to keep climbing instead of giving up.
     */
    public function testADepartmentInheritsTheCnpjOfItsCompany(): void
    {
        $chain = [
            $this->unit('Producao'),
            $this->unit('Acacia do Sul', '17.493.799/0001-06'),
            $this->unit('Grupo HRR'),
        ];

        $employer = $this->service->resolveFromChain($chain, $this->organization(null));

        $this->assertSame('17493799000106', $employer['cnpj']);
        $this->assertSame('Acacia do Sul', $employer['name']);
    }

    /**
     * Two companies under the same root must not borrow each other's identity:
     * the nearest one up the chain wins.
     */
    public function testTheNearestCompanyUpTheChainWins(): void
    {
        $chain = [
            $this->unit('Filial Itajuipe', '33.000.167/0001-01'),
            $this->unit('Acacia do Sul', '17.493.799/0001-06'),
        ];

        $employer = $this->service->resolveFromChain($chain, $this->organization(null));

        $this->assertSame('33000167000101', $employer['cnpj']);
        $this->assertSame('Filial Itajuipe', $employer['name']);
    }

    /**
     * The root groups the companies and has no CNPJ by design. Nothing to fall
     * back to means no employer -- the caller has to refuse, and it can only
     * do that if it is told null instead of a padded zero.
     */
    public function testRunningOutOfChainYieldsNoEmployerAtAll(): void
    {
        $chain = [$this->unit('Producao'), $this->unit('Grupo HRR')];

        $employer = $this->service->resolveFromChain($chain, $this->organization(null));

        $this->assertNull($employer['cnpj']);
    }

    public function testAnEmployeeWithNoUnitFallsBackToTheOrganization(): void
    {
        $employer = $this->service->resolveFromChain([], $this->organization('33.000.167/0001-01'));

        $this->assertSame('33000167000101', $employer['cnpj']);
    }

    /**
     * A mistyped CNPJ is worse than a missing one: it names a company that is
     * not the employer. Treated as absent so the export refuses.
     */
    public function testAMistypedCompanyCnpjIsNotUsed(): void
    {
        $chain = [$this->unit('Acacia do Sul', '17.493.799/0001-07')];

        $employer = $this->service->resolveFromChain($chain, $this->organization(null));

        $this->assertNull($employer['cnpj']);
    }

    public function testAMistypedCompanyCnpjDoesNotFallBackToTheCompanyAbove(): void
    {
        $chain = [
            $this->unit('Acacia do Sul', '17.493.799/0001-07'),
            $this->unit('Holding', '33.000.167/0001-01'),
        ];

        $employer = $this->service->resolveFromChain($chain, $this->organization(null));

        $this->assertNull($employer['cnpj']);
        $this->assertSame('Acacia do Sul', $employer['name']);
    }

    public function testAnInvalidOrganizationTaxIdIsNotUsedEither(): void
    {
        $employer = $this->service->resolveFromChain([], $this->organization('11.111.111/1111-11'));

        $this->assertNull($employer['cnpj']);
    }

    /**
     * CEI/CNO identifies a worksite rather than a company and has no check
     * digit here, so it travels alongside without gating the export.
     */
    public function testTheCeiComesFromTheSameUnitChain(): void
    {
        $chain = [
            $this->unit('Producao'),
            $this->unit('Acacia do Sul', '17.493.799/0001-06', '123456789012'),
        ];

        $employer = $this->service->resolveFromChain($chain, $this->organization(null));

        $this->assertSame('123456789012', $employer['cei']);
    }
}
