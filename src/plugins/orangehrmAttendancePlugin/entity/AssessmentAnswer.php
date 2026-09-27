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
 * BR: one statement answered, 1 to 5.
 *
 * @ORM\Table(name="ohrm_br_assessment_answer")
 * @ORM\Entity
 */
class AssessmentAnswer
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
     * @ORM\Column(name="item_code", length=12, type="string")
     */
    private string $itemCode;

    /**
     * @ORM\Column(name="value", type="integer")
     */
    private int $value;

    /**
     * @ORM\Column(name="answered_at", type="datetime")
     */
    private DateTime $answeredAt;

    public function __construct()
    {
        $this->answeredAt = new DateTime();
    }

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

    public function getItemCode(): string
    {
        return $this->itemCode;
    }

    public function setItemCode(string $itemCode): void
    {
        $this->itemCode = $itemCode;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    public function getAnsweredAt(): DateTime
    {
        return $this->answeredAt;
    }

    public function setAnsweredAt(DateTime $answeredAt): void
    {
        $this->answeredAt = $answeredAt;
    }
}
