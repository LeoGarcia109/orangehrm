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
 * BR: the ideal profile of a native job title -- a range and an importance
 * per behavioural factor, the competencies, and how much of the fit the
 * behavioural part carries.
 *
 * @ORM\Table(name="ohrm_br_job_profile")
 * @ORM\Entity
 */
class JobProfile
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\OneToOne(targetEntity="OrangeHRM\Entity\JobTitle")
     * @ORM\JoinColumn(name="job_title_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private JobTitle $jobTitle;

    /**
     * @ORM\Column(name="behavior_weight", type="integer")
     */
    private int $behaviorWeight = 50;

    /**
     * @ORM\Column(name="updated_by_emp_number", type="integer", nullable=true)
     */
    private ?int $updatedByEmpNumber = null;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private DateTime $updatedAt;

    /**
     * @ORM\OneToMany(targetEntity="OrangeHRM\Entity\JobProfileFactor", mappedBy="jobProfile", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var Collection<int, JobProfileFactor>
     */
    private Collection $factors;

    /**
     * @ORM\OneToMany(targetEntity="OrangeHRM\Entity\JobCompetency", mappedBy="jobProfile", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"sortOrder" = "ASC", "id" = "ASC"})
     *
     * @var Collection<int, JobCompetency>
     */
    private Collection $competencies;

    public function __construct()
    {
        $this->updatedAt = new DateTime();
        $this->factors = new ArrayCollection();
        $this->competencies = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getJobTitle(): JobTitle
    {
        return $this->jobTitle;
    }

    public function setJobTitle(JobTitle $jobTitle): void
    {
        $this->jobTitle = $jobTitle;
    }

    public function getBehaviorWeight(): int
    {
        return $this->behaviorWeight;
    }

    public function setBehaviorWeight(int $behaviorWeight): void
    {
        $this->behaviorWeight = $behaviorWeight;
    }

    public function getUpdatedByEmpNumber(): ?int
    {
        return $this->updatedByEmpNumber;
    }

    public function setUpdatedByEmpNumber(?int $updatedByEmpNumber): void
    {
        $this->updatedByEmpNumber = $updatedByEmpNumber;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return Collection<int, JobProfileFactor>
     */
    public function getFactors(): Collection
    {
        return $this->factors;
    }

    public function addFactor(JobProfileFactor $factor): void
    {
        $factor->setJobProfile($this);
        $this->factors->add($factor);
    }

    /**
     * @return Collection<int, JobCompetency>
     */
    public function getCompetencies(): Collection
    {
        return $this->competencies;
    }

    public function addCompetency(JobCompetency $competency): void
    {
        $competency->setJobProfile($this);
        $this->competencies->add($competency);
    }

    public function removeCompetency(JobCompetency $competency): void
    {
        $this->competencies->removeElement($competency);
    }
}
