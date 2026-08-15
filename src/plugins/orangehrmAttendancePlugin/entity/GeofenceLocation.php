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
 * BR: Allowed punch location for a company structure unit (multi-company).
 * subunit_id NULL means "default location" — used when geofence is enabled
 * globally and the employee's unit has no specific locations.
 *
 * @ORM\Table(
 *     name="ohrm_attendance_geofence_location",
 *     indexes={
 *         @ORM\Index(name="idx_geofence_subunit", columns={"subunit_id"}),
 *     }
 * )
 * @ORM\Entity
 */
class GeofenceLocation
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @var Subunit|null
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Subunit")
     * @ORM\JoinColumn(name="subunit_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private ?Subunit $subunit = null;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100)
     */
    private string $name;

    /**
     * @var string
     *
     * @ORM\Column(name="latitude", type="decimal", precision=10, scale=8)
     */
    private string $latitude;

    /**
     * @var string
     *
     * @ORM\Column(name="longitude", type="decimal", precision=11, scale=8)
     */
    private string $longitude;

    /**
     * @var int
     *
     * @ORM\Column(name="radius", type="integer")
     */
    private int $radius;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return Subunit|null
     */
    public function getSubunit(): ?Subunit
    {
        return $this->subunit;
    }

    /**
     * @param Subunit|null $subunit
     */
    public function setSubunit(?Subunit $subunit): void
    {
        $this->subunit = $subunit;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getLatitude(): string
    {
        return $this->latitude;
    }

    /**
     * @param string $latitude
     */
    public function setLatitude(string $latitude): void
    {
        $this->latitude = $latitude;
    }

    /**
     * @return string
     */
    public function getLongitude(): string
    {
        return $this->longitude;
    }

    /**
     * @param string $longitude
     */
    public function setLongitude(string $longitude): void
    {
        $this->longitude = $longitude;
    }

    /**
     * @return int
     */
    public function getRadius(): int
    {
        return $this->radius;
    }

    /**
     * @param int $radius
     */
    public function setRadius(int $radius): void
    {
        $this->radius = $radius;
    }
}
