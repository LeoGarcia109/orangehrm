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

use Doctrine\ORM\EntityManagerInterface;
use OrangeHRM\Entity\AttendanceRecord;

/**
 * Assigns the NSR (Numero Sequencial de Registro) to attendance records.
 *
 * Per Portaria 673/2021 art. 23, every punch event must receive a unique,
 * sequential number that cannot be reused or reordered. The NSR is global
 * across all employees (not per-employee).
 *
 * This service uses a pessimistic write lock on the last record to guarantee
 * sequentiality even under concurrent requests.
 */
class NsrService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Get the next NSR value. Must be called within an active transaction.
     *
     * Uses SELECT MAX(nsr) FOR UPDATE to serialize concurrent access.
     *
     * @return int The next NSR to assign
     */
    public function getNextNsr(): int
    {
        $conn = $this->em->getConnection();

        // Lock the row with the highest NSR to prevent concurrent duplicates
        $result = $conn->executeQuery(
            'SELECT MAX(nsr) AS max_nsr FROM ohrm_attendance_record FOR UPDATE'
        );
        $maxNsr = (int)($result->fetchOne() ?? 0);

        return $maxNsr + 1;
    }

    /**
     * Assign the next NSR to an attendance record and persist it.
     * Wraps the operation in a transaction if one is not already active.
     *
     * @param AttendanceRecord $record
     * @return int The assigned NSR
     */
    public function assignNsr(AttendanceRecord $record): int
    {
        $conn = $this->em->getConnection();
        $ownsTransaction = !$conn->isTransactionActive();

        if ($ownsTransaction) {
            $conn->beginTransaction();
        }

        try {
            $nsr = $this->getNextNsr();
            $record->setNsr($nsr);
            $this->em->persist($record);
            $this->em->flush();

            if ($ownsTransaction) {
                $conn->commit();
            }

            return $nsr;
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $conn->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Get the current (latest) NSR value without locking.
     * Useful for display/reporting purposes.
     */
    public function getCurrentNsr(): int
    {
        $conn = $this->em->getConnection();
        $result = $conn->executeQuery('SELECT MAX(nsr) FROM ohrm_attendance_record');
        return (int)($result->fetchOne() ?? 0);
    }
}
