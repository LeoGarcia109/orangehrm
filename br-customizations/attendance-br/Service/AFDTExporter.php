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
use OrangeHRM\Entity\Organization;

/**
 * Generates the AFDT (Arquivo Fonte de Dados Tratado) file.
 *
 * The AFDT extends the AFD with "treated" data: it includes the same punch
 * records but adds information about the source of each record (original,
 * rectified, or manually included) and whether it was pre-assigned by the
 * employer's time-tracking software.
 *
 * Layout per Portaria 1.510/2009, Annex II:
 *   Type 1 - Header (same as AFD)
 *   Type 2 - Treated punch records (adds source type and rectification flag)
 *   Type 9 - Trailer
 */
class AFDTExporter
{
    private const RECORD_TYPE_HEADER = '1';
    private const RECORD_TYPE_PUNCH = '2';
    private const RECORD_TYPE_TRAILER = '9';

    // Source types for AFDT type 2 records
    private const SOURCE_ORIGINAL = 'O';      // Original from REP
    private const SOURCE_RECTIFIED = 'R';     // Rectified
    private const SOURCE_MANUAL = 'M';        // Manually included
    private const SOURCE_PRE_ASSIGNED = 'P';  // Pre-assigned by software

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Generate the AFDT file content for a given period.
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param int|null $employeeNumber
     * @return string AFDT file content (fixed-width, CRLF)
     */
    public function generate(DateTime $startDate, DateTime $endDate, ?int $employeeNumber = null): string
    {
        $organization = $this->em->getRepository(Organization::class)->findOneBy([]);
        $records = $this->fetchRecords($startDate, $endDate, $employeeNumber);

        $lines = [];
        $lines[] = $this->buildHeader($organization, $startDate, $endDate);

        $punchCount = 0;
        foreach ($records as $record) {
            $punchLines = $this->buildPunchLines($record);
            foreach ($punchLines as $line) {
                $lines[] = $line;
                $punchCount++;
            }
        }

        $lines[] = $this->buildTrailer($punchCount);

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * @return array{filename: string, content: string, content_type: string}
     */
    public function generateFile(DateTime $startDate, DateTime $endDate, ?int $employeeNumber = null): array
    {
        $content = $this->generate($startDate, $endDate, $employeeNumber);
        $filename = sprintf('AFDT_%s_%s.txt', $startDate->format('Ymd'), $endDate->format('Ymd'));

        return [
            'filename' => $filename,
            'content' => $content,
            'content_type' => 'text/plain; charset=ASCII',
        ];
    }

    private function buildHeader(?Organization $org, DateTime $startDate, DateTime $endDate): string
    {
        $cnpj = $this->sanitizeDigits($org?->getTaxId() ?? '', 14);
        $name = $this->padRight($org?->getName() ?? 'EMPREGADOR', 150);
        $cei = $this->sanitizeDigits($org?->getRegistrationNumber() ?? '', 12);
        $now = new DateTime();

        return self::RECORD_TYPE_HEADER
            . '1'
            . $cnpj
            . $name
            . $cei
            . $startDate->format('dmY')
            . $endDate->format('dmY')
            . $now->format('dmY')
            . $now->format('Hi');
    }

    /**
     * AFDT type 2 adds: source type (1 char) and rectification indicator.
     */
    private function buildPunchLines(AttendanceRecord $record): array
    {
        $lines = [];
        $employee = $record->getEmployee();
        $pis = $this->getEmployeePis($employee);
        $sourceType = $record->isRectified() ? self::SOURCE_RECTIFIED : self::SOURCE_ORIGINAL;

        if ($record->getPunchInUserTime() !== null) {
            $lines[] = self::RECORD_TYPE_PUNCH
                . str_pad((string)($record->getNsr() ?? 0), 17, '0', STR_PAD_LEFT)
                . $record->getPunchInUserTime()->format('dmY')
                . $record->getPunchInUserTime()->format('Hi')
                . str_pad($pis, 12, '0', STR_PAD_LEFT)
                . $sourceType
                . '0'; // not pre-assigned
        }

        if ($record->getPunchOutUserTime() !== null) {
            $lines[] = self::RECORD_TYPE_PUNCH
                . str_pad((string)($record->getNsr() ?? 0), 17, '0', STR_PAD_LEFT)
                . $record->getPunchOutUserTime()->format('dmY')
                . $record->getPunchOutUserTime()->format('Hi')
                . str_pad($pis, 12, '0', STR_PAD_LEFT)
                . $sourceType
                . '0';
        }

        return $lines;
    }

    private function buildTrailer(int $count): string
    {
        return self::RECORD_TYPE_TRAILER
            . str_pad((string)$count, 17, '0', STR_PAD_LEFT);
    }

    private function fetchRecords(DateTime $startDate, DateTime $endDate, ?int $employeeNumber): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('ar')
            ->from(AttendanceRecord::class, 'ar')
            ->join('ar.employee', 'e')
            ->where('ar.punchInUserTime >= :startDate')
            ->andWhere('ar.punchInUserTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('ar.nsr', 'ASC');

        if ($employeeNumber !== null) {
            $qb->andWhere('e.empNumber = :empNumber')
                ->setParameter('empNumber', $employeeNumber);
        }

        return $qb->getQuery()->getResult();
    }

    private function getEmployeePis($employee): string
    {
        if (method_exists($employee, 'getPisNumber') && !empty($employee->getPisNumber())) {
            return $this->sanitizeDigits($employee->getPisNumber(), 12);
        }
        return $this->sanitizeDigits($employee->getOtherId() ?? '', 12);
    }

    private function sanitizeDigits(string $value, int $length): string
    {
        $digits = preg_replace('/\D/', '', $value);
        return str_pad(substr($digits, 0, $length), $length, '0', STR_PAD_LEFT);
    }

    private function padRight(string $value, int $length): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = strtoupper($value);
        if (mb_strlen($value) > $length) {
            return mb_substr($value, 0, $length);
        }
        return str_pad($value, $length);
    }
}
