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

use OrangeHRM\Attendance\Exception\JobFitRuleException;
use OrangeHRM\Attendance\Service\JobFit\JobFitService;
use OrangeHRM\Attendance\Service\JobFit\JobProfileRules;
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
 * BR: a job title's ranges drawn from reference employees. Nothing is saved:
 * the screen fills the form and HR decides.
 *
 * GET /api/v2/attendance/br/job-profile-suggestion?empNumbers=1,2,3
 */
class JobProfileSuggestionAPI extends Endpoint implements CollectionEndpoint
{
    public function getAll(): EndpointResult
    {
        try {
            $rows = (new JobFitService())->suggest(JobProfileRules::parseIds(
                $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_QUERY, 'empNumbers')
            ));
        } catch (JobFitRuleException $e) {
            throw $this->getBadRequestException($e->getMessage());
        }
        return new EndpointCollectionResult(
            ArrayModel::class,
            $rows,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => count($rows)])
        );
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(new ParamRule('empNumbers', new Rule(Rules::STRING_TYPE)));
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
