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

use OrangeHRM\Attendance\Service\JobFit\JobFitService;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CollectionEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;

/**
 * BR: who can be compared -- one row per person with a completed profile test.
 *
 * GET /api/v2/attendance/br/profile-people?type=c|e&vacancyId=&subunitId=&name=
 *
 * With a vacancy, meta.jobTitleId is the vacancy's job title, so the
 * comparison can start from it.
 */
class ProfilePeopleAPI extends Endpoint implements CollectionEndpoint
{
    public function getAll(): EndpointResult
    {
        $params = $this->getRequestParams();
        $service = new JobFitService();
        $vacancyId = $params->getIntOrNull(RequestParams::PARAM_TYPE_QUERY, 'vacancyId');
        $rows = $service->people([
            'type' => $params->getStringOrNull(RequestParams::PARAM_TYPE_QUERY, 'type'),
            'vacancyId' => $vacancyId,
            'subunitId' => $params->getIntOrNull(RequestParams::PARAM_TYPE_QUERY, 'subunitId'),
            'name' => $params->getStringOrNull(RequestParams::PARAM_TYPE_QUERY, 'name'),
        ]);
        return new EndpointCollectionResult(
            ArrayModel::class,
            $rows,
            new ParameterBag([
                CommonParams::PARAMETER_TOTAL => count($rows),
                'jobTitleId' => $vacancyId === null ? null : $service->jobTitleIdForVacancy($vacancyId),
            ])
        );
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule('type', new Rule(Rules::IN, [[JobFitService::TYPE_CANDIDATE, JobFitService::TYPE_EMPLOYEE]]))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule('vacancyId', new Rule(Rules::POSITIVE))),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule('subunitId', new Rule(Rules::POSITIVE))),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule('name', new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [null, 100]))
            ),
        );
    }

    public function create(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
