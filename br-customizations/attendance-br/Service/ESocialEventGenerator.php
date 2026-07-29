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
 * Generates e-Social XML events for attendance/payroll data.
 *
 * Supported events:
 *   S-1200 - Remuneracao de trabalhador vinculado ao RGPS
 *   S-1210 - Pagamentos de rendimentos do trabalho
 *
 * The generated XML follows the e-Social layout (version S-1.1).
 * Events are stored in ohrm_br_esocial_event for later transmission
 * via the e-Social web service (transmission is out of scope here).
 *
 * @see https://www.gov.br/esocial/pt-br/documentacao-tecnica
 */
class ESocialEventGenerator
{
    private const ESOCIAL_NS = 'http://www.esocial.gov.br/schema/evt';
    private const LAYOUT_VERSION = 'S-1.1';

    private EntityManagerInterface $em;
    private BrazilianWorkTimeCalculator $calculator;

    public function __construct(EntityManagerInterface $em, ?BrazilianWorkTimeCalculator $calculator = null)
    {
        $this->em = $em;
        $this->calculator = $calculator ?? new BrazilianWorkTimeCalculator();
    }

    /**
     * Generate S-1200 (Remuneracao) event XML for an employee in a period.
     *
     * @param int $employeeNumber
     * @param string $referencePeriod Format: YYYY-MM
     * @return array{xml: string, eventType: string, referencePeriod: string}
     */
    public function generateS1200(int $employeeNumber, string $referencePeriod): array
    {
        $employee = $this->em->find(Employee::class, $employeeNumber);
        if ($employee === null) {
            throw new \InvalidArgumentException("Employee {$employeeNumber} not found");
        }

        $org = $this->em->getRepository(Organization::class)->findOneBy([]);
        $cnpj = preg_replace('/\D/', '', $org?->getTaxId() ?? '');

        [$year, $month] = explode('-', $referencePeriod);
        $startDate = new DateTime("{$year}-{$month}-01");
        $endDate = (clone $startDate)->modify('last day of this month');

        // Calculate work time for the period
        $records = $this->fetchRecordsForPeriod($employeeNumber, $startDate, $endDate);
        $totalWorkedSeconds = 0;
        $totalOvertimeSeconds = 0;
        $totalNightSeconds = 0;

        foreach ($this->groupByDay($records) as $pairs) {
            $dayResult = $this->calculator->calculateDay($pairs);
            $totalWorkedSeconds += $dayResult['totalWorkedSeconds'];
            $totalOvertimeSeconds += $dayResult['overtimeFirst2hSeconds'] + $dayResult['overtimeBeyond2hSeconds'];
            $totalNightSeconds += (int)round($dayResult['nightHoursReduced'] * 3600);
        }

        $totalHours = round($totalWorkedSeconds / 3600, 2);
        $overtimeHours = round($totalOvertimeSeconds / 3600, 2);
        $nightHours = round($totalNightSeconds / 3600, 2);

        $cpf = $this->getEmployeeCpf($employee);
        $pis = $this->getEmployeePis($employee);
        $eventId = $this->generateEventId($cnpj);

        $xml = $this->buildS1200Xml(
            $eventId,
            $cnpj,
            $cpf,
            $pis,
            $referencePeriod,
            $totalHours,
            $overtimeHours,
            $nightHours
        );

        // Persist the event
        $this->persistEvent($employeeNumber, 'S-1200', $referencePeriod, $xml);

        return [
            'xml' => $xml,
            'eventType' => 'S-1200',
            'referencePeriod' => $referencePeriod,
            'eventId' => $eventId,
            'summary' => [
                'totalHours' => $totalHours,
                'overtimeHours' => $overtimeHours,
                'nightHours' => $nightHours,
            ],
        ];
    }

    /**
     * Generate S-1210 (Pagamentos) event XML for an employee in a period.
     *
     * @param int $employeeNumber
     * @param string $referencePeriod Format: YYYY-MM
     * @param float $netPay Net payment value (required - comes from payroll)
     * @param string $paymentDate Format: YYYY-MM-DD
     * @return array{xml: string, eventType: string, referencePeriod: string}
     */
    public function generateS1210(
        int $employeeNumber,
        string $referencePeriod,
        float $netPay,
        string $paymentDate
    ): array {
        $employee = $this->em->find(Employee::class, $employeeNumber);
        if ($employee === null) {
            throw new \InvalidArgumentException("Employee {$employeeNumber} not found");
        }

        $org = $this->em->getRepository(Organization::class)->findOneBy([]);
        $cnpj = preg_replace('/\D/', '', $org?->getTaxId() ?? '');
        $cpf = $this->getEmployeeCpf($employee);
        $eventId = $this->generateEventId($cnpj);

        $xml = $this->buildS1210Xml(
            $eventId,
            $cnpj,
            $cpf,
            $referencePeriod,
            $netPay,
            $paymentDate
        );

        $this->persistEvent($employeeNumber, 'S-1210', $referencePeriod, $xml);

        return [
            'xml' => $xml,
            'eventType' => 'S-1210',
            'referencePeriod' => $referencePeriod,
            'eventId' => $eventId,
        ];
    }

    private function buildS1200Xml(
        string $eventId,
        string $cnpj,
        string $cpf,
        string $pis,
        string $period,
        float $totalHours,
        float $overtimeHours,
        float $nightHours
    ): string {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $eSocial = $dom->createElement('eSocial');
        $eSocial->setAttribute('xmlns', self::ESOCIAL_NS);
        $dom->appendChild($eSocial);

        $evtRemun = $dom->createElement('evtRemun');
        $evtRemun->setAttribute('Id', $eventId);
        $eSocial->appendChild($evtRemun);

        // ideEvento
        $ideEvento = $dom->createElement('ideEvento');
        $ideEvento->appendChild($dom->createElement('indRetif', '1'));
        $ideEvento->appendChild($dom->createElement('tpAmb', '2'));
        $ideEvento->appendChild($dom->createElement('procEmi', '1'));
        $ideEvento->appendChild($dom->createElement('verProc', 'OrangeHRM-BR-' . self::LAYOUT_VERSION));
        $evtRemun->appendChild($ideEvento);

        // ideEmpregador
        $ideEmpregador = $dom->createElement('ideEmpregador');
        $ideEmpregador->appendChild($dom->createElement('tpInsc', '1'));
        $ideEmpregador->appendChild($dom->createElement('nrInsc', $cnpj));
        $evtRemun->appendChild($ideEmpregador);

        // ideTrabalhador
        $ideTrabalhador = $dom->createElement('ideTrabalhador');
        $ideTrabalhador->appendChild($dom->createElement('cpfTrab', $cpf));
        $evtRemun->appendChild($ideTrabalhador);

        // dmDev (demonstrativo de valores devidos)
        $dmDev = $dom->createElement('dmDev');
        $dmDev->appendChild($dom->createElement('ideDmDev', '1'));
        $dmDev->appendChild($dom->createElement('perRef', str_replace('-', '', $period)));

        // infoPerApur - periodo de apuracao
        $infoPerApur = $dom->createElement('infoPerApur');
        $ideEstab = $dom->createElement('ideEstab');
        $ideEstab->appendChild($dom->createElement('tpInsc', '1'));
        $ideEstab->appendChild($dom->createElement('nrInsc', $cnpj));

        $remunPerApur = $dom->createElement('remunPerApur');
        $remunPerApur->appendChild($dom->createElement('matricula', $pis));

        // Rubricas de horas
        $itensRemun = $dom->createElement('itensRemun');
        $itensRemun->appendChild($dom->createElement('codRubr', '0001'));
        $itensRemun->appendChild($dom->createElement('qtdRubr', number_format($totalHours, 2, '.', '')));
        $remunPerApur->appendChild($itensRemun);

        if ($overtimeHours > 0) {
            $itensHE = $dom->createElement('itensRemun');
            $itensHE->appendChild($dom->createElement('codRubr', '0002'));
            $itensHE->appendChild($dom->createElement('qtdRubr', number_format($overtimeHours, 2, '.', '')));
            $remunPerApur->appendChild($itensHE);
        }

        if ($nightHours > 0) {
            $itensNoturno = $dom->createElement('itensRemun');
            $itensNoturno->appendChild($dom->createElement('codRubr', '0003'));
            $itensNoturno->appendChild($dom->createElement('qtdRubr', number_format($nightHours, 2, '.', '')));
            $remunPerApur->appendChild($itensNoturno);
        }

        $ideEstab->appendChild($remunPerApur);
        $infoPerApur->appendChild($ideEstab);
        $dmDev->appendChild($infoPerApur);
        $evtRemun->appendChild($dmDev);

        return $dom->saveXML();
    }

    private function buildS1210Xml(
        string $eventId,
        string $cnpj,
        string $cpf,
        string $period,
        float $netPay,
        string $paymentDate
    ): string {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $eSocial = $dom->createElement('eSocial');
        $eSocial->setAttribute('xmlns', self::ESOCIAL_NS);
        $dom->appendChild($eSocial);

        $evtPgtos = $dom->createElement('evtPgtos');
        $evtPgtos->setAttribute('Id', $eventId);
        $eSocial->appendChild($evtPgtos);

        $ideEvento = $dom->createElement('ideEvento');
        $ideEvento->appendChild($dom->createElement('indRetif', '1'));
        $ideEvento->appendChild($dom->createElement('tpAmb', '2'));
        $ideEvento->appendChild($dom->createElement('procEmi', '1'));
        $ideEvento->appendChild($dom->createElement('verProc', 'OrangeHRM-BR-' . self::LAYOUT_VERSION));
        $evtPgtos->appendChild($ideEvento);

        $ideEmpregador = $dom->createElement('ideEmpregador');
        $ideEmpregador->appendChild($dom->createElement('tpInsc', '1'));
        $ideEmpregador->appendChild($dom->createElement('nrInsc', $cnpj));
        $evtPgtos->appendChild($ideEmpregador);

        $ideBenef = $dom->createElement('ideBenef');
        $ideBenef->appendChild($dom->createElement('cpfBenef', $cpf));

        $dmDev = $dom->createElement('dmDev');
        $dmDev->appendChild($dom->createElement('ideDmDev', '1'));
        $dmDev->appendChild($dom->createElement('perRef', str_replace('-', '', $period)));

        $pgto = $dom->createElement('pgto');
        $pgto->appendChild($dom->createElement('dtPgto', $paymentDate));
        $pgto->appendChild($dom->createElement('vlrLiq', number_format($netPay, 2, '.', '')));
        $dmDev->appendChild($pgto);

        $ideBenef->appendChild($dmDev);
        $evtPgtos->appendChild($ideBenef);

        return $dom->saveXML();
    }

    private function persistEvent(int $employeeNumber, string $eventType, string $period, string $xml): void
    {
        $conn = $this->em->getConnection();
        $conn->executeStatement(
            'INSERT INTO ohrm_br_esocial_event (employee_id, event_type, reference_period, xml_content, status)
             VALUES (?, ?, ?, ?, ?)',
            [$employeeNumber, $eventType, $period, $xml, 'DRAFT']
        );
    }

    private function fetchRecordsForPeriod(int $employeeNumber, DateTime $start, DateTime $end): array
    {
        return $this->em->createQueryBuilder()
            ->select('ar')
            ->from(AttendanceRecord::class, 'ar')
            ->join('ar.employee', 'e')
            ->where('e.empNumber = :empNumber')
            ->andWhere('ar.punchInUserTime >= :start')
            ->andWhere('ar.punchInUserTime <= :end')
            ->andWhere('ar.punchOutUserTime IS NOT NULL')
            ->setParameter('empNumber', $employeeNumber)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('ar.punchInUserTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function groupByDay(array $records): array
    {
        $byDay = [];
        foreach ($records as $record) {
            $dayKey = $record->getPunchInUserTime()->format('Y-m-d');
            $byDay[$dayKey][] = [
                'in' => $record->getPunchInUserTime(),
                'out' => $record->getPunchOutUserTime(),
            ];
        }
        return array_values($byDay);
    }

    private function generateEventId(string $cnpj): string
    {
        // e-Social event ID format: ID + tpInsc(1) + nrInsc(14) + timestamp(14) + seq(5)
        $timestamp = (new DateTime())->format('YmdHis');
        $seq = str_pad((string)random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        return 'ID1' . $cnpj . $timestamp . $seq;
    }

    private function getEmployeeCpf(Employee $employee): string
    {
        // CPF is typically stored in ssnNumber for BR deployments
        return preg_replace('/\D/', '', $employee->getSsnNumber() ?? '') ?: '00000000000';
    }

    private function getEmployeePis(Employee $employee): string
    {
        if (method_exists($employee, 'getPisNumber') && !empty($employee->getPisNumber())) {
            return preg_replace('/\D/', '', $employee->getPisNumber());
        }
        return preg_replace('/\D/', '', $employee->getOtherId() ?? '') ?: '000000000000';
    }
}
