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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * BR: one block of a form -- a question, or content with no answer.
 *
 * @ORM\Table(name="ohrm_br_form_item")
 * @ORM\Entity
 */
class FormItem
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Form")
     * @ORM\JoinColumn(name="form_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private Form $form;

    /**
     * @ORM\Column(name="position", type="integer")
     */
    private int $position;

    /**
     * @ORM\Column(name="type", length=20, type="string")
     */
    private string $type;

    /**
     * @ORM\Column(name="prompt", type="text")
     */
    private string $prompt;

    /**
     * @ORM\Column(name="help_text", type="text", nullable=true)
     */
    private ?string $helpText = null;

    /**
     * @ORM\Column(name="required", options={"default" : 0}, type="boolean")
     */
    private bool $required = false;

    /**
     * Doctrine hands decimals back as strings; the accessors speak float.
     *
     * @ORM\Column(name="points", type="decimal", precision=5, scale=2)
     */
    private string $points = '0';

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\FormImage")
     * @ORM\JoinColumn(name="image_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private ?FormImage $image = null;

    /**
     * @ORM\Column(name="youtube_id", length=11, type="string", nullable=true)
     */
    private ?string $youtubeId = null;

    /**
     * @ORM\Column(name="correct_yes_no", type="boolean", nullable=true)
     */
    private ?bool $correctYesNo = null;

    /**
     * @ORM\OneToMany(targetEntity="OrangeHRM\Entity\FormOption", mappedBy="item", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"position"="ASC"})
     *
     * @var Collection<int, FormOption>
     */
    private Collection $options;

    public function __construct()
    {
        $this->options = new ArrayCollection();
    }

    public function getId(): int
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

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getPrompt(): string
    {
        return $this->prompt;
    }

    public function setPrompt(string $prompt): void
    {
        $this->prompt = $prompt;
    }

    public function getHelpText(): ?string
    {
        return $this->helpText;
    }

    public function setHelpText(?string $helpText): void
    {
        $this->helpText = $helpText;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): void
    {
        $this->required = $required;
    }

    public function getPoints(): float
    {
        return (float)$this->points;
    }

    public function setPoints(float $points): void
    {
        $this->points = (string)$points;
    }

    public function getImage(): ?FormImage
    {
        return $this->image;
    }

    public function setImage(?FormImage $image): void
    {
        $this->image = $image;
    }

    public function getYoutubeId(): ?string
    {
        return $this->youtubeId;
    }

    public function setYoutubeId(?string $youtubeId): void
    {
        $this->youtubeId = $youtubeId;
    }

    public function getCorrectYesNo(): ?bool
    {
        return $this->correctYesNo;
    }

    public function setCorrectYesNo(?bool $correctYesNo): void
    {
        $this->correctYesNo = $correctYesNo;
    }

    /**
     * @return Collection<int, FormOption>
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(FormOption $option): void
    {
        $option->setItem($this);
        $this->options->add($option);
    }
}
