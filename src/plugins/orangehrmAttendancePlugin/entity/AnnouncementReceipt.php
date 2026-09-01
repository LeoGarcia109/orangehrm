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
 * BR: what one employee did with one notice.
 *
 * Read is passive -- the notice was opened. Acknowledged is deliberate: the
 * employee pressed "estou ciente", and the timestamp is what turns "we told
 * them" into something demonstrable.
 *
 * @ORM\Table(
 *     name="ohrm_br_announcement_receipt",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="idx_receipt_unique", columns={"announcement_id", "employee_id"})
 *     },
 *     indexes={
 *         @ORM\Index(name="idx_receipt_employee", columns={"employee_id"}),
 *     }
 * )
 * @ORM\Entity
 */
class AnnouncementReceipt
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Announcement")
     * @ORM\JoinColumn(name="announcement_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private Announcement $announcement;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Employee")
     * @ORM\JoinColumn(name="employee_id", referencedColumnName="emp_number", nullable=false, onDelete="CASCADE")
     */
    private Employee $employee;

    /**
     * @ORM\Column(name="read_at", type="datetime", nullable=true)
     */
    private ?DateTime $readAt = null;

    /**
     * @ORM\Column(name="acknowledged_at", type="datetime", nullable=true)
     */
    private ?DateTime $acknowledgedAt = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAnnouncement(): Announcement
    {
        return $this->announcement;
    }

    public function setAnnouncement(Announcement $announcement): void
    {
        $this->announcement = $announcement;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
    }

    public function getReadAt(): ?DateTime
    {
        return $this->readAt;
    }

    public function setReadAt(?DateTime $readAt): void
    {
        $this->readAt = $readAt;
    }

    public function getAcknowledgedAt(): ?DateTime
    {
        return $this->acknowledgedAt;
    }

    public function setAcknowledgedAt(?DateTime $acknowledgedAt): void
    {
        $this->acknowledgedAt = $acknowledgedAt;
    }
}
