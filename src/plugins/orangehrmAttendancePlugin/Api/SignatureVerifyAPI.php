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
use OrangeHRM\Attendance\Service\RecordSignatureService;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\CollectionEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\AttendanceRecord;

/**
 * Verify and manage SHA-256 integrity signatures for attendance records.
 *
 * GET  /api/v2/attendance/br/signature/verify?fromDate=...&toDate=... - verify period
 * POST /api/v2/attendance/br/signature/verify - batch-sign unsigned records
 */
class SignatureVerifyAPI extends Endpoint implements CollectionEndpoint
{
    use EntityManagerHelperTrait;

    public const PARAMETER_FROM_DATE = 'fromDate';
    public const PARAMETER_TO_DATE = 'toDate';
    public const PARAMETER_EMP_NUMBER = 'empNumber';
    public const PARAMETER_RECORD_ID = 'recordId';

    /**
     * Verify integrity of records in a period, or a single record.
     *
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $em = $this->getEntityManager();
        $service = new RecordSignatureService($em);

        $recordId = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_RECORD_ID
        );

        // Single record verification
        if ($recordId !== null) {
            $record = $em->find(AttendanceRecord::class, $recordId);
            if ($record === null) {
                throw $this->getRecordNotFoundException();
            }
            $result = $service->verifyRecord($record);
            return new EndpointResourceResult(
                ArrayModel::class,
                [
                    'recordId' => $recordId,
                    'nsr' => $record->getNsr(),
                    'valid' => $result['valid'],
                    'expectedHash' => $result['expected'],
                    'storedHash' => $result['actual'],
                ]
            );
        }

        // Period verification
        $fromDate = $this->getRequestParams()->getDateTime(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_FROM_DATE
        );
        $toDate = $this->getRequestParams()->getDateTime(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_TO_DATE
        );
        $empNumber = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_EMP_NUMBER
        );

        $result = $service->verifyPeriod($fromDate, $toDate, $empNumber);

        return new EndpointResourceResult(
            ArrayModel::class,
            [
                'period' => [
                    'from' => $fromDate->format('Y-m-d'),
                    'to' => $toDate->format('Y-m-d'),
                ],
                'total' => $result['total'],
                'valid' => $result['valid'],
                'invalid' => $result['invalid'],
                'unsigned' => $result['unsigned'],
                'invalidRecordIds' => $result['invalidIds'],
                'integrityStatus' => $result['invalid'] === 0 ? 'INTEGRAL' : 'VIOLATED',
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_RECORD_ID, new Rule(Rules::POSITIVE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_FROM_DATE, new Rule(Rules::API_DATE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_TO_DATE, new Rule(Rules::API_DATE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EMP_NUMBER, new Rule(Rules::POSITIVE))
            ),
        );
    }

    /**
     * Batch-sign all unsigned records in a period.
     *
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        return $this->getOne();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return $this->getValidationRuleForGetOne();
    }

    /**
     * Batch-sign unsigned records (POST).
     *
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $fromDate = $this->getRequestParams()->getDateTime(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_FROM_DATE
        );
        $toDate = $this->getRequestParams()->getDateTime(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_TO_DATE
        );

        $service = new RecordSignatureService($this->getEntityManager());
        $signedCount = $service->signUnsignedRecords($fromDate, $toDate);

        return new EndpointResourceResult(
            ArrayModel::class,
            [
                'signedCount' => $signedCount,
                'period' => [
                    'from' => $fromDate->format('Y-m-d'),
                    'to' => $toDate->format('Y-m-d'),
                ],
                'message' => "{$signedCount} registro(s) assinado(s) com sucesso",
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_FROM_DATE, new Rule(Rules::API_DATE)),
            new ParamRule(self::PARAMETER_TO_DATE, new Rule(Rules::API_DATE)),
        );
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
