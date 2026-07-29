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

use OrangeHRM\Attendance\Service\ESocialEventGenerator;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CollectionEndpoint;
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
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;

/**
 * e-Social event management API.
 *
 * POST /api/v2/attendance/br/esocial/generate - generate S-1200 or S-1210 event
 * GET  /api/v2/attendance/br/esocial/events   - list generated events
 */
class ESocialEventAPI extends Endpoint implements CollectionEndpoint
{
    use EntityManagerHelperTrait;

    public const PARAMETER_EMP_NUMBER = 'empNumber';
    public const PARAMETER_EVENT_TYPE = 'eventType';
    public const PARAMETER_REFERENCE_PERIOD = 'referencePeriod';
    public const PARAMETER_NET_PAY = 'netPay';
    public const PARAMETER_PAYMENT_DATE = 'paymentDate';
    public const PARAMETER_STATUS = 'status';

    /**
     * List generated e-Social events.
     *
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $empNumber = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_EMP_NUMBER
        );
        $eventType = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_EVENT_TYPE
        );
        $status = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_STATUS
        );
        $referencePeriod = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_REFERENCE_PERIOD
        );

        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT id, employee_id, event_type, reference_period, status,
                       sent_at, acknowledged_at, error_message, created_at
                FROM ohrm_br_esocial_event WHERE 1=1';
        $params = [];

        if ($empNumber !== null) {
            $sql .= ' AND employee_id = ?';
            $params[] = $empNumber;
        }
        if ($eventType !== null) {
            $sql .= ' AND event_type = ?';
            $params[] = strtoupper($eventType);
        }
        if ($status !== null) {
            $sql .= ' AND status = ?';
            $params[] = strtoupper($status);
        }
        if ($referencePeriod !== null) {
            $sql .= ' AND reference_period = ?';
            $params[] = $referencePeriod;
        }

        $sql .= ' ORDER BY created_at DESC LIMIT 100';

        $result = $conn->executeQuery($sql, $params);
        $events = $result->fetchAllAssociative();

        return new EndpointCollectionResult(
            ArrayModel::class,
            [$events],
            new ParameterBag([CommonParams::PARAMETER_TOTAL => count($events)])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EMP_NUMBER, new Rule(Rules::POSITIVE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EVENT_TYPE, new Rule(Rules::IN, [['S-1200', 'S-1210', 's-1200', 's-1210']]))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_STATUS, new Rule(Rules::IN, [['DRAFT', 'READY', 'SENT', 'ACKNOWLEDGED', 'ERROR']]))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_REFERENCE_PERIOD, new Rule(Rules::LENGTH, [7, 7]))
            ),
        );
    }

    /**
     * Generate an e-Social event (S-1200 or S-1210).
     *
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $empNumber = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_EMP_NUMBER
        );
        $eventType = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_EVENT_TYPE
        );
        $referencePeriod = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_REFERENCE_PERIOD
        );

        $generator = new ESocialEventGenerator($this->getEntityManager());

        $eventType = strtoupper($eventType);
        if ($eventType === 'S-1200') {
            $result = $generator->generateS1200($empNumber, $referencePeriod);
        } elseif ($eventType === 'S-1210') {
            $netPay = $this->getRequestParams()->getFloat(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_NET_PAY
            );
            $paymentDate = $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_PAYMENT_DATE
            );
            $result = $generator->generateS1210($empNumber, $referencePeriod, $netPay, $paymentDate);
        } else {
            throw $this->getBadRequestException('eventType must be S-1200 or S-1210');
        }

        return new EndpointResourceResult(ArrayModel::class, $result);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_EMP_NUMBER, new Rule(Rules::POSITIVE)),
            new ParamRule(self::PARAMETER_EVENT_TYPE, new Rule(Rules::IN, [['S-1200', 'S-1210', 's-1200', 's-1210']])),
            new ParamRule(self::PARAMETER_REFERENCE_PERIOD, new Rule(Rules::LENGTH, [7, 7])),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_NET_PAY, new Rule(Rules::POSITIVE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_PAYMENT_DATE, new Rule(Rules::API_DATE))
            ),
        );
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
