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
use OrangeHRM\Attendance\Service\AFDTExporter;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;

/**
 * GET /api/v2/attendance/br/afdt-export?fromDate=2024-01-01&toDate=2024-01-31&empNumber=1
 *
 * Generates the AFDT (Arquivo Fonte de Dados Tratado) file for download.
 */
class AFDTExportAPI extends Endpoint implements ResourceEndpoint
{
    use EntityManagerHelperTrait;

    public const PARAMETER_FROM_DATE = 'fromDate';
    public const PARAMETER_TO_DATE = 'toDate';
    public const PARAMETER_EMP_NUMBER = 'empNumber';

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
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

        $exporter = new AFDTExporter($this->getEntityManager());
        $file = $exporter->generateFile($fromDate, $toDate, $empNumber);

        return new EndpointResourceResult(
            new ParameterBag([
                'filename' => $file['filename'],
                'content' => $file['content'],
                'contentType' => $file['content_type'],
                'generatedAt' => (new DateTime())->format('Y-m-d H:i:s'),
                'period' => [
                    'from' => $fromDate->format('Y-m-d'),
                    'to' => $toDate->format('Y-m-d'),
                ],
            ])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_FROM_DATE, new Rule(Rules::API_DATE)),
            new ParamRule(self::PARAMETER_TO_DATE, new Rule(Rules::API_DATE)),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EMP_NUMBER, new Rule(Rules::POSITIVE))
            ),
        );
    }

    /**
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
