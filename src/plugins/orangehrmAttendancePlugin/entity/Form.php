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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * BR: a test or survey HR sends to the network, a company/site, or one person.
 *
 * Locked once published -- answers are graded against it -- and changed only by
 * duplicating. See docs/superpowers/specs/2026-09-27-formularios-design.md.
 *
 * @ORM\Table(name="ohrm_br_form")
 * @ORM\Entity
 */
class Form
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(name="title", length=150, type="string")
     */
    private string $title;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private ?string $description = null;

    /**
     * @ORM\Column(name="kind", length=10, type="string")
     */
    private string $kind;

    /**
     * @ORM\Column(name="anonymous", options={"default" : 0}, type="boolean")
     */
    private bool $anonymous = false;

    /**
     * @ORM\Column(name="pass_percent", type="integer", nullable=true)
     */
    private ?int $passPercent = null;

    /**
     * @ORM\Column(name="scope", length=20, type="string")
     */
    private string $scope = 'NETWORK';

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Subunit")
     * @ORM\JoinColumn(name="subunit_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private ?Subunit $subunit = null;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Employee")
     * @ORM\JoinColumn(name="employee_id", referencedColumnName="emp_number", nullable=true, onDelete="SET NULL")
     */
    private ?Employee $employee = null;

    /**
     * @ORM\Column(name="due_at", type="datetime", nullable=true)
     */
    private ?DateTime $dueAt = null;

    /**
     * @ORM\Column(name="status", length=20, type="string")
     */
    private string $status = 'DRAFT';

    /**
     * @ORM\Column(name="is_template", options={"default" : 0}, type="boolean")
     */
    private bool $template = false;

    /**
     * @ORM\Column(name="published_at", type="datetime", nullable=true)
     */
    private ?DateTime $publishedAt = null;

    /**
     * @ORM\Column(name="closed_at", type="datetime", nullable=true)
     */
    private ?DateTime $closedAt = null;

    /**
     * @ORM\Column(name="created_by_emp_number", type="integer", nullable=true)
     */
    private ?int $createdByEmpNumber = null;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private DateTime $createdAt;

    /**
     * @ORM\OneToMany(targetEntity="OrangeHRM\Entity\FormItem", mappedBy="form", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"position"="ASC"})
     *
     * @var Collection<int, FormItem>
     */
    private Collection $items;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->items = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function setKind(string $kind): void
    {
        $this->kind = $kind;
    }

    public function isAnonymous(): bool
    {
        return $this->anonymous;
    }

    public function setAnonymous(bool $anonymous): void
    {
        $this->anonymous = $anonymous;
    }

    public function getPassPercent(): ?int
    {
        return $this->passPercent;
    }

    public function setPassPercent(?int $passPercent): void
    {
        $this->passPercent = $passPercent;
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

    public function getDueAt(): ?DateTime
    {
        return $this->dueAt;
    }

    public function setDueAt(?DateTime $dueAt): void
    {
        $this->dueAt = $dueAt;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function isTemplate(): bool
    {
        return $this->template;
    }

    public function setTemplate(bool $template): void
    {
        $this->template = $template;
    }

    public function getPublishedAt(): ?DateTime
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?DateTime $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }

    public function getClosedAt(): ?DateTime
    {
        return $this->closedAt;
    }

    public function setClosedAt(?DateTime $closedAt): void
    {
        $this->closedAt = $closedAt;
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

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return Collection<int, FormItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(FormItem $item): void
    {
        $item->setForm($this);
        $this->items->add($item);
    }
}
