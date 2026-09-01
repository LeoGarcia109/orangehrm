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
 * BR: a notice from HR to the network, a company/site, or one person.
 *
 * Reach is decided by AnnouncementAudience, which walks the same unit chain
 * the geofence and the employer CNPJ walk.
 *
 * @ORM\Table(
 *     name="ohrm_br_announcement",
 *     indexes={
 *         @ORM\Index(name="idx_announcement_scope", columns={"scope", "subunit_id", "employee_id"}),
 *         @ORM\Index(name="idx_announcement_published", columns={"published_at"}),
 *     }
 * )
 * @ORM\Entity
 */
class Announcement
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(name="title", type="string", length=150)
     */
    private string $title;

    /**
     * @ORM\Column(name="body", type="text")
     */
    private string $body;

    /**
     * @ORM\Column(name="scope", type="string", length=20)
     */
    private string $scope = 'NETWORK';

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Subunit")
     * @ORM\JoinColumn(name="subunit_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private ?Subunit $subunit = null;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Employee")
     * @ORM\JoinColumn(name="employee_id", referencedColumnName="emp_number", nullable=true, onDelete="CASCADE")
     */
    private ?Employee $employee = null;

    /**
     * @ORM\Column(name="requires_ack", type="boolean", options={"default" : 0})
     */
    private bool $requiresAck = false;

    /**
     * @ORM\Column(name="published_at", type="datetime")
     */
    private DateTime $publishedAt;

    /**
     * Past this moment the notice leaves the inbox.
     *
     * @ORM\Column(name="expires_at", type="datetime", nullable=true)
     */
    private ?DateTime $expiresAt = null;

    /**
     * @ORM\Column(name="created_by_emp_number", type="integer", nullable=true)
     */
    private ?int $createdByEmpNumber = null;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private DateTime $createdAt;

    public function __construct()
    {
        $this->publishedAt = new DateTime();
        $this->createdAt = new DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    public function getSubunit(): ?Subunit
    {
        return $this->subunit;
    }

    public function setSubunit(?Subunit $subunit): void
    {
        $this->subunit = $subunit;
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): void
    {
        $this->employee = $employee;
    }

    public function isRequiresAck(): bool
    {
        return $this->requiresAck;
    }

    public function setRequiresAck(bool $requiresAck): void
    {
        $this->requiresAck = $requiresAck;
    }

    public function getPublishedAt(): DateTime
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(DateTime $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }

    public function getExpiresAt(): ?DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?DateTime $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function getCreatedByEmpNumber(): ?int
    {
        return $this->createdByEmpNumber;
    }

    public function setCreatedByEmpNumber(?int $createdByEmpNumber): void
    {
        $this->createdByEmpNumber = $createdByEmpNumber;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
}
