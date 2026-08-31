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
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\Organization;

/**
 * Generates the AFD (Arquivo Fonte de Dados) file as specified in
 * Portaria SEPRT 673/2021 (layout from Portaria 1.510/2009, Annex I).
 *
 * The AFD is a fixed-width text file with three record types:
 *   Type 1 - Header (employer identification, period, generation timestamp)
 *   Type 2 - Punch records (NSR, date, time, PIS)
 *   Type 9 - Trailer (count of type 2 records)
 */
class AFDExporter
{
    private const RECORD_TYPE_HEADER = '1';
    private const RECORD_TYPE_PUNCH = '2';
    private const RECORD_TYPE_TRAILER = '9';

    // REP type: "4" = outros (software-based, not a physical REP-C/A/P)
    private const REP_TYPE = '4';

    private EntityManagerInterface $em;

    private EmployerResolverService $employerResolver;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->employerResolver = new EmployerResolverService();
    }

    /**
     * Generate the AFD file content for a given period.
     *
     * @param DateTime $startDate Period start (inclusive)
     * @param DateTime $endDate   Period end (inclusive)
     * @param int|null $employeeNumber Filter by employee (null = all)
     * @return string The AFD file content (fixed-width, CRLF line endings)
     */
    public function generate(DateTime $startDate, DateTime $endDate, ?int $employeeNumber = null): string
    {
        $organization = $this->em->getRepository(Organization::class)->findOneBy([]);
        $records = $this->fetchAttendanceRecords($startDate, $endDate, $employeeNumber);

        // BR multi-company: AFD spec = one file per employer. When the export
        // targets a single employee, the header carries that employee's unit
        // CNPJ/CEI (falling back to the organization). Mixed-company exports
        // keep the organization header.
        $employer = $employeeNumber !== null
            ? $this->employerResolver->resolveForEmployee(
                $this->em->find(Employee::class, $employeeNumber),
                $organization
            )
            : $this->employerResolver->resolveFromChain([], $organization);
        // Refuses here rather than shipping a header full of zeros.
        BrExportGuard::cnpjFor($employer);

        $lines = [];
        $lines[] = $this->buildHeaderLine($organization, $employer, $startDate, $endDate);

        $punchCount = 0;
        foreach ($records as $record) {
            $punchLines = $this->buildPunchLines($record);
            foreach ($punchLines as $line) {
                $lines[] = $line;
                $punchCount++;
            }
        }

        $lines[] = $this->buildTrailerLine($punchCount);

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Generate and return as a downloadable file array (for API responses).
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param int|null $employeeNumber
     * @return array{filename: string, content: string, content_type: string}
     */
    public function generateFile(DateTime $startDate, DateTime $endDate, ?int $employeeNumber = null): array
    {
        $content = $this->generate($startDate, $endDate, $employeeNumber);
        $filename = sprintf(
            'AFD_%s_%s.txt',
            $startDate->format('Ymd'),
            $endDate->format('Ymd')
        );

        return [
            'filename' => $filename,
            'content' => $content,
            'content_type' => 'text/plain; charset=ASCII',
        ];
    }

    /**
     * Header record (type 1).
     *
     * @param Organization|null $org
     * @param array|null $employer BR multi-company override (cnpj, cei, name)
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return string
     */
    private function buildHeaderLine(
        ?Organization $org,
        array $employer,
        DateTime $startDate,
        DateTime $endDate
    ): string {
        $ceiSource = $employer['cei'] ?? '';
        $nameSource = $employer['name'] ?? $org?->getName();

        $cnpj = $this->sanitizeDigits(BrExportGuard::cnpjFor($employer), 14);
        $employerName = $this->padRight($nameSource ?? 'EMPREGADOR NAO INFORMADO', 150);
        $workplaceName = $this->padRight($org?->getName() ?? 'LOCAL NAO INFORMADO', 150);
        $cei = $this->sanitizeDigits($ceiSource, 12);
        $address = $this->buildAddress($org);

        $now = new DateTime();

        return self::RECORD_TYPE_HEADER
            . '1'                          // identificador do empregador: 1=CNPJ
            . $cnpj                       // CNPJ (14 posicoes)
            . $employerName               // razao social (150 posicoes)
            . $workplaceName              // local de trabalho (150 posicoes)
            . $cei                        // CEI/CNO (12 posicoes)
            . $address                    // endereco (150 posicoes)
            . self::REP_TYPE              // tipo de REP (1 posicao)
            . $startDate->format('dmY')   // data inicio (DDMMAAAA)
            . $endDate->format('dmY')     // data fim (DDMMAAAA)
            . $now->format('dmY')         // data geracao (DDMMAAAA)
            . $now->format('Hi');         // hora geracao (HHMM)
    }

    /**
     * Punch records (type 2). Each attendance record may generate up to
     * two punch lines: one for punch-in and one for punch-out.
     *
     * @return string[]
     */
    private function buildPunchLines(AttendanceRecord $record): array
    {
        $lines = [];
        $employee = $record->getEmployee();
        $pis = BrExportGuard::pisFor($employee);

        // Punch-in line
        if ($record->getPunchInUserTime() !== null) {
            $lines[] = $this->buildSinglePunchLine(
                $record->getNsr(),
                $record->getPunchInUserTime(),
                $pis
            );
        }

        // Punch-out line (uses same NSR; the time distinguishes in/out)
        if ($record->getPunchOutUserTime() !== null) {
            $lines[] = $this->buildSinglePunchLine(
                $record->getNsr(),
                $record->getPunchOutUserTime(),
                $pis
            );
        }

        return $lines;
    }

    /**
     * Single punch line (type 2).
     */
    private function buildSinglePunchLine(int $nsr, DateTime $time, string $pis): string
    {
        return self::RECORD_TYPE_PUNCH
            . str_pad((string)$nsr, 17, '0', STR_PAD_LEFT)  // NSR (17 posicoes)
            . $time->format('dmY')                          // data (DDMMAAAA)
            . $time->format('Hi')                           // hora (HHMM)
            . str_pad($pis, 12, '0', STR_PAD_LEFT);         // PIS (12 posicoes)
    }

    /**
     * Trailer record (type 9).
     */
    private function buildTrailerLine(int $punchCount): string
    {
        return self::RECORD_TYPE_TRAILER
            . str_pad((string)$punchCount, 17, '0', STR_PAD_LEFT);
    }

    /**
     * Fetch attendance records for the period, ordered by NSR.
     *
     * @return AttendanceRecord[]
     */
    private function fetchAttendanceRecords(DateTime $startDate, DateTime $endDate, ?int $employeeNumber): array
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

    private function buildAddress(?Organization $org): string
    {
        if ($org === null) {
            return str_pad('ENDERECO NAO INFORMADO', 150);
        }

        $parts = array_filter([
            $org->getStreet1(),
            $org->getStreet2(),
            $org->getCity(),
            $org->getProvince(),
            $org->getZipCode(),
        ]);

        return $this->padRight(implode(', ', $parts) ?: 'ENDERECO NAO INFORMADO', 150);
    }

    /**
     * Remove non-digit characters and pad/truncate to exact length.
     */
    private function sanitizeDigits(string $value, int $length): string
    {
        $digits = preg_replace('/\D/', '', $value);
        return str_pad(substr($digits, 0, $length), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Pad string to the right, truncating if necessary.
     * Converts to ASCII to comply with AFD charset requirements.
     */
    private function padRight(string $value, int $length): string
    {
        // Transliterate to ASCII (AFD requires no accented characters)
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = strtoupper($value);

        if (mb_strlen($value) > $length) {
            return mb_substr($value, 0, $length);
        }

        return str_pad($value, $length);
    }
}
