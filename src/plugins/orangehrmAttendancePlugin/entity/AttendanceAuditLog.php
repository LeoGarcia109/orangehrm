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

namespace OrangeHRM\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Audit trail for attendance record changes (Portaria SEPRT 673/2021).
 * Every create/update/delete/rectify action on a punch record is logged here.
 *
 * @ORM\Table(name="ohrm_attendance_audit_log")
 * @ORM\Entity
 */
class AttendanceAuditLog
{
    public const ACTION_CREATE = 'CREATE';
    public const ACTION_UPDATE = 'UPDATE';
    public const ACTION_DELETE = 'DELETE';
    public const ACTION_RECTIFY = 'RECTIFY';
    // BR: punch recorded on somebody else's behalf, bypassing the geofence
    public const ACTION_PROXY_PUNCH = 'PROXY_PUNCH';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @var AttendanceRecord
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\AttendanceRecord")
     * @ORM\JoinColumn(name="attendance_record_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private AttendanceRecord $attendanceRecord;

    /**
     * @var Employee
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Employee")
     * @ORM\JoinColumn(name="employee_id", referencedColumnName="emp_number", nullable=false, onDelete="CASCADE")
     */
    private Employee $employee;

    /**
     * @var string
     *
     * @ORM\Column(name="action", type="string", length=10, nullable=false)
     */
    private string $action;

    /**
     * @var string|null
     *
     * @ORM\Column(name="field_name", type="string", length=64, nullable=true)
     */
    private ?string $fieldName = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="old_value", type="text", nullable=true)
     */
    private ?string $oldValue = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="new_value", type="text", nullable=true)
     */
    private ?string $newValue = null;

    /**
     * @var int|null
     *
     * @ORM\Column(name="changed_by_user_id", type="integer", nullable=true)
     */
    private ?int $changedByUserId = null;

    /**
     * @var int|null
     *
     * @ORM\Column(name="changed_by_emp_number", type="integer", nullable=true)
     */
    private ?int $changedByEmpNumber = null;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="changed_at", type="datetime", nullable=false)
     */
    private DateTime $changedAt;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ip_address", type="string", length=45, nullable=true)
     */
    private ?string $ipAddress = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="user_agent", type="string", length=255, nullable=true)
     */
    private ?string $userAgent = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="note", type="string", length=255, nullable=true)
     */
    private ?string $note = null;

    public function __construct()
    {
        $this->changedAt = new DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAttendanceRecord(): AttendanceRecord
    {
        return $this->attendanceRecord;
    }

    public function setAttendanceRecord(AttendanceRecord $attendanceRecord): void
    {
        $this->attendanceRecord = $attendanceRecord;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    public function setFieldName(?string $fieldName): void
    {
        $this->fieldName = $fieldName;
    }

    public function getOldValue(): ?string
    {
        return $this->oldValue;
    }

    public function setOldValue(?string $oldValue): void
    {
        $this->oldValue = $oldValue;
    }

    public function getNewValue(): ?string
    {
        return $this->newValue;
    }

    public function setNewValue(?string $newValue): void
    {
        $this->newValue = $newValue;
    }

    public function getChangedByUserId(): ?int
    {
        return $this->changedByUserId;
    }

    public function setChangedByUserId(?int $changedByUserId): void
    {
        $this->changedByUserId = $changedByUserId;
    }

    public function getChangedByEmpNumber(): ?int
    {
        return $this->changedByEmpNumber;
    }

    public function setChangedByEmpNumber(?int $changedByEmpNumber): void
    {
        $this->changedByEmpNumber = $changedByEmpNumber;
    }

    public function getChangedAt(): DateTime
    {
        return $this->changedAt;
    }

    public function setChangedAt(DateTime $changedAt): void
    {
        $this->changedAt = $changedAt;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }
}
