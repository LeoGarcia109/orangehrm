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

/**
 * BR: a competency a job title asks for, with its importance and the
 * minimum level (1-5) HR expects.
 *
 * @ORM\Table(name="ohrm_br_job_competency")
 * @ORM\Entity
 */
class JobCompetency
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\JobProfile")
     * @ORM\JoinColumn(name="job_profile_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private JobProfile $jobProfile;

    /**
     * @ORM\Column(name="name", type="string", length=100)
     */
    private string $name;

    /**
     * @ORM\Column(name="weight", type="integer")
     */
    private int $weight;

    /**
     * @ORM\Column(name="min_level", type="integer")
     */
    private int $minLevel = 3;

    /**
     * @ORM\Column(name="sort_order", type="integer")
     */
    private int $sortOrder = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function getJobProfile(): JobProfile
    {
        return $this->jobProfile;
    }

    public function setJobProfile(JobProfile $jobProfile): void
    {
        $this->jobProfile = $jobProfile;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): void
    {
        $this->weight = $weight;
    }

    public function getMinLevel(): int
    {
        return $this->minLevel;
    }

    public function setMinLevel(int $minLevel): void
    {
        $this->minLevel = $minLevel;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }
}
