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

use OrangeHRM\Attendance\Service\AbsenceJustificationService;
use OrangeHRM\Core\Controller\AbstractFileController;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Core\Traits\Service\TextHelperTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\AbsenceJustification;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;

/**
 * BR: the document attached to a justified absence.
 *
 * Served as a file rather than through the REST stack: it is a photo or a PDF
 * of somebody's medical certificate, and the browser has to display or save it,
 * not render base64.
 *
 * A certificate is health information about a named person, so access is
 * narrower than the rest of the module: the employee who filed it, or somebody
 * whose role already lets them see that employee's records.
 */
class AbsenceDocumentDownload extends AbstractFileController
{
    use AuthUserTrait;
    use EntityManagerHelperTrait;
    use TextHelperTrait;
    use UserRoleManagerTrait;

    public function handle(Request $request): Response
    {
        $response = $this->getResponse();

        $id = $request->attributes->get('id');
        $justification = $id === null
            ? null
            : $this->getEntityManager()->find(AbsenceJustification::class, (int)$id);

        if (!$justification instanceof AbsenceJustification) {
            return $this->handleBadRequest($response);
        }

        if (!$this->mayRead($justification)) {
            return $this->handleBadRequest($response);
        }

        $service = new AbsenceJustificationService();
        $document = $service->getAttachment($justification);
        if ($document === null) {
            return $this->handleBadRequest($response);
        }

        $content = $document->getContent();
        $this->setCommonHeadersToResponse(
            $document->getFilename(),
            $document->getFileType(),
            $this->getTextHelper()->strLength($content, '8bit'),
            $response
        );
        $response->setContent($content);

        return $response;
    }

    private function mayRead(AbsenceJustification $justification): bool
    {
        $owner = $justification->getEmployee()->getEmpNumber();
        if ($this->getAuthUser()->getEmpNumber() === $owner) {
            return true;
        }
        return in_array(
            $owner,
            $this->getUserRoleManager()->getAccessibleEntityIds(Employee::class)
        );
    }
}
