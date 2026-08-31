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

use OrangeHRM\Attendance\Exception\AttendanceServiceException;
use OrangeHRM\Core\Utility\BrazilianDocument;
use OrangeHRM\Entity\Employee;

/**
 * BR: what the fiscal exports refuse to be generated without.
 *
 * Every field of the AFD/AFDT is fixed-width and zero-padded, so a missing
 * CNPJ or PIS still produces a well-formed file -- one that identifies no
 * company and no worker, and that only gets rejected when an auditor reads it.
 * These guards move that failure to export time, naming what is missing so the
 * operator knows which record to go fix.
 */
class BrExportGuard
{
    /**
     * The employer's CNPJ for the file header.
     *
     * @param array{cnpj: string|null, cei: string|null, name: string|null} $employer
     * @throws AttendanceServiceException when no valid CNPJ could be resolved
     */
    public static function cnpjFor(array $employer): string
    {
        if ($employer['cnpj'] === null) {
            throw AttendanceServiceException::employerNotIdentified($employer['name'] ?? null);
        }

        return $employer['cnpj'];
    }

    /**
     * The PIS/NIS that identifies the worker on every punch line.
     *
     * Falls back to "Other Id" for installations that recorded the PIS there
     * before the dedicated field existed -- but only when what is stored is
     * really a PIS, so a badge number never reaches the file.
     *
     * @throws AttendanceServiceException when no valid PIS is on file
     */
    public static function pisFor(Employee $employee): string
    {
        foreach ([$employee->getPisNumber(), $employee->getOtherId()] as $candidate) {
            if (BrazilianDocument::isValidPis($candidate)) {
                return BrazilianDocument::digits($candidate);
            }
        }

        throw AttendanceServiceException::employeePisMissing(
            trim($employee->getFirstName() . ' ' . $employee->getLastName())
        );
    }
}
