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

namespace OrangeHRM\Attendance\Traits\Service;

use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\Subunit;

/**
 * BR multi-company: walking from an employee's unit up to the root.
 *
 * The company-level facts (geofence flag, CNPJ) sit on the company unit while
 * employees sit in departments below it, so both have to climb the same chain.
 * Shared so the two never disagree about who an employee belongs to.
 */
trait SubunitChainTrait
{
    use EntityManagerHelperTrait;

    /**
     * The unit and its ancestors, nearest first, ending at the root.
     *
     * Walked as a nested set: an ancestor encloses the node's lft/rgt bounds.
     *
     * @param Subunit $subunit
     * @return Subunit[]
     */
    public function getSubunitChain(Subunit $subunit): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('s')
            ->from(Subunit::class, 's')
            ->where('s.lft <= :lft')
            ->andWhere('s.rgt >= :rgt')
            ->setParameter('lft', $subunit->getLft())
            ->setParameter('rgt', $subunit->getRgt())
            ->orderBy('s.lft', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
