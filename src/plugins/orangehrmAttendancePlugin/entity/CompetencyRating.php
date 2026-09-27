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
 * BR: HR's 1-5 rating of one person in one competency. A candidate's
 * rating also points to the employee they became when hired.
 *
 * @ORM\Table(name="ohrm_br_competency_rating")
 * @ORM\Entity
 */
class CompetencyRating
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\JobCompetency")
     * @ORM\JoinColumn(name="competency_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private JobCompetency $competency;

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
     * @ORM\Column(name="rating", type="integer")
     */
    private int $rating;

    /**
     * @ORM\Column(name="rated_by_emp_number", type="integer", nullable=true)
     */
    private ?int $ratedByEmpNumber = null;

    /**
     * @ORM\Column(name="rated_at", type="datetime")
     */
    private DateTime $ratedAt;

    public function __construct()
    {
        $this->ratedAt = new DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCompetency(): JobCompetency
    {
        return $this->competency;
    }

    public function setCompetency(JobCompetency $competency): void
    {
        $this->competency = $competency;
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

    public function getRating(): int
    {
        return $this->rating;
    }

    public function setRating(int $rating): void
    {
        $this->rating = $rating;
    }

    public function getRatedByEmpNumber(): ?int
    {
        return $this->ratedByEmpNumber;
    }

    public function setRatedByEmpNumber(?int $ratedByEmpNumber): void
    {
        $this->ratedByEmpNumber = $ratedByEmpNumber;
    }

    public function getRatedAt(): DateTime
    {
        return $this->ratedAt;
    }

    public function setRatedAt(DateTime $ratedAt): void
    {
        $this->ratedAt = $ratedAt;
    }
}
