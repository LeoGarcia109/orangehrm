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
 * BR: the range (0-100) and importance of one factor in a job profile.
 *
 * @ORM\Table(name="ohrm_br_job_profile_factor")
 * @ORM\Entity
 */
class JobProfileFactor
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
     * @ORM\Column(name="instrument", type="string", length=10)
     */
    private string $instrument;

    /**
     * @ORM\Column(name="factor", type="string", length=1)
     */
    private string $factor;

    /**
     * @ORM\Column(name="min_score", type="integer")
     */
    private int $minScore;

    /**
     * @ORM\Column(name="max_score", type="integer")
     */
    private int $maxScore;

    /**
     * @ORM\Column(name="weight", type="integer")
     */
    private int $weight;

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

    public function getInstrument(): string
    {
        return $this->instrument;
    }

    public function setInstrument(string $instrument): void
    {
        $this->instrument = $instrument;
    }

    public function getFactor(): string
    {
        return $this->factor;
    }

    public function setFactor(string $factor): void
    {
        $this->factor = $factor;
    }

    public function getMinScore(): int
    {
        return $this->minScore;
    }

    public function setMinScore(int $minScore): void
    {
        $this->minScore = $minScore;
    }

    public function getMaxScore(): int
    {
        return $this->maxScore;
    }

    public function setMaxScore(int $maxScore): void
    {
        $this->maxScore = $maxScore;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): void
    {
        $this->weight = $weight;
    }
}
