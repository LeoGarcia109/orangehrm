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

namespace OrangeHRM\Attendance\Menu;

use OrangeHRM\Core\Menu\MenuConfigurator;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\MenuItem;
use OrangeHRM\Entity\Screen;

/**
 * BR: the HR form screens (list, builder, results) sit under their own
 * "Formularios" entry in the side panel, not under Ponto.
 *
 * Their URLs live in the attendance module, and the side panel matches by
 * module, so without this nothing would be highlighted and the top bar would
 * be empty. Returning the "Forms" menu item makes the side panel follow its
 * chain up to the top-level entry (migration 016).
 */
class FormsMenuConfigurator implements MenuConfigurator
{
    use EntityManagerHelperTrait;

    /**
     * @inheritDoc
     */
    public function configure(Screen $screen): ?MenuItem
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from(MenuItem::class, 'm')
            ->innerJoin('m.screen', 's')
            ->where('s.actionUrl = :url')
            ->andWhere('m.level = 2')
            ->setParameter('url', 'brForms')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
