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
use DateTime;

/**
 * BR: the document that backs a justified absence.
 *
 * Stored as a blob in the database, the same way hs_hr_emp_attachment already
 * does -- so a database backup carries the certificates with it, instead of
 * leaving them on a filesystem nobody remembers to back up.
 *
 * @ORM\Table(
 *     name="ohrm_br_absence_attachment",
 *     indexes={
 *         @ORM\Index(name="idx_attachment_justification", columns={"justification_id"}),
 *     }
 * )
 * @ORM\Entity
 */
class AbsenceAttachment
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\AbsenceJustification")
     * @ORM\JoinColumn(name="justification_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private AbsenceJustification $justification;

    /**
     * @ORM\Column(name="filename", type="string", length=200)
     */
    private string $filename;

    /**
     * @ORM\Column(name="file_type", type="string", length=100)
     */
    private string $fileType;

    /**
     * @ORM\Column(name="file_size", type="integer")
     */
    private int $fileSize;

    /**
     * @ORM\Column(name="content", type="blob")
     */
    private $content;

    /**
     * @ORM\Column(name="uploaded_at", type="datetime")
     */
    private DateTime $uploadedAt;

    public function __construct()
    {
        $this->uploadedAt = new DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getJustification(): AbsenceJustification
    {
        return $this->justification;
    }

    public function setJustification(AbsenceJustification $justification): void
    {
        $this->justification = $justification;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    public function getFileType(): string
    {
        return $this->fileType;
    }

    public function setFileType(string $fileType): void
    {
        $this->fileType = $fileType;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    /**
     * Doctrine hands blobs back as a stream resource on read, and takes a
     * string on write, so the property stays untyped and the getter normalises.
     */
    public function getContent(): string
    {
        if (is_resource($this->content)) {
            $content = stream_get_contents($this->content);
            rewind($this->content);
            return $content === false ? '' : $content;
        }
        return (string)$this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getUploadedAt(): DateTime
    {
        return $this->uploadedAt;
    }
}
