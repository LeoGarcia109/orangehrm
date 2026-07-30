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

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use OrangeHRM\Entity\AttendanceRecord;

/**
 * Manages the Brazilian "banco de horas" (time bank) per CLT art. 59.
 * Tracks positive/negative balance per employee per period, and provides
 * period closure and compensation workflows.
 */
class TimeBankService
{
    private EntityManagerInterface $em;
    private BrazilianWorkTimeCalculator $calculator;

    public function __construct(EntityManagerInterface $em, ?BrazilianWorkTimeCalculator $calculator = null)
    {
        $this->em = $em;
        $this->calculator = $calculator ?? new BrazilianWorkTimeCalculator();
    }

    /**
     * Calculate and persist the time bank balance for an employee in a period.
     *
     * @param int $employeeNumber
     * @param DateTime $periodStart
     * @param DateTime $periodEnd
     * @param int $businessDays Number of expected work days in the period
     * @return array The computed time bank entry
     */
    public function calculatePeriod(
        int $employeeNumber,
        DateTime $periodStart,
        DateTime $periodEnd,
        int $businessDays
    ): array {
        $records = $this->fetchRecordsForPeriod($employeeNumber, $periodStart, $periodEnd);
        $dayResults = $this->groupAndCalculateByDay($records);

        $totalWorked = 0;
        $totalOvertime = 0;
        $totalNight = 0;

        foreach ($dayResults as $dayResult) {
            $totalWorked += $dayResult['totalWorkedSeconds'];
            $totalOvertime += $dayResult['overtimeFirst2hSeconds'] + $dayResult['overtimeBeyond2hSeconds'];
            $totalNight += (int)round($dayResult['nightHoursReduced'] * 3600);
        }

        $expectedSeconds = $this->calculator->getExpectedSecondsForDays($businessDays);
        $balance = $this->calculator->calculateTimeBankBalance($totalWorked, $expectedSeconds);

        $this->persistTimeBankEntry(
            $employeeNumber,
            $periodStart,
            $periodEnd,
            $expectedSeconds,
            $totalWorked,
            $balance,
            $totalOvertime,
            $totalNight
        );

        return [
            'employeeNumber' => $employeeNumber,
            'periodStart' => $periodStart->format('Y-m-d'),
            'periodEnd' => $periodEnd->format('Y-m-d'),
            'businessDays' => $businessDays,
            'expectedSeconds' => $expectedSeconds,
            'expectedFormatted' => BrazilianWorkTimeCalculator::formatDuration($expectedSeconds),
            'workedSeconds' => $totalWorked,
            'workedFormatted' => BrazilianWorkTimeCalculator::formatDuration($totalWorked),
            'balanceSeconds' => $balance,
            'balanceFormatted' => BrazilianWorkTimeCalculator::formatDuration($balance),
            'overtimeSeconds' => $totalOvertime,
            'overtimeFormatted' => BrazilianWorkTimeCalculator::formatDuration($totalOvertime),
            'nightBonusSeconds' => $totalNight,
            'nightBonusFormatted' => BrazilianWorkTimeCalculator::formatDuration($totalNight),
            'dailyBreakdown' => $dayResults,
        ];
    }

    /**
     * Get the current accumulated balance for an employee (sum of all OPEN periods).
     */
    public function getCurrentBalance(int $employeeNumber): array
    {
        $conn = $this->em->getConnection();
        $result = $conn->executeQuery(
            'SELECT COALESCE(SUM(balance_seconds), 0) AS total_balance,
                    COALESCE(SUM(overtime_seconds), 0) AS total_overtime,
                    COUNT(*) AS open_periods
             FROM ohrm_br_time_bank
             WHERE employee_id = ? AND status = ?',
            [$employeeNumber, 'OPEN']
        );
        $row = $result->fetchAssociative();

        return [
            'balanceSeconds' => (int)$row['total_balance'],
            'balanceFormatted' => BrazilianWorkTimeCalculator::formatDuration((int)$row['total_balance']),
            'overtimeSeconds' => (int)$row['total_overtime'],
            'overtimeFormatted' => BrazilianWorkTimeCalculator::formatDuration((int)$row['total_overtime']),
            'openPeriods' => (int)$row['open_periods'],
        ];
    }

    /**
     * Close a time bank period (marks it as CLOSED, no further changes).
     */
    public function closePeriod(int $employeeNumber, DateTime $periodStart, DateTime $periodEnd): bool
    {
        $conn = $this->em->getConnection();
        $affected = $conn->executeStatement(
            'UPDATE ohrm_br_time_bank SET status = ?, closed_at = NOW()
             WHERE employee_id = ? AND period_start = ? AND period_end = ? AND status = ?',
            ['CLOSED', $employeeNumber, $periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d'), 'OPEN']
        );
        return $affected > 0;
    }

    /**
     * @return AttendanceRecord[]
     */
    private function fetchRecordsForPeriod(int $employeeNumber, DateTime $start, DateTime $end): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('ar')
            ->from(AttendanceRecord::class, 'ar')
            ->join('ar.employee', 'e')
            ->where('e.empNumber = :empNumber')
            ->andWhere('ar.punchInUserTime >= :start')
            ->andWhere('ar.punchInUserTime <= :end')
            ->andWhere('ar.punchOutUserTime IS NOT NULL')
            ->setParameter('empNumber', $employeeNumber)
            ->setParameter('start', $start)
            ->setParameter('end', (clone $end)->setTime(23, 59, 59))
            ->orderBy('ar.punchInUserTime', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Group records by day and calculate each day's breakdown.
     */
    private function groupAndCalculateByDay(array $records): array
    {
        $byDay = [];
        foreach ($records as $record) {
            $dayKey = $record->getPunchInUserTime()->format('Y-m-d');
            $byDay[$dayKey][] = [
                'in' => $record->getPunchInUserTime(),
                'out' => $record->getPunchOutUserTime(),
            ];
        }

        $results = [];
        foreach ($byDay as $day => $pairs) {
            $date = new DateTime($day);
            $isSunday = ((int)$date->format('w') === 0);
            $dayResult = $this->calculator->calculateDay($pairs, $isSunday);
            $dayResult['date'] = $day;
            $results[] = $dayResult;
        }

        return $results;
    }

    private function persistTimeBankEntry(
        int $employeeNumber,
        DateTime $periodStart,
        DateTime $periodEnd,
        int $expectedSeconds,
        int $workedSeconds,
        int $balanceSeconds,
        int $overtimeSeconds,
        int $nightBonusSeconds
    ): void {
        $conn = $this->em->getConnection();
        $conn->executeStatement(
            'INSERT INTO ohrm_br_time_bank
                (employee_id, period_start, period_end, expected_seconds, worked_seconds,
                 balance_seconds, overtime_seconds, night_bonus_seconds, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                expected_seconds = VALUES(expected_seconds),
                worked_seconds = VALUES(worked_seconds),
                balance_seconds = VALUES(balance_seconds),
                overtime_seconds = VALUES(overtime_seconds),
                night_bonus_seconds = VALUES(night_bonus_seconds),
                updated_at = NOW()',
            [
                $employeeNumber,
                $periodStart->format('Y-m-d'),
                $periodEnd->format('Y-m-d'),
                $expectedSeconds,
                $workedSeconds,
                $balanceSeconds,
                $overtimeSeconds,
                $nightBonusSeconds,
                'OPEN',
            ]
        );
    }
}
