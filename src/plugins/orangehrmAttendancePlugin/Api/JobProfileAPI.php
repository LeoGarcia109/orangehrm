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
use OrangeHRM\Entity\JobTitle;

/**
 * BR: the ideal profile of each job title, for HR.
 *
 * GET /api/v2/attendance/br/job-profiles       - job titles and whether they have a profile
 * GET /api/v2/attendance/br/job-profiles/{id}  - the profile of job title {id} (or the open default)
 * PUT /api/v2/attendance/br/job-profiles/{id} {behaviorWeight, factors[9], competencies[]}
 *
 * Admin only (migration 018).
 */
class JobProfileAPI extends Endpoint implements CrudEndpoint
{
    use EntityManagerHelperTrait;
    use AuthUserTrait;

    public function getAll(): EndpointResult
    {
        $items = (new JobFitService())->jobTitles();
        return new EndpointCollectionResult(
            ArrayModel::class,
            $items,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => count($items)])
        );
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection();
    }

    public function getOne(): EndpointResult
    {
        return new EndpointResourceResult(ArrayModel::class, (new JobFitService())->profileFor($this->jobTitle()));
    }

    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)));
    }

    public function update(): EndpointResult
    {
        $params = $this->getRequestParams();
        try {
            $profile = (new JobFitService())->saveProfile($this->jobTitle(), [
                'behaviorWeight' => $params->getInt(RequestParams::PARAM_TYPE_BODY, 'behaviorWeight'),
                'factors' => $params->getArray(RequestParams::PARAM_TYPE_BODY, 'factors'),
                'competencies' => $params->getArray(RequestParams::PARAM_TYPE_BODY, 'competencies', []),
            ], $this->getAuthUser()->getEmpNumber());
        } catch (JobFitRuleException $e) {
            throw $this->getBadRequestException($e->getMessage());
        }
        return new EndpointResourceResult(ArrayModel::class, $profile);
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)),
            new ParamRule('behaviorWeight', new Rule(Rules::INT_TYPE), new Rule(Rules::BETWEEN, [0, 100])),
            new ParamRule('factors', new Rule(Rules::ARRAY_TYPE)),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule('competencies', new Rule(Rules::ARRAY_TYPE))
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

    private function jobTitle(): JobTitle
    {
        $jobTitle = $this->getEntityManager()->find(JobTitle::class, $this->getAttributeId());
        $this->throwRecordNotFoundExceptionIfNotExist($jobTitle, JobTitle::class);
        if ($jobTitle->isDeleted()) {
            throw $this->getRecordNotFoundException();
        }
        return $jobTitle;
    }
}
