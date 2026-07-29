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
 * Generates a printable punch receipt (comprovante de registro de ponto)
 * for the employee, as required by Portaria 673/2021 art. 74.
 *
 * The receipt contains: employer identification, employee identification,
 * NSR, date/time of the punch, and the record's integrity hash.
 *
 * Output is self-contained HTML that can be printed directly or converted
 * to PDF via wkhtmltopdf / browser print / DOMPDF.
 */
class PunchReceiptService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Generate the HTML receipt for a single attendance record.
     *
     * @param AttendanceRecord $record
     * @return string HTML content (self-contained, printable)
     */
    public function generateReceiptHtml(AttendanceRecord $record): string
    {
        $org = $this->em->getRepository(Organization::class)->findOneBy([]);
        $employee = $record->getEmployee();

        $cnpj = $this->formatCnpj($org?->getTaxId() ?? '');
        $orgName = $org?->getName() ?? 'EMPRESA NAO INFORMADA';
        $empName = trim($employee->getFirstName() . ' ' . $employee->getLastName());
        $empId = $employee->getEmployeeId() ?? '-';
        $pis = method_exists($employee, 'getPisNumber') ? ($employee->getPisNumber() ?? '-') : '-';
        $nsr = $record->getNsr() ?? '-';
        $hash = $this->getRecordHash($record->getId());

        $punchIn = $record->getPunchInUserTime();
        $punchOut = $record->getPunchOutUserTime();

        $punchInStr = $punchIn ? $punchIn->format('d/m/Y H:i') : '-';
        $punchOutStr = $punchOut ? $punchOut->format('d/m/Y H:i') : '-';

        $generatedAt = (new DateTime())->format('d/m/Y H:i:s');

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Comprovante de Registro de Ponto - NSR {$nsr}</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Courier New', monospace; font-size: 12px; width: 80mm; padding: 5mm; }
  .header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 8px; margin-bottom: 8px; }
  .header h1 { font-size: 14px; margin-bottom: 4px; }
  .header p { font-size: 10px; }
  .section { margin-bottom: 8px; }
  .section-title { font-weight: bold; font-size: 11px; border-bottom: 1px solid #ccc; margin-bottom: 4px; }
  .row { display: flex; justify-content: space-between; padding: 2px 0; }
  .row .label { font-weight: bold; }
  .hash { font-size: 9px; word-break: break-all; margin-top: 8px; padding-top: 8px; border-top: 1px dashed #000; }
  .footer { text-align: center; margin-top: 12px; padding-top: 8px; border-top: 1px dashed #000; font-size: 9px; }
  @media print { body { width: 100%; } }
</style>
</head>
<body>
  <div class="header">
    <h1>COMPROVANTE DE REGISTRO DE PONTO</h1>
    <p>Portaria SEPRT 673/2021</p>
  </div>

  <div class="section">
    <div class="section-title">EMPREGADOR</div>
    <div class="row"><span class="label">Nome:</span><span>{$orgName}</span></div>
    <div class="row"><span class="label">CNPJ:</span><span>{$cnpj}</span></div>
  </div>

  <div class="section">
    <div class="section-title">EMPREGADO</div>
    <div class="row"><span class="label">Nome:</span><span>{$empName}</span></div>
    <div class="row"><span class="label">Matricula:</span><span>{$empId}</span></div>
    <div class="row"><span class="label">PIS/NIS:</span><span>{$pis}</span></div>
  </div>

  <div class="section">
    <div class="section-title">REGISTRO</div>
    <div class="row"><span class="label">NSR:</span><span>{$nsr}</span></div>
    <div class="row"><span class="label">Entrada:</span><span>{$punchInStr}</span></div>
    <div class="row"><span class="label">Saida:</span><span>{$punchOutStr}</span></div>
  </div>

  <div class="hash">
    <strong>Hash de integridade (SHA-256):</strong><br>
    {$hash}
  </div>

  <div class="footer">
    Gerado em {$generatedAt}<br>
    Documento eletronico - nao requer assinatura
  </div>
</body>
</html>
HTML;
    }

    /**
     * Generate receipts for all records of an employee on a given date.
     *
     * @param int $employeeNumber
     * @param DateTime $date
     * @return string Combined HTML with all receipts for the day
     */
    public function generateDailyReceipts(int $employeeNumber, DateTime $date): string
    {
        $startOfDay = (clone $date)->setTime(0, 0, 0);
        $endOfDay = (clone $date)->setTime(23, 59, 59);

        $records = $this->em->createQueryBuilder()
            ->select('ar')
            ->from(AttendanceRecord::class, 'ar')
            ->join('ar.employee', 'e')
            ->where('e.empNumber = :empNumber')
            ->andWhere('ar.punchInUserTime >= :start')
            ->andWhere('ar.punchInUserTime <= :end')
            ->setParameter('empNumber', $employeeNumber)
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->orderBy('ar.nsr', 'ASC')
            ->getQuery()
            ->getResult();

        if (empty($records)) {
            return '<html><body><p>Nenhum registro encontrado para esta data.</p></body></html>';
        }

        $receipts = [];
        foreach ($records as $record) {
            $receipts[] = $this->generateReceiptHtml($record);
        }

        // For multiple receipts, wrap in a single page with page breaks
        if (count($receipts) === 1) {
            return $receipts[0];
        }

        $combined = '<html><head><meta charset="UTF-8"><style>';
        $combined .= '@media print { .receipt { page-break-after: always; } .receipt:last-child { page-break-after: avoid; } }';
        $combined .= '</style></head><body>';
        foreach ($receipts as $receipt) {
            // Extract body content from each receipt
            $bodyContent = $this->extractBodyContent($receipt);
            $combined .= '<div class="receipt">' . $bodyContent . '</div>';
        }
        $combined .= '</body></html>';

        return $combined;
    }

    private function extractBodyContent(string $html): string
    {
        if (preg_match('/<body>(.*?)<\/body>/s', $html, $matches)) {
            return $matches[1];
        }
        return $html;
    }

    private function getRecordHash(int $recordId): string
    {
        try {
            $conn = $this->em->getConnection();
            $result = $conn->executeQuery(
                'SELECT record_hash FROM ohrm_attendance_record WHERE id = ?',
                [$recordId]
            );
            $hash = $result->fetchOne();
            return $hash !== false ? (string)$hash : 'NAO ASSINADO';
        } catch (\Throwable $e) {
            return 'INDISPONIVEL';
        }
    }

    private function formatCnpj(string $cnpj): string
    {
        $digits = preg_replace('/\D/', '', $cnpj);
        if (strlen($digits) !== 14) {
            return $cnpj ?: 'NAO INFORMADO';
        }
        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($digits, 0, 2),
            substr($digits, 2, 3),
            substr($digits, 5, 3),
            substr($digits, 8, 4),
            substr($digits, 12, 2)
        );
    }
}
