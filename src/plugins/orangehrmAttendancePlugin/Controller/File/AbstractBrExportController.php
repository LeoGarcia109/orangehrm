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

use DateTime;
use OrangeHRM\Core\Controller\AbstractFileController;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Core\Traits\Service\TextHelperTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;

/**
 * BR: shared plumbing for the Portaria 673/2021 fiscal exports (AFD, AFDT).
 *
 * These are plain-text files handed to a labour inspector, so they are served as
 * attachments rather than through the REST stack -- an EndpointResult would
 * JSON-encode the payload and the browser would render it instead of saving it.
 */
abstract class AbstractBrExportController extends AbstractFileController
{
    use AuthUserTrait;
    use EntityManagerHelperTrait;
    use TextHelperTrait;
    use UserRoleManagerTrait;

    /**
     * Build the export. Returns the `filename`, `content`, `content_type` triple
     * produced by the matching exporter service.
     *
     * @param DateTime $fromDate
     * @param DateTime $toDate
     * @param int|null $empNumber null exports every accessible employee
     * @return array
     */
    abstract protected function generate(DateTime $fromDate, DateTime $toDate, ?int $empNumber): array;

    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        $response = $this->getResponse();

        $fromDate = $this->parseDate($request->query->get('fromDate'));
        $toDate = $this->parseDate($request->query->get('toDate'));
        if (is_null($fromDate) || is_null($toDate) || $fromDate > $toDate) {
            return $this->handleBadRequest($response);
        }

        $empNumber = $request->query->get('empNumber');
        if ($empNumber === null || $empNumber === '') {
            $empNumber = null;
        } elseif (!$this->isAccessibleEmpNumber($empNumber)) {
            // Mirrors the IN_ACCESSIBLE_EMP_NUMBERS rule the REST endpoint used:
            // without it, any authenticated user could read another employee's
            // punch history by editing the query string.
            return $this->handleBadRequest($response);
        } else {
            $empNumber = (int)$empNumber;
        }

        $file = $this->generate($fromDate, $toDate, $empNumber);

        $this->setCommonHeadersToResponse(
            $file['filename'],
            $file['content_type'],
            $this->getTextHelper()->strLength($file['content'], '8bit'),
            $response
        );
        $response->setContent($file['content']);
        return $response;
    }

    /**
     * @param mixed $value
     * @return DateTime|null
     */
    private function parseDate($value): ?DateTime
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return $date instanceof DateTime && $date->format('Y-m-d') === $value ? $date : null;
    }

    /**
     * @param mixed $empNumber
     * @return bool
     */
    private function isAccessibleEmpNumber($empNumber): bool
    {
        if (!(is_numeric($empNumber) && $empNumber > 0)) {
            return false;
        }
        if ($empNumber == $this->getAuthUser()->getEmpNumber()) {
            return true;
        }
        return in_array($empNumber, $this->getUserRoleManager()->getAccessibleEntityIds(Employee::class));
    }
}
