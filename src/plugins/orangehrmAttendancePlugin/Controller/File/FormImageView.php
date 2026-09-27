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

namespace OrangeHRM\Attendance\Controller\File;

use OrangeHRM\Attendance\Api\FormAPI;
use OrangeHRM\Attendance\Service\Form\FormService;
use OrangeHRM\Attendance\Service\Form\FormTypes;
use OrangeHRM\Core\Controller\AbstractFileController;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\FormImage;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;

/**
 * BR: an image from a form question, shown inline in <img>.
 *
 * Whoever may build forms sees any of them; anybody else only the images of a
 * form that was published to them -- a draft's pictures are not out yet.
 */
class FormImageView extends AbstractFileController
{
    use AuthUserTrait;
    use EntityManagerHelperTrait;
    use UserRoleManagerTrait;

    public function handle(Request $request): Response
    {
        $response = $this->getResponse();

        $id = $request->attributes->get('id');
        $image = $id === null ? null : $this->getEntityManager()->find(FormImage::class, (int)$id);
        if (!$image instanceof FormImage || !$this->mayView($image)) {
            return $this->handleBadRequest($response);
        }

        $content = $image->getContent();
        $response->setContent($content);
        $response->headers->set('Content-Type', $image->getFileType());
        $response->headers->set('Content-Length', (string)strlen($content));
        $response->headers->set('Content-Disposition', 'inline');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'private, max-age=3600');

        return $response;
    }

    private function mayView(FormImage $image): bool
    {
        if ($this->getUserRoleManager()->getApiPermissions(FormAPI::class)->canRead()) {
            return true;
        }

        $form = $image->getForm();
        if ($form->getStatus() === FormTypes::STATUS_DRAFT || $form->isTemplate()) {
            return false;
        }
        $empNumber = $this->getAuthUser()->getEmpNumber();
        $employee = $empNumber === null ? null : $this->getEntityManager()->find(Employee::class, $empNumber);

        return $employee instanceof Employee && (new FormService())->reaches($form, $employee);
    }
}
