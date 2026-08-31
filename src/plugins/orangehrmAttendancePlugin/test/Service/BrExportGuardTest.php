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

use OrangeHRM\Attendance\Exception\AttendanceServiceException;
use OrangeHRM\Attendance\Service\BrExportGuard;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Tests\Util\TestCase;

/**
 * What the AFD refuses to be generated without.
 *
 * Every field in the file is fixed-width and zero-padded, so a missing CNPJ or
 * PIS produces a well-formed file that identifies nobody -- accepted by the
 * exporter, rejected by the auditor months later. These guards turn that into
 * an error at export time, naming what is missing.
 *
 * @group Attendance
 * @group Employer
 */
class BrExportGuardTest extends TestCase
{
    private function employee(?string $pis, ?string $otherId = null): Employee
    {
        $employee = new Employee();
        $employee->setFirstName('Leo');
        $employee->setLastName('Garcia');
        $employee->setPisNumber($pis);
        $employee->setOtherId($otherId);
        return $employee;
    }

    public function testAnIdentifiedEmployerYieldsItsCnpj(): void
    {
        $employer = ['cnpj' => '17493799000106', 'cei' => null, 'name' => 'Acacia do Sul'];

        $this->assertSame('17493799000106', BrExportGuard::cnpjFor($employer));
    }

    public function testAnUnidentifiedEmployerStopsTheExport(): void
    {
        $employer = ['cnpj' => null, 'cei' => null, 'name' => 'Acacia do Sul'];

        $this->expectException(AttendanceServiceException::class);
        BrExportGuard::cnpjFor($employer);
    }

    /**
     * The operator has to know which company to go fix, not just that one is
     * broken -- with 30 companies registered, "CNPJ ausente" is not actionable.
     */
    public function testTheRefusalNamesTheCompany(): void
    {
        $employer = ['cnpj' => null, 'cei' => null, 'name' => 'Acacia do Sul'];

        try {
            BrExportGuard::cnpjFor($employer);
            $this->fail('Deveria ter recusado a exportacao');
        } catch (AttendanceServiceException $e) {
            $this->assertStringContainsString('Acacia do Sul', $e->getMessage());
        }
    }

    public function testAnEmployeesPisIdentifiesThemOnEveryPunchLine(): void
    {
        $this->assertSame('12064487893', BrExportGuard::pisFor($this->employee('120.64487.89-3')));
    }

    public function testAnEmployeeWithoutAPisStopsTheExport(): void
    {
        $this->expectException(AttendanceServiceException::class);
        BrExportGuard::pisFor($this->employee(null));
    }

    public function testTheRefusalNamesTheEmployee(): void
    {
        try {
            BrExportGuard::pisFor($this->employee(null));
            $this->fail('Deveria ter recusado a exportacao');
        } catch (AttendanceServiceException $e) {
            $this->assertStringContainsString('Leo Garcia', $e->getMessage());
        }
    }

    public function testAMistypedPisStopsTheExportInsteadOfShippingIt(): void
    {
        $this->expectException(AttendanceServiceException::class);
        BrExportGuard::pisFor($this->employee('12064487894'));
    }

    /**
     * Installations that recorded the PIS in "Other Id" before the dedicated
     * field existed keep working -- but only when what is there really is a
     * PIS, never as a way to smuggle an arbitrary number into the file.
     */
    public function testOtherIdIsUsedOnlyWhenItHoldsARealPis(): void
    {
        $this->assertSame('12064487893', BrExportGuard::pisFor($this->employee(null, '12064487893')));

        $this->expectException(AttendanceServiceException::class);
        BrExportGuard::pisFor($this->employee(null, 'CRACHA-0042'));
    }

    public function testTheDedicatedFieldWinsOverOtherId(): void
    {
        $this->assertSame(
            '12064487893',
            BrExportGuard::pisFor($this->employee('12064487893', '17000040449'))
        );
    }
}
