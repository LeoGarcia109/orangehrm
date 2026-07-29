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

use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CollectionEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\AttendanceAuditLog;

/**
 * GET /api/v2/attendance/br/audit-log?empNumber=1&fromDate=2024-01-01&toDate=2024-01-31
 *
 * Lists audit trail entries for attendance records.
 * Admin only - provides full traceability per Portaria 673/2021.
 */
class AttendanceAuditLogAPI extends Endpoint implements CollectionEndpoint
{
    use EntityManagerHelperTrait;

    public const PARAMETER_EMP_NUMBER = 'empNumber';
    public const PARAMETER_RECORD_ID = 'recordId';
    public const PARAMETER_FROM_DATE = 'fromDate';
    public const PARAMETER_TO_DATE = 'toDate';
    public const PARAMETER_ACTION = 'action';

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $empNumber = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_EMP_NUMBER
        );
        $recordId = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_RECORD_ID
        );
        $fromDate = $this->getRequestParams()->getDateTimeOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_FROM_DATE
        );
        $toDate = $this->getRequestParams()->getDateTimeOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_TO_DATE
        );
        $action = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_ACTION
        );

        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('a')
            ->from(AttendanceAuditLog::class, 'a')
            ->orderBy('a.changedAt', 'DESC');

        if ($empNumber !== null) {
            $qb->andWhere('a.employee = :empNumber')
                ->setParameter('empNumber', $empNumber);
        }
        if ($recordId !== null) {
            $qb->andWhere('a.attendanceRecord = :recordId')
                ->setParameter('recordId', $recordId);
        }
        if ($fromDate !== null) {
            $qb->andWhere('a.changedAt >= :fromDate')
                ->setParameter('fromDate', $fromDate);
        }
        if ($toDate !== null) {
            $qb->andWhere('a.changedAt <= :toDate')
                ->setParameter('toDate', $toDate);
        }
        if ($action !== null) {
            $qb->andWhere('a.action = :action')
                ->setParameter('action', strtoupper($action));
        }

        $this->setSortingAndPaginationParams($qb);
        $paginator = $this->getPaginator($qb);

        return new EndpointCollectionResult(
            AttendanceAuditLogModel::class,
            [$paginator->getQuery()->execute()],
            new ParameterBag([CommonParams::PARAMETER_TOTAL => $paginator->count()])
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
                    self::PARAMETER_RECORD_ID,
                    new Rule(Rules::POSITIVE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_FROM_DATE,
                    new Rule(Rules::API_DATE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_TO_DATE,
                    new Rule(Rules::API_DATE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_ACTION,
                    new Rule(Rules::IN, [['CREATE', 'UPDATE', 'DELETE', 'RECTIFY']])
                )
            ),
        );
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
