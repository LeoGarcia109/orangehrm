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
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\JobCompetency;

/**
 * BR: HR's 1-5 rating of a person in one competency of a job title.
 *
 * PUT /api/v2/attendance/br/competency-ratings/{competencyId} {subject: "c12"|"e5", rating?: 1..5}
 *
 * Without a rating, the rating is cleared.
 */
class CompetencyRatingAPI extends Endpoint implements CrudEndpoint
{
    use EntityManagerHelperTrait;
    use AuthUserTrait;

    public function update(): EndpointResult
    {
        $competency = $this->getEntityManager()->find(JobCompetency::class, $this->getAttributeId());
        $this->throwRecordNotFoundExceptionIfNotExist($competency, JobCompetency::class);

        $params = $this->getRequestParams();
        $subject = $params->getString(RequestParams::PARAM_TYPE_BODY, 'subject');
        $rating = $params->getIntOrNull(RequestParams::PARAM_TYPE_BODY, 'rating');
        try {
            [$person] = JobProfileRules::parseSubjects($subject);
            (new JobFitService())->rate(
                $person['type'],
                $person['id'],
                $competency,
                $rating,
                $this->getAuthUser()->getEmpNumber()
            );
        } catch (JobFitRuleException $e) {
            throw $this->getBadRequestException($e->getMessage());
        }
        return new EndpointResourceResult(ArrayModel::class, [
            'competencyId' => $competency->getId(),
            'subject' => $subject,
            'rating' => $rating,
        ]);
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)),
            new ParamRule('subject', new Rule(Rules::REGEX, ['/^[ce][1-9]\d*$/'])),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule('rating', new Rule(Rules::INT_TYPE), new Rule(Rules::BETWEEN, [1, 5]))
            ),
        );
    }

    public function getAll(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    public function getOne(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
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
