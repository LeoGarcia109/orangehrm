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
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Entity\AttendanceAuditLog;
use OrangeHRM\Entity\AttendanceRecord;

/**
 * Records every change to attendance records in the audit trail table.
 * Portaria 673/2021 requires full traceability of punch record modifications.
 */
class AttendanceAuditService
{
    use AuthUserTrait;

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Log the creation of a new attendance record.
     */
    public function logCreate(AttendanceRecord $record, ?string $note = null): void
    {
        $this->persistLog(
            $record,
            AttendanceAuditLog::ACTION_CREATE,
            null,
            null,
            $this->serializeRecord($record),
            $note
        );
    }

    /**
     * BR: log a punch recorded on somebody else's behalf at a company that
     * requires geofence.
     *
     * The fence could not be evaluated for this punch, so the reason goes on
     * its own row rather than being left to be inferred from changed_by not
     * matching the employee.
     */
    public function logProxyPunch(AttendanceRecord $record, string $justification): void
    {
        $this->persistLog(
            $record,
            AttendanceAuditLog::ACTION_PROXY_PUNCH,
            null,
            null,
            $this->serializeRecord($record),
            $justification
        );
    }

    /**
     * Log a field-level update on an attendance record.
     * Call this BEFORE applying the change (so old_value is still available).
     *
     * @param AttendanceRecord $record
     * @param string $fieldName
     * @param mixed $oldValue
     * @param mixed $newValue
     * @param string|null $note Justification for the change
     */
    public function logUpdate(
        AttendanceRecord $record,
        string $fieldName,
        $oldValue,
        $newValue,
        ?string $note = null
    ): void {
        $this->persistLog(
            $record,
            AttendanceAuditLog::ACTION_UPDATE,
            $fieldName,
            $this->valueToString($oldValue),
            $this->valueToString($newValue),
            $note
        );
    }

    /**
     * Log a logical deletion of an attendance record.
     */
    public function logDelete(AttendanceRecord $record, ?string $note = null): void
    {
        $this->persistLog(
            $record,
            AttendanceAuditLog::ACTION_DELETE,
            null,
            $this->serializeRecord($record),
            null,
            $note
        );
    }

    /**
     * Log a rectification request for an attendance record.
     */
    public function logRectify(AttendanceRecord $record, ?string $note = null): void
    {
        $this->persistLog(
            $record,
            AttendanceAuditLog::ACTION_RECTIFY,
            null,
            null,
            null,
            $note ?? 'Retificacao solicitada'
        );
    }

    /**
     * Get the full audit history for a given attendance record.
     *
     * @param int $attendanceRecordId
     * @return AttendanceAuditLog[]
     */
    public function getAuditHistory(int $attendanceRecordId): array
    {
        return $this->em->getRepository(AttendanceAuditLog::class)
            ->findBy(
                ['attendanceRecord' => $attendanceRecordId],
                ['changedAt' => 'ASC']
            );
    }

    /**
     * Get audit entries for an employee within a date range.
     *
     * @param int $employeeNumber
     * @param DateTime $from
     * @param DateTime $to
     * @return AttendanceAuditLog[]
     */
    public function getAuditByEmployeeAndPeriod(int $employeeNumber, DateTime $from, DateTime $to): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('a')
            ->from(AttendanceAuditLog::class, 'a')
            ->where('a.employee = :empNumber')
            ->andWhere('a.changedAt >= :from')
            ->andWhere('a.changedAt <= :to')
            ->setParameter('empNumber', $employeeNumber)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('a.changedAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    private function persistLog(
        AttendanceRecord $record,
        string $action,
        ?string $fieldName,
        ?string $oldValue,
        ?string $newValue,
        ?string $note
    ): void {
        $log = new AttendanceAuditLog();
        $log->setAttendanceRecord($record);
        $log->setEmployee($record->getEmployee());
        $log->setAction($action);
        $log->setFieldName($fieldName);
        $log->setOldValue($oldValue);
        $log->setNewValue($newValue);
        $log->setNote($note);

        // Capture auth context
        try {
            $authUser = $this->getAuthUser();
            $log->setChangedByUserId($authUser->getUserId());
            $log->setChangedByEmpNumber($authUser->getEmpNumber());
        } catch (\Throwable $e) {
            // CLI context or no auth user available
        }

        // Capture request context if available
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $log->setIpAddress($_SERVER['REMOTE_ADDR']);
        }
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $log->setUserAgent(substr($_SERVER['HTTP_USER_AGENT'], 0, 255));
        }

        $this->em->persist($log);
        $this->em->flush();
    }

    private function serializeRecord(AttendanceRecord $record): string
    {
        return json_encode([
            'id' => $record->getId(),
            'punch_in_utc_time' => $record->getPunchInUtcTime()?->format('Y-m-d H:i:s'),
            'punch_in_note' => $record->getPunchInNote(),
            'punch_out_utc_time' => $record->getPunchOutUtcTime()?->format('Y-m-d H:i:s'),
            'punch_out_note' => $record->getPunchOutNote(),
            'state' => $record->getState(),
        ], JSON_UNESCAPED_UNICODE);
    }

    private function valueToString($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof DateTime) {
            return $value->format('Y-m-d H:i:s');
        }
        return (string)$value;
    }
}
