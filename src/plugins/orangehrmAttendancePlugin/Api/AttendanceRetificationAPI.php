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
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\AttendanceRecord;
use OrangeHRM\Entity\AttendanceRetification;
use OrangeHRM\Entity\Employee;

/**
 * CRUD for attendance retifications (Portaria 673/2021).
 * The original record is never modified; corrections create a retification
 * that must be approved before taking effect.
 *
 * GET    /api/v2/attendance/br/retification          - list retifications
 * POST   /api/v2/attendance/br/retification          - request a retification
 * PUT    /api/v2/attendance/br/retification/{id}/action - approve/reject
 * DELETE /api/v2/attendance/br/retification          - cancel pending retifications
 */
class AttendanceRetificationAPI extends Endpoint implements CrudEndpoint
{
    use EntityManagerHelperTrait;
    use AuthUserTrait;

    public const PARAMETER_RECORD_ID = 'recordId';
    public const PARAMETER_EMP_NUMBER = 'empNumber';
    public const PARAMETER_STATUS = 'status';
    public const PARAMETER_REASON = 'reason';
    public const PARAMETER_PUNCH_IN_DATE = 'punchInDate';
    public const PARAMETER_PUNCH_IN_TIME = 'punchInTime';
    public const PARAMETER_PUNCH_OUT_DATE = 'punchOutDate';
    public const PARAMETER_PUNCH_OUT_TIME = 'punchOutTime';
    public const PARAMETER_PUNCH_IN_NOTE = 'punchInNote';
    public const PARAMETER_PUNCH_OUT_NOTE = 'punchOutNote';
    public const PARAMETER_ACTION = 'retificationAction';

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $empNumber = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_EMP_NUMBER
        );
        $status = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_STATUS
        );

        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('r')
            ->from(AttendanceRetification::class, 'r')
            ->orderBy('r.createdAt', 'DESC');

        if ($empNumber !== null) {
            $qb->andWhere('r.employee = :empNumber')
                ->setParameter('empNumber', $empNumber);
        }
        if ($status !== null) {
            $qb->andWhere('r.status = :status')
                ->setParameter('status', strtoupper($status));
        }

        $this->setSortingAndPaginationParams($qb);
        $paginator = $this->getPaginator($qb);

        return new EndpointCollectionResult(
            ArrayModel::class,
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
                    self::PARAMETER_STATUS,
                    new Rule(Rules::IN, [['PENDING', 'APPROVED', 'REJECTED']])
                )
            ),
        );
    }

    /**
     * Create a retification request.
     *
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $recordId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_RECORD_ID
        );
        $reason = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_REASON
        );

        $em = $this->getEntityManager();
        $originalRecord = $em->find(AttendanceRecord::class, $recordId);
        if ($originalRecord === null) {
            throw $this->getRecordNotFoundException();
        }

        $retification = new AttendanceRetification();
        $retification->setOriginalRecord($originalRecord);
        $retification->setEmployee($originalRecord->getEmployee());
        $retification->setReason($reason);
        $retification->setRequestedByEmpNumber($this->getAuthUser()->getEmpNumber());

        // Set rectified times if provided
        $punchInDate = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_PUNCH_IN_DATE
        );
        $punchInTime = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_PUNCH_IN_TIME
        );
        if ($punchInDate && $punchInTime) {
            $retification->setRectifiedPunchInUserTime(new DateTime("$punchInDate $punchInTime"));
        }

        $punchOutDate = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_PUNCH_OUT_DATE
        );
        $punchOutTime = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_PUNCH_OUT_TIME
        );
        if ($punchOutDate && $punchOutTime) {
            $retification->setRectifiedPunchOutUserTime(new DateTime("$punchOutDate $punchOutTime"));
        }

        $punchInNote = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_PUNCH_IN_NOTE
        );
        if ($punchInNote !== null) {
            $retification->setRectifiedPunchInNote($punchInNote);
        }

        $punchOutNote = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_PUNCH_OUT_NOTE
        );
        if ($punchOutNote !== null) {
            $retification->setRectifiedPunchOutNote($punchOutNote);
        }

        $em->persist($retification);

        // Mark original record as having a pending retification
        $originalRecord->setIsRectified(true);
        $em->persist($originalRecord);
        $em->flush();

        return new EndpointResourceResult(
            ArrayModel::class,
            [
                'id' => $retification->getId(),
                'originalRecordId' => $recordId,
                'status' => $retification->getStatus(),
                'reason' => $reason,
                'createdAt' => $retification->getCreatedAt()->format('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_RECORD_ID,
                new Rule(Rules::POSITIVE),
                new Rule(Rules::ENTITY_ID_EXISTS, [AttendanceRecord::class])
            ),
            new ParamRule(
                self::PARAMETER_REASON,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::LENGTH, [1, 500])
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_PUNCH_IN_DATE, new Rule(Rules::API_DATE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_PUNCH_IN_TIME, new Rule(Rules::TIME, ['H:i']))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_PUNCH_OUT_DATE, new Rule(Rules::API_DATE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_PUNCH_OUT_TIME, new Rule(Rules::TIME, ['H:i']))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_PUNCH_IN_NOTE, new Rule(Rules::STRING_TYPE)),
                true
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_PUNCH_OUT_NOTE, new Rule(Rules::STRING_TYPE)),
                true
            ),
        );
    }

    /**
     * Approve or reject a retification.
     *
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_ID
        );
        $action = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_ACTION
        );

        $em = $this->getEntityManager();
        $retification = $em->find(AttendanceRetification::class, $id);
        if ($retification === null) {
            throw $this->getRecordNotFoundException();
        }

        if ($retification->getStatus() !== AttendanceRetification::STATUS_PENDING) {
            throw $this->getBadRequestException('Retification already decided');
        }

        $approverEmpNumber = $this->getAuthUser()->getEmpNumber();

        if (strtoupper($action) === 'APPROVE') {
            $retification->approve($approverEmpNumber);

            // Apply the rectification to the original record
            $original = $retification->getOriginalRecord();
            if ($retification->getRectifiedPunchInUserTime() !== null) {
                $original->setPunchInUserTime($retification->getRectifiedPunchInUserTime());
            }
            if ($retification->getRectifiedPunchOutUserTime() !== null) {
                $original->setPunchOutUserTime($retification->getRectifiedPunchOutUserTime());
            }
            if ($retification->getRectifiedPunchInNote() !== null) {
                $original->setPunchInNote($retification->getRectifiedPunchInNote());
            }
            if ($retification->getRectifiedPunchOutNote() !== null) {
                $original->setPunchOutNote($retification->getRectifiedPunchOutNote());
            }
            $em->persist($original);
        } elseif (strtoupper($action) === 'REJECT') {
            $retification->reject($approverEmpNumber);

            // If no other pending retifications, unmark the record
            $original = $retification->getOriginalRecord();
            $original->setIsRectified(false);
            $em->persist($original);
        } else {
            throw $this->getBadRequestException('Action must be APPROVE or REJECT');
        }

        $em->persist($retification);
        $em->flush();

        return new EndpointResourceResult(
            ArrayModel::class,
            [
                'id' => $retification->getId(),
                'status' => $retification->getStatus(),
                'decidedAt' => $retification->getDecidedAt()?->format('Y-m-d H:i:s'),
                'decidedBy' => $approverEmpNumber,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_ID,
                new Rule(Rules::POSITIVE)
            ),
            new ParamRule(
                self::PARAMETER_ACTION,
                new Rule(Rules::IN, [['APPROVE', 'REJECT', 'approve', 'reject']])
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        $ids = $this->getRequestParams()->getArray(
            RequestParams::PARAM_TYPE_BODY,
            CommonParams::PARAMETER_IDS
        );

        $em = $this->getEntityManager();
        $deleted = [];
        foreach ($ids as $id) {
            $retification = $em->find(AttendanceRetification::class, (int)$id);
            if ($retification !== null && $retification->getStatus() === AttendanceRetification::STATUS_PENDING) {
                $em->remove($retification);
                $deleted[] = (int)$id;
            }
        }
        $em->flush();

        return new EndpointResourceResult(ArrayModel::class, $deleted);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_IDS,
                new Rule(Rules::ARRAY_TYPE)
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
