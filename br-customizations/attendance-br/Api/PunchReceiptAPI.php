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

use DateTime;
use OrangeHRM\Attendance\Service\PunchReceiptService;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\AttendanceRecord;

/**
 * Generates printable punch receipts (comprovante de registro de ponto).
 *
 * GET /api/v2/attendance/br/receipt/{id}         - single record receipt
 * GET /api/v2/attendance/br/receipt/daily?empNumber=1&date=2024-01-15 - all records for a day
 */
class PunchReceiptAPI extends Endpoint implements ResourceEndpoint
{
    use EntityManagerHelperTrait;
    use AuthUserTrait;

    public const PARAMETER_EMP_NUMBER = 'empNumber';
    public const PARAMETER_DATE = 'date';

    /**
     * Get receipt for a single attendance record.
     *
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $recordId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_ID
        );

        $em = $this->getEntityManager();
        $record = $em->find(AttendanceRecord::class, $recordId);
        if ($record === null) {
            throw $this->getRecordNotFoundException();
        }

        // Access control: own record or admin/supervisor
        $this->validateAccess($record);

        $service = new PunchReceiptService($em);
        $html = $service->generateReceiptHtml($record);

        return new EndpointResourceResult(
            ArrayModel::class,
            [
                'recordId' => $recordId,
                'nsr' => $record->getNsr(),
                'html' => $html,
                'contentType' => 'text/html; charset=UTF-8',
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_ID,
                new Rule(Rules::POSITIVE)
            ),
        );
    }

    /**
     * Get daily receipts for an employee.
     *
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $empNumber = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_EMP_NUMBER,
            $this->getAuthUser()->getEmpNumber()
        );
        $date = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_DATE,
            (new DateTime())->format('Y-m-d')
        );

        $service = new PunchReceiptService($this->getEntityManager());
        $html = $service->generateDailyReceipts($empNumber, new DateTime($date));

        return new EndpointResourceResult(
            ArrayModel::class,
            [
                'empNumber' => $empNumber,
                'date' => $date,
                'html' => $html,
                'contentType' => 'text/html; charset=UTF-8',
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_EMP_NUMBER,
                    new Rule(Rules::POSITIVE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_DATE,
                    new Rule(Rules::API_DATE)
                )
            ),
        );
    }

    private function validateAccess(AttendanceRecord $record): void
    {
        $authEmpNumber = $this->getAuthUser()->getEmpNumber();
        $recordEmpNumber = $record->getEmployee()->getEmpNumber();

        // Allow if own record
        if ($authEmpNumber === $recordEmpNumber) {
            return;
        }

        // Allow if user is admin (userId check)
        if ($this->getAuthUser()->getUserRoleName() === 'Admin') {
            return;
        }

        throw $this->getForbiddenException();
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
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
}
