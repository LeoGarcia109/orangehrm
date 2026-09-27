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
 * BR: a behavioural-assessment invite -- for a candidate (public link) or an
 * employee (the app) -- and where its profile lives.
 *
 * Only the sha256 of the candidate link is kept, and it is dropped once the
 * assessment is completed.
 *
 * @ORM\Table(name="ohrm_br_assessment")
 * @ORM\Entity
 */
class Assessment
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(name="subject_type", length=10, type="string")
     */
    private string $subjectType;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Candidate")
     * @ORM\JoinColumn(name="candidate_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private ?Candidate $candidate = null;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Employee")
     * @ORM\JoinColumn(name="employee_id", referencedColumnName="emp_number", nullable=true, onDelete="CASCADE")
     */
    private ?Employee $employee = null;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Vacancy")
     * @ORM\JoinColumn(name="vacancy_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private ?Vacancy $vacancy = null;

    /**
     * @ORM\Column(name="instruments", length=40, type="string")
     */
    private string $instruments;

    /**
     * @ORM\Column(name="status", length=12, type="string")
     */
    private string $status = 'PENDING';

    /**
     * @ORM\Column(name="token_hash", length=64, type="string", nullable=true)
     */
    private ?string $tokenHash = null;

    /**
     * @ORM\Column(name="expires_at", type="datetime", nullable=true)
     */
    private ?DateTime $expiresAt = null;

    /**
     * @ORM\Column(name="consent_at", type="datetime", nullable=true)
     */
    private ?DateTime $consentAt = null;

    /**
     * @ORM\Column(name="consent_ip", length=45, type="string", nullable=true)
     */
    private ?string $consentIp = null;

    /**
     * @ORM\Column(name="consent_user_agent", length=255, type="string", nullable=true)
     */
    private ?string $consentUserAgent = null;

    /**
     * @ORM\Column(name="created_by_emp_number", type="integer", nullable=true)
     */
    private ?int $createdByEmpNumber = null;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private DateTime $createdAt;

    /**
     * @ORM\Column(name="started_at", type="datetime", nullable=true)
     */
    private ?DateTime $startedAt = null;

    /**
     * @ORM\Column(name="completed_at", type="datetime", nullable=true)
     */
    private ?DateTime $completedAt = null;

    /**
     * @ORM\OneToMany(targetEntity="OrangeHRM\Entity\AssessmentAnswer", mappedBy="assessment", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var Collection<int, AssessmentAnswer>
     */
    private Collection $answers;

    /**
     * @ORM\OneToMany(targetEntity="OrangeHRM\Entity\AssessmentResult", mappedBy="assessment", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var Collection<int, AssessmentResult>
     */
    private Collection $results;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->answers = new ArrayCollection();
        $this->results = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSubjectType(): string
    {
        return $this->subjectType;
    }

    public function setSubjectType(string $subjectType): void
    {
        $this->subjectType = $subjectType;
    }

    public function getCandidate(): ?Candidate
    {
        return $this->candidate;
    }

    public function setCandidate(?Candidate $candidate): void
    {
        $this->candidate = $candidate;
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): void
    {
        $this->employee = $employee;
    }

    public function getVacancy(): ?Vacancy
    {
        return $this->vacancy;
    }

    public function setVacancy(?Vacancy $vacancy): void
    {
        $this->vacancy = $vacancy;
    }

    public function getInstruments(): string
    {
        return $this->instruments;
    }

    public function setInstruments(string $instruments): void
    {
        $this->instruments = $instruments;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getTokenHash(): ?string
    {
        return $this->tokenHash;
    }

    public function setTokenHash(?string $tokenHash): void
    {
        $this->tokenHash = $tokenHash;
    }

    public function getExpiresAt(): ?DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?DateTime $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function getConsentAt(): ?DateTime
    {
        return $this->consentAt;
    }

    public function setConsentAt(?DateTime $consentAt): void
    {
        $this->consentAt = $consentAt;
    }

    public function getConsentIp(): ?string
    {
        return $this->consentIp;
    }

    public function setConsentIp(?string $consentIp): void
    {
        $this->consentIp = $consentIp;
    }

    public function getConsentUserAgent(): ?string
    {
        return $this->consentUserAgent;
    }

    public function setConsentUserAgent(?string $consentUserAgent): void
    {
        $this->consentUserAgent = $consentUserAgent;
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

    public function getStartedAt(): ?DateTime
    {
        return $this->startedAt;
    }

    public function setStartedAt(?DateTime $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function getCompletedAt(): ?DateTime
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?DateTime $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    /**
     * @return Collection<int, AssessmentAnswer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(AssessmentAnswer $answer): void
    {
        $answer->setAssessment($this);
        $this->answers->add($answer);
    }

    /**
     * @return Collection<int, AssessmentResult>
     */
    public function getResults(): Collection
    {
        return $this->results;
    }

    public function addResult(AssessmentResult $result): void
    {
        $result->setAssessment($this);
        $this->results->add($result);
    }
}
