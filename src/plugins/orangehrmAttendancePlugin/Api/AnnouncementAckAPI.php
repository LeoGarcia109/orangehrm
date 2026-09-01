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

namespace OrangeHRM\Attendance\Api;

use OrangeHRM\Attendance\Service\AnnouncementAudience;
use OrangeHRM\Attendance\Service\AnnouncementService;
use OrangeHRM\Core\Api\V2\CollectionEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\Announcement;
use OrangeHRM\Entity\Employee;

/**
 * BR: the employee opening a notice, or pressing "estou ciente".
 *
 * POST /api/v2/attendance/br/announcements/receipt {announcementId, acknowledge}
 *
 * The reach check is repeated here on purpose: the id arrives from the client,
 * and without it anybody could acknowledge -- or silently read -- a notice
 * addressed to another company.
 */
class AnnouncementAckAPI extends Endpoint implements CollectionEndpoint
{
    use EntityManagerHelperTrait;
    use AuthUserTrait;

    public const PARAMETER_ANNOUNCEMENT_ID = 'announcementId';
    public const PARAMETER_ACKNOWLEDGE = 'acknowledge';

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $announcementId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_ANNOUNCEMENT_ID
        );
        $acknowledge = $this->getRequestParams()->getBooleanOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_ACKNOWLEDGE
        ) === true;

        $announcement = $this->getEntityManager()->find(Announcement::class, $announcementId);
        $employee = $this->getCurrentEmployee();
        if (!$announcement instanceof Announcement || !$employee instanceof Employee) {
            throw $this->getRecordNotFoundException();
        }

        $service = new AnnouncementService();
        $reaches = AnnouncementAudience::reaches(
            $announcement->getScope(),
            $announcement->getSubunit()?->getId(),
            $announcement->getEmployee()?->getEmpNumber(),
            $service->getChainIds($employee),
            $employee->getEmpNumber()
        );
        if (!$reaches) {
            throw $this->getRecordNotFoundException();
        }

        $receipt = $acknowledge
            ? $service->acknowledge($announcement, $employee)
            : $service->markRead($announcement, $employee);

        return new EndpointResourceResult(ArrayModel::class, [
            'announcementId' => $announcement->getId(),
            'readAt' => $receipt->getReadAt()?->format('Y-m-d H:i'),
            'acknowledgedAt' => $receipt->getAcknowledgedAt()?->format('Y-m-d H:i'),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_ANNOUNCEMENT_ID, new Rule(Rules::POSITIVE)),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_ACKNOWLEDGE, new Rule(Rules::BOOL_VAL))
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    private function getCurrentEmployee(): ?Employee
    {
        $empNumber = $this->getAuthUser()->getEmpNumber();
        return $empNumber === null
            ? null
            : $this->getEntityManager()->find(Employee::class, $empNumber);
    }
}
