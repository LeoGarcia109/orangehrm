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
 * BR: one answer row -- one per ticked option, or the text, scale or yes/no.
 *
 * @ORM\Table(name="ohrm_br_form_answer")
 * @ORM\Entity
 */
class FormAnswer
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\FormSubmission")
     * @ORM\JoinColumn(name="submission_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private FormSubmission $submission;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\FormItem")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private FormItem $item;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\FormOption")
     * @ORM\JoinColumn(name="option_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private ?FormOption $option = null;

    /**
     * @ORM\Column(name="text_value", type="text", nullable=true)
     */
    private ?string $textValue = null;

    /**
     * @ORM\Column(name="scale_value", type="integer", nullable=true)
     */
    private ?int $scaleValue = null;

    /**
     * @ORM\Column(name="yes_no_value", type="boolean", nullable=true)
     */
    private ?bool $yesNoValue = null;

    /**
     * Doctrine hands decimals back as strings; the accessors speak float.
     *
     * @ORM\Column(name="points_awarded", type="decimal", precision=5, scale=2, nullable=true)
     */
    private ?string $pointsAwarded = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSubmission(): FormSubmission
    {
        return $this->submission;
    }

    public function setSubmission(FormSubmission $submission): void
    {
        $this->submission = $submission;
    }

    public function getItem(): FormItem
    {
        return $this->item;
    }

    public function setItem(FormItem $item): void
    {
        $this->item = $item;
    }

    public function getOption(): ?FormOption
    {
        return $this->option;
    }

    public function setOption(?FormOption $option): void
    {
        $this->option = $option;
    }

    public function getTextValue(): ?string
    {
        return $this->textValue;
    }

    public function setTextValue(?string $textValue): void
    {
        $this->textValue = $textValue;
    }

    public function getScaleValue(): ?int
    {
        return $this->scaleValue;
    }

    public function setScaleValue(?int $scaleValue): void
    {
        $this->scaleValue = $scaleValue;
    }

    public function getYesNoValue(): ?bool
    {
        return $this->yesNoValue;
    }

    public function setYesNoValue(?bool $yesNoValue): void
    {
        $this->yesNoValue = $yesNoValue;
    }

    public function getPointsAwarded(): ?float
    {
        return $this->pointsAwarded === null ? null : (float)$this->pointsAwarded;
    }

    public function setPointsAwarded(?float $pointsAwarded): void
    {
        $this->pointsAwarded = $pointsAwarded === null ? null : (string)$pointsAwarded;
    }
}
