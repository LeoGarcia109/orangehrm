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

use OrangeHRM\Attendance\Api\FormResultAPI;
use OrangeHRM\Attendance\Service\Form\FormResultService;
use OrangeHRM\Core\Controller\AbstractFileController;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\Form;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;

/**
 * BR: a form's results as a spreadsheet -- the record HR keeps of who was
 * trained and how they did.
 *
 * A file controller skips the screen check, so access is checked here: the
 * same permission as reading results through the API.
 */
class FormResultsCsv extends AbstractFileController
{
    use EntityManagerHelperTrait;
    use UserRoleManagerTrait;

    public function handle(Request $request): Response
    {
        $response = $this->getResponse();
        if (!$this->getUserRoleManager()->getApiPermissions(FormResultAPI::class)->canRead()) {
            return $this->handleBadRequest($response);
        }

        $id = $request->attributes->get('id');
        $form = $id === null ? null : $this->getEntityManager()->find(Form::class, (int)$id);
        if (!$form instanceof Form) {
            return $this->handleBadRequest($response);
        }

        $content = (new FormResultService())->csv($form);
        $slug = trim((string)preg_replace('/[^a-z0-9]+/', '-', strtolower(
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $form->getTitle()) ?: 'formulario'
        )), '-');

        $this->setCommonHeadersToResponse(
            "resultados-{$slug}.csv",
            'text/csv; charset=UTF-8',
            strlen($content),
            $response
        );
        $response->setContent($content);
        return $response;
    }
}
