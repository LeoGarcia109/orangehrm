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

namespace OrangeHRM\Attendance\Controller\PublicAssessment;

use OrangeHRM\Core\Controller\AbstractVueController;
use OrangeHRM\Core\Controller\PublicControllerInterface;
use OrangeHRM\Core\Vue\Component;
use OrangeHRM\Core\Vue\Prop;
use OrangeHRM\CorporateBranding\Traits\ThemeServiceTrait;
use OrangeHRM\Framework\Http\Request;

/**
 * BR: the candidate's questionnaire page, outside the login.
 *
 * The page renders for any token: whether the link still works is answered
 * by the public API, with one message for every reason it may not.
 */
class AssessmentPublicViewController extends AbstractVueController implements PublicControllerInterface
{
    use ThemeServiceTrait;

    /**
     * @inheritDoc
     */
    public function preRender(Request $request): void
    {
        $component = new Component('br-assessment-public');
        $component->addProp(new Prop('token', Prop::TYPE_STRING, (string)$request->attributes->get('token', '')));
        $component->addProp(new Prop('applied', Prop::TYPE_BOOLEAN, $request->query->getBoolean('applied')));
        $component->addProp(
            new Prop('banner-src', Prop::TYPE_STRING, $this->getThemeService()->getClientBannerURL($request))
        );
        $this->setComponent($component);
        $this->setTemplate('no_header.html.twig');
    }
}
