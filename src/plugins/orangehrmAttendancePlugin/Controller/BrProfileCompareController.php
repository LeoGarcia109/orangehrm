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

namespace OrangeHRM\Attendance\Controller;

use OrangeHRM\Core\Controller\AbstractVueController;
use OrangeHRM\Core\Vue\Component;
use OrangeHRM\Core\Vue\Prop;
use OrangeHRM\Framework\Http\Request;

/**
 * BR: people compared against a job title's profile. The selection lives in
 * the address, so a reload or a shared link opens the same comparison.
 */
class BrProfileCompareController extends AbstractVueController
{
    /**
     * @inheritDoc
     */
    public function preRender(Request $request): void
    {
        $component = new Component('br-profile-compare');
        $component->addProp(new Prop('job-title-id', Prop::TYPE_NUMBER, $this->positive($request, 'jobTitleId')));
        $component->addProp(new Prop('vacancy-id', Prop::TYPE_NUMBER, $this->positive($request, 'vacancyId')));
        $component->addProp(new Prop('subjects', Prop::TYPE_STRING, substr((string)$request->query->get('subjects', ''), 0, 400)));
        $this->setComponent($component);
    }

    private function positive(Request $request, string $key): ?int
    {
        $value = (string)$request->query->get($key, '');
        return preg_match('/^[1-9]\d{0,9}$/', $value) ? (int)$value : null;
    }
}
