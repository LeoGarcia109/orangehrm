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
 * BR: another attempt HR allowed somebody. Allowed = 1 + these.
 *
 * @ORM\Table(name="ohrm_br_form_retake")
 * @ORM\Entity
 */
class FormRetake
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
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Employee")
     * @ORM\JoinColumn(name="employee_id", referencedColumnName="emp_number", nullable=false, onDelete="CASCADE")
     */
    private Employee $employee;

    /**
     * @ORM\Column(name="granted_by_emp_number", type="integer", nullable=true)
     */
    private ?int $grantedByEmpNumber = null;

    /**
     * @ORM\Column(name="granted_at", type="datetime")
     */
    private DateTime $grantedAt;

    public function __construct()
    {
        $this->grantedAt = new DateTime();
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

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
    }

    public function getGrantedByEmpNumber(): ?int
    {
        return $this->grantedByEmpNumber;
    }

    public function setGrantedByEmpNumber(?int $grantedByEmpNumber): void
    {
        $this->grantedByEmpNumber = $grantedByEmpNumber;
    }

    public function getGrantedAt(): DateTime
    {
        return $this->grantedAt;
    }

    public function setGrantedAt(DateTime $grantedAt): void
    {
        $this->grantedAt = $grantedAt;
    }
}
