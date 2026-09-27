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
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\JobTitle;

/**
 * BR: people against a job title's profile -- the fit of each, best first.
 *
 * GET /api/v2/attendance/br/profile-comparison?jobTitleId=3&subjects=c12,e5
 */
class ProfileComparisonAPI extends Endpoint implements CollectionEndpoint
{
    use EntityManagerHelperTrait;

    public function getAll(): EndpointResult
    {
        $params = $this->getRequestParams();
        $jobTitle = $this->getEntityManager()->find(
            JobTitle::class,
            $params->getInt(RequestParams::PARAM_TYPE_QUERY, 'jobTitleId')
        );
        $this->throwRecordNotFoundExceptionIfNotExist($jobTitle, JobTitle::class);

        try {
            $result = (new JobFitService())->compare(
                $jobTitle,
                JobProfileRules::parseSubjects($params->getString(RequestParams::PARAM_TYPE_QUERY, 'subjects'))
            );
        } catch (JobFitRuleException $e) {
            throw $this->getBadRequestException($e->getMessage());
        }
        return new EndpointResourceResult(ArrayModel::class, $result);
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule('jobTitleId', new Rule(Rules::POSITIVE)),
            new ParamRule('subjects', new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [null, 400])),
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
