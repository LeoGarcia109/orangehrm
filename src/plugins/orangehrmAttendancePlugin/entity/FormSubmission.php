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
 * BR: what was answered.
 *
 * The id is random, never sequential, and an anonymous survey stores no
 * employee, attempt or time: any of those would let it be matched against the
 * list of who answered.
 *
 * @ORM\Table(name="ohrm_br_form_submission")
 * @ORM\Entity
 */
class FormSubmission
{
    /**
     * @ORM\Column(name="id", type="string", length=32)
     * @ORM\Id
     */
    private string $id;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Form")
     * @ORM\JoinColumn(name="form_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private Form $form;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Employee")
     * @ORM\JoinColumn(name="employee_id", referencedColumnName="emp_number", nullable=true, onDelete="SET NULL")
     */
    private ?Employee $employee = null;

    /**
     * @ORM\Column(name="attempt", type="integer", nullable=true)
     */
    private ?int $attempt = null;

    /**
     * @ORM\Column(name="submitted_at", type="datetime", nullable=true)
     */
    private ?DateTime $submittedAt = null;

    /**
     * Doctrine hands decimals back as strings; the accessors speak float.
     *
     * @ORM\Column(name="score_points", type="decimal", precision=6, scale=2, nullable=true)
     */
    private ?string $scorePoints = null;

    /**
     * Doctrine hands decimals back as strings; the accessors speak float.
     *
     * @ORM\Column(name="max_points", type="decimal", precision=6, scale=2, nullable=true)
     */
    private ?string $maxPoints = null;

    /**
     * @ORM\Column(name="status", length=20, type="string")
     */
    private string $status;

    /**
     * @ORM\Column(name="reviewed_by_emp_number", type="integer", nullable=true)
     */
    private ?int $reviewedByEmpNumber = null;

    /**
     * @ORM\Column(name="reviewed_at", type="datetime", nullable=true)
     */
    private ?DateTime $reviewedAt = null;

    /**
     * @ORM\OneToMany(targetEntity="OrangeHRM\Entity\FormAnswer", mappedBy="submission", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var Collection<int, FormAnswer>
     */
    private Collection $answers;

    public function __construct()
    {
        $this->id = bin2hex(random_bytes(16));
        $this->answers = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    public function setForm(Form $form): void
    {
        $this->form = $form;
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): void
    {
        $this->employee = $employee;
    }

    public function getAttempt(): ?int
    {
        return $this->attempt;
    }

    public function setAttempt(?int $attempt): void
    {
        $this->attempt = $attempt;
    }

    public function getSubmittedAt(): ?DateTime
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(?DateTime $submittedAt): void
    {
        $this->submittedAt = $submittedAt;
    }

    public function getScorePoints(): ?float
    {
        return $this->scorePoints === null ? null : (float)$this->scorePoints;
    }

    public function setScorePoints(?float $scorePoints): void
    {
        $this->scorePoints = $scorePoints === null ? null : (string)$scorePoints;
    }

    public function getMaxPoints(): ?float
    {
        return $this->maxPoints === null ? null : (float)$this->maxPoints;
    }

    public function setMaxPoints(?float $maxPoints): void
    {
        $this->maxPoints = $maxPoints === null ? null : (string)$maxPoints;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getReviewedByEmpNumber(): ?int
    {
        return $this->reviewedByEmpNumber;
    }

    public function setReviewedByEmpNumber(?int $reviewedByEmpNumber): void
    {
        $this->reviewedByEmpNumber = $reviewedByEmpNumber;
    }

    public function getReviewedAt(): ?DateTime
    {
        return $this->reviewedAt;
    }

    public function setReviewedAt(?DateTime $reviewedAt): void
    {
        $this->reviewedAt = $reviewedAt;
    }

    /**
     * @return Collection<int, FormAnswer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(FormAnswer $answer): void
    {
        $answer->setSubmission($this);
        $this->answers->add($answer);
    }
}
