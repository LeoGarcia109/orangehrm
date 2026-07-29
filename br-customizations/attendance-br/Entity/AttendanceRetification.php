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
 * Retification of an attendance record (Portaria SEPRT 673/2021).
 * The original record is never modified; corrections are stored here
 * and linked back to the original via original_record_id.
 *
 * @ORM\Table(name="ohrm_attendance_retification")
 * @ORM\Entity
 */
class AttendanceRetification
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

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
     * @ORM\JoinColumn(name="original_record_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private AttendanceRecord $originalRecord;

    /**
     * @var Employee
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Employee")
     * @ORM\JoinColumn(name="employee_id", referencedColumnName="emp_number", nullable=false, onDelete="CASCADE")
     */
    private Employee $employee;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="rectified_punch_in_utc_time", type="datetime", nullable=true)
     */
    private ?DateTime $rectifiedPunchInUtcTime = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="rectified_punch_in_note", type="string", length=255, nullable=true)
     */
    private ?string $rectifiedPunchInNote = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="rectified_punch_in_time_offset", type="string", length=255, nullable=true)
     */
    private ?string $rectifiedPunchInTimeOffset = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="rectified_punch_in_timezone_name", type="string", length=100, nullable=true)
     */
    private ?string $rectifiedPunchInTimezoneName = null;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="rectified_punch_in_user_time", type="datetime", nullable=true)
     */
    private ?DateTime $rectifiedPunchInUserTime = null;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="rectified_punch_out_utc_time", type="datetime", nullable=true)
     */
    private ?DateTime $rectifiedPunchOutUtcTime = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="rectified_punch_out_note", type="string", length=255, nullable=true)
     */
    private ?string $rectifiedPunchOutNote = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="rectified_punch_out_time_offset", type="string", length=255, nullable=true)
     */
    private ?string $rectifiedPunchOutTimeOffset = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="rectified_punch_out_timezone_name", type="string", length=100, nullable=true)
     */
    private ?string $rectifiedPunchOutTimezoneName = null;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="rectified_punch_out_user_time", type="datetime", nullable=true)
     */
    private ?DateTime $rectifiedPunchOutUserTime = null;

    /**
     * @var string
     *
     * @ORM\Column(name="reason", type="string", length=500, nullable=false)
     */
    private string $reason;

    /**
     * @var int
     *
     * @ORM\Column(name="requested_by_emp_number", type="integer", nullable=false)
     */
    private int $requestedByEmpNumber;

    /**
     * @var int|null
     *
     * @ORM\Column(name="approved_by_emp_number", type="integer", nullable=true)
     */
    private ?int $approvedByEmpNumber = null;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=10, nullable=false)
     */
    private string $status = self::STATUS_PENDING;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private DateTime $createdAt;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="decided_at", type="datetime", nullable=true)
     */
    private ?DateTime $decidedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOriginalRecord(): AttendanceRecord
    {
        return $this->originalRecord;
    }

    public function setOriginalRecord(AttendanceRecord $originalRecord): void
    {
        $this->originalRecord = $originalRecord;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
    }

    public function getRectifiedPunchInUtcTime(): ?DateTime
    {
        return $this->rectifiedPunchInUtcTime;
    }

    public function setRectifiedPunchInUtcTime(?DateTime $rectifiedPunchInUtcTime): void
    {
        $this->rectifiedPunchInUtcTime = $rectifiedPunchInUtcTime;
    }

    public function getRectifiedPunchInNote(): ?string
    {
        return $this->rectifiedPunchInNote;
    }

    public function setRectifiedPunchInNote(?string $rectifiedPunchInNote): void
    {
        $this->rectifiedPunchInNote = $rectifiedPunchInNote;
    }

    public function getRectifiedPunchInTimeOffset(): ?string
    {
        return $this->rectifiedPunchInTimeOffset;
    }

    public function setRectifiedPunchInTimeOffset(?string $rectifiedPunchInTimeOffset): void
    {
        $this->rectifiedPunchInTimeOffset = $rectifiedPunchInTimeOffset;
    }

    public function getRectifiedPunchInTimezoneName(): ?string
    {
        return $this->rectifiedPunchInTimezoneName;
    }

    public function setRectifiedPunchInTimezoneName(?string $rectifiedPunchInTimezoneName): void
    {
        $this->rectifiedPunchInTimezoneName = $rectifiedPunchInTimezoneName;
    }

    public function getRectifiedPunchInUserTime(): ?DateTime
    {
        return $this->rectifiedPunchInUserTime;
    }

    public function setRectifiedPunchInUserTime(?DateTime $rectifiedPunchInUserTime): void
    {
        $this->rectifiedPunchInUserTime = $rectifiedPunchInUserTime;
    }

    public function getRectifiedPunchOutUtcTime(): ?DateTime
    {
        return $this->rectifiedPunchOutUtcTime;
    }

    public function setRectifiedPunchOutUtcTime(?DateTime $rectifiedPunchOutUtcTime): void
    {
        $this->rectifiedPunchOutUtcTime = $rectifiedPunchOutUtcTime;
    }

    public function getRectifiedPunchOutNote(): ?string
    {
        return $this->rectifiedPunchOutNote;
    }

    public function setRectifiedPunchOutNote(?string $rectifiedPunchOutNote): void
    {
        $this->rectifiedPunchOutNote = $rectifiedPunchOutNote;
    }

    public function getRectifiedPunchOutTimeOffset(): ?string
    {
        return $this->rectifiedPunchOutTimeOffset;
    }

    public function setRectifiedPunchOutTimeOffset(?string $rectifiedPunchOutTimeOffset): void
    {
        $this->rectifiedPunchOutTimeOffset = $rectifiedPunchOutTimeOffset;
    }

    public function getRectifiedPunchOutTimezoneName(): ?string
    {
        return $this->rectifiedPunchOutTimezoneName;
    }

    public function setRectifiedPunchOutTimezoneName(?string $rectifiedPunchOutTimezoneName): void
    {
        $this->rectifiedPunchOutTimezoneName = $rectifiedPunchOutTimezoneName;
    }

    public function getRectifiedPunchOutUserTime(): ?DateTime
    {
        return $this->rectifiedPunchOutUserTime;
    }

    public function setRectifiedPunchOutUserTime(?DateTime $rectifiedPunchOutUserTime): void
    {
        $this->rectifiedPunchOutUserTime = $rectifiedPunchOutUserTime;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }

    public function getRequestedByEmpNumber(): int
    {
        return $this->requestedByEmpNumber;
    }

    public function setRequestedByEmpNumber(int $requestedByEmpNumber): void
    {
        $this->requestedByEmpNumber = $requestedByEmpNumber;
    }

    public function getApprovedByEmpNumber(): ?int
    {
        return $this->approvedByEmpNumber;
    }

    public function setApprovedByEmpNumber(?int $approvedByEmpNumber): void
    {
        $this->approvedByEmpNumber = $approvedByEmpNumber;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getDecidedAt(): ?DateTime
    {
        return $this->decidedAt;
    }

    public function setDecidedAt(?DateTime $decidedAt): void
    {
        $this->decidedAt = $decidedAt;
    }

    public function approve(int $approvedByEmpNumber): void
    {
        $this->status = self::STATUS_APPROVED;
        $this->approvedByEmpNumber = $approvedByEmpNumber;
        $this->decidedAt = new DateTime();
    }

    public function reject(int $approvedByEmpNumber): void
    {
        $this->status = self::STATUS_REJECTED;
        $this->approvedByEmpNumber = $approvedByEmpNumber;
        $this->decidedAt = new DateTime();
    }
}
