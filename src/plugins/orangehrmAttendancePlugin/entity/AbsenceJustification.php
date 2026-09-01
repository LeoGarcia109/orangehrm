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

use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * BR: an absence the employee explained, waiting for HR to settle it.
 *
 * Deliberately not tied to the AFD: that file records punches, not absences.
 * This is a parallel record, for the timesheet and for whoever decides whether
 * the day is discounted.
 *
 * @ORM\Table(
 *     name="ohrm_br_absence_justification",
 *     indexes={
 *         @ORM\Index(name="idx_justification_employee", columns={"employee_id", "from_date"}),
 *         @ORM\Index(name="idx_justification_status", columns={"status"}),
 *     }
 * )
 * @ORM\Entity
 */
class AbsenceJustification
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Employee")
     * @ORM\JoinColumn(name="employee_id", referencedColumnName="emp_number", nullable=false, onDelete="CASCADE")
     */
    private Employee $employee;

    /**
     * @ORM\Column(name="from_date", type="date")
     */
    private DateTime $fromDate;

    /**
     * @ORM\Column(name="to_date", type="date")
     */
    private DateTime $toDate;

    /**
     * @ORM\Column(name="reason_type", type="string", length=40)
     */
    private string $reasonType = 'OUTRO';

    /**
     * @ORM\Column(name="note", type="text", nullable=true)
     */
    private ?string $note = null;

    /**
     * @ORM\Column(name="status", type="string", length=20)
     */
    private string $status = 'PENDING';

    /**
     * @ORM\Column(name="decided_by_emp_number", type="integer", nullable=true)
     */
    private ?int $decidedByEmpNumber = null;

    /**
     * @ORM\Column(name="decided_at", type="datetime", nullable=true)
     */
    private ?DateTime $decidedAt = null;

    /**
     * @ORM\Column(name="decision_note", type="text", nullable=true)
     */
    private ?string $decisionNote = null;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
    }

    public function getFromDate(): DateTime
    {
        return $this->fromDate;
    }

    public function setFromDate(DateTime $fromDate): void
    {
        $this->fromDate = $fromDate;
    }

    public function getToDate(): DateTime
    {
        return $this->toDate;
    }

    public function setToDate(DateTime $toDate): void
    {
        $this->toDate = $toDate;
    }

    public function getReasonType(): string
    {
        return $this->reasonType;
    }

    public function setReasonType(string $reasonType): void
    {
        $this->reasonType = $reasonType;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getDecidedByEmpNumber(): ?int
    {
        return $this->decidedByEmpNumber;
    }

    public function setDecidedByEmpNumber(?int $decidedByEmpNumber): void
    {
        $this->decidedByEmpNumber = $decidedByEmpNumber;
    }

    public function getDecidedAt(): ?DateTime
    {
        return $this->decidedAt;
    }

    public function setDecidedAt(?DateTime $decidedAt): void
    {
        $this->decidedAt = $decidedAt;
    }

    public function getDecisionNote(): ?string
    {
        return $this->decisionNote;
    }

    public function setDecisionNote(?string $decisionNote): void
    {
        $this->decisionNote = $decisionNote;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
}
