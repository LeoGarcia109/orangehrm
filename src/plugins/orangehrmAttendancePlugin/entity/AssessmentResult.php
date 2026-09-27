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
 * BR: one factor of a completed assessment, raw and on 0-100.
 *
 * @ORM\Table(name="ohrm_br_assessment_result")
 * @ORM\Entity
 */
class AssessmentResult
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Assessment")
     * @ORM\JoinColumn(name="assessment_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private Assessment $assessment;

    /**
     * @ORM\Column(name="instrument", length=10, type="string")
     */
    private string $instrument;

    /**
     * @ORM\Column(name="version", length=20, type="string")
     */
    private string $version;

    /**
     * @ORM\Column(name="factor", length=1, type="string")
     */
    private string $factor;

    /**
     * @ORM\Column(name="raw", type="integer")
     */
    private int $raw;

    /**
     * Doctrine hands decimals back as strings; the accessors speak float.
     *
     * @ORM\Column(name="score", type="decimal", precision=4, scale=1)
     */
    private string $score = '0';

    public function getId(): int
    {
        return $this->id;
    }

    public function getAssessment(): Assessment
    {
        return $this->assessment;
    }

    public function setAssessment(Assessment $assessment): void
    {
        $this->assessment = $assessment;
    }

    public function getInstrument(): string
    {
        return $this->instrument;
    }

    public function setInstrument(string $instrument): void
    {
        $this->instrument = $instrument;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function getFactor(): string
    {
        return $this->factor;
    }

    public function setFactor(string $factor): void
    {
        $this->factor = $factor;
    }

    public function getRaw(): int
    {
        return $this->raw;
    }

    public function setRaw(int $raw): void
    {
        $this->raw = $raw;
    }

    public function getScore(): float
    {
        return (float)$this->score;
    }

    public function setScore(float $score): void
    {
        $this->score = (string)$score;
    }
}
