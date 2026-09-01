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
 * BR: the employee's signature over one month of their own timesheet.
 *
 * Meant to stand up in a labour dispute, so it records more than the fact: the
 * hash binds the exact records that were on screen, and the address and device
 * are kept because "somebody left the session open" is the first thing a
 * signature gets challenged with.
 *
 * @ORM\Table(
 *     name="ohrm_br_timesheet_signature",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(
 *             name="idx_timesheet_signature_unique",
 *             columns={"employee_id", "reference_month"}
 *         )
 *     },
 *     indexes={
 *         @ORM\Index(name="idx_timesheet_signature_month", columns={"reference_month"}),
 *     }
 * )
 * @ORM\Entity
 */
class TimesheetSignature
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
     * @ORM\Column(name="reference_month", type="string", length=7)
     */
    private string $referenceMonth;

    /**
     * @ORM\Column(name="signed_at", type="datetime")
     */
    private DateTime $signedAt;

    /**
     * @ORM\Column(name="signature_hash", type="string", length=64)
     */
    private string $signatureHash;

    /**
     * @ORM\Column(name="record_count", type="integer")
     */
    private int $recordCount = 0;

    /**
     * The total the employee was shown when they signed, kept so the sheet can
     * be reproduced even if the records are later disputed.
     *
     * @ORM\Column(name="total_seconds", type="integer")
     */
    private int $totalSeconds = 0;

    /**
     * @ORM\Column(name="ip_address", type="string", length=45, nullable=true)
     */
    private ?string $ipAddress = null;

    /**
     * @ORM\Column(name="user_agent", type="string", length=255, nullable=true)
     */
    private ?string $userAgent = null;

    public function __construct()
    {
        $this->signedAt = new DateTime();
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

    public function getReferenceMonth(): string
    {
        return $this->referenceMonth;
    }

    public function setReferenceMonth(string $referenceMonth): void
    {
        $this->referenceMonth = $referenceMonth;
    }

    public function getSignedAt(): DateTime
    {
        return $this->signedAt;
    }

    public function getSignatureHash(): string
    {
        return $this->signatureHash;
    }

    public function setSignatureHash(string $signatureHash): void
    {
        $this->signatureHash = $signatureHash;
    }

    public function getRecordCount(): int
    {
        return $this->recordCount;
    }

    public function setRecordCount(int $recordCount): void
    {
        $this->recordCount = $recordCount;
    }

    public function getTotalSeconds(): int
    {
        return $this->totalSeconds;
    }

    public function setTotalSeconds(int $totalSeconds): void
    {
        $this->totalSeconds = $totalSeconds;
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
}
