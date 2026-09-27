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

use OrangeHRM\Attendance\Service\Assessment\AssessmentRules;
use OrangeHRM\Attendance\Service\Assessment\AssessmentService;
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
use OrangeHRM\Entity\Assessment;

/**
 * BR: a behavioural profile, for HR.
 *
 * GET /api/v2/attendance/br/assessment-profile?assessmentId=N
 */
class AssessmentResultAPI extends Endpoint implements CollectionEndpoint
{
    use EntityManagerHelperTrait;

    public function getAll(): EndpointResult
    {
        $assessment = $this->getEntityManager()->find(
            Assessment::class,
            $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_QUERY, 'assessmentId')
        );
        $this->throwRecordNotFoundExceptionIfNotExist($assessment, Assessment::class);
        if ($assessment->getStatus() !== AssessmentRules::STATUS_COMPLETED) {
            throw $this->getBadRequestException('Este perfil ainda nao foi respondido.');
        }
        return new EndpointResourceResult(ArrayModel::class, (new AssessmentService())->profile($assessment));
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(new ParamRule('assessmentId', new Rule(Rules::POSITIVE)));
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
