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
use OrangeHRM\Attendance\Exception\AssessmentRuleException;
use OrangeHRM\Attendance\Service\Assessment\AssessmentRules;
use OrangeHRM\Attendance\Service\Assessment\AssessmentService;
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
use OrangeHRM\Entity\Assessment;
use OrangeHRM\Entity\Employee;

/**
 * BR: the caller's own behavioural questionnaires (employees, in the app).
 *
 * GET /api/v2/attendance/br/my-assessments        - open invites
 * GET /api/v2/attendance/br/my-assessments/{id}   - what the answering screen needs
 * PUT /api/v2/attendance/br/my-assessments/{id}   - {answers: {...}} or {complete: true}
 *
 * Another person's invite answers 404, like one that does not exist.
 */
class MyAssessmentAPI extends Endpoint implements CrudEndpoint
{
    use EntityManagerHelperTrait;
    use AuthUserTrait;

    public function getAll(): EndpointResult
    {
        $employee = $this->currentEmployee();
        $items = $employee === null ? [] : array_map(
            static fn (Assessment $a) => ['id' => $a->getId(), 'createdAt' => $a->getCreatedAt()->format('Y-m-d')],
            (new AssessmentService())->pendingForEmployee($employee, new DateTime())
        );
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
        return new EndpointResourceResult(ArrayModel::class, (new AssessmentService())->state($this->mine()));
    }

    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)));
    }

    public function update(): EndpointResult
    {
        $assessment = $this->mine();
        $service = new AssessmentService();
        try {
            if ($this->getRequestParams()->getBooleanOrNull(RequestParams::PARAM_TYPE_BODY, 'complete') === true) {
                $service->complete($assessment, new DateTime());
                return new EndpointResourceResult(ArrayModel::class, ['completed' => true]);
            }
            $service->saveAnswers(
                $assessment,
                $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, 'answers'),
                new DateTime()
            );
        } catch (AssessmentRuleException $e) {
            throw $this->getBadRequestException($e->getMessage());
        }
        return new EndpointResourceResult(ArrayModel::class, $service->state($assessment));
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule('answers', new Rule(Rules::ARRAY_TYPE))),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule('complete', new Rule(Rules::BOOL_TYPE))),
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

    private function mine(): Assessment
    {
        $assessment = $this->getEntityManager()->find(Assessment::class, $this->getAttributeId());
        $employee = $this->currentEmployee();
        if (!$assessment instanceof Assessment
            || $employee === null
            || $assessment->getSubjectType() !== AssessmentRules::SUBJECT_EMPLOYEE
            || $assessment->getEmployee()?->getEmpNumber() !== $employee->getEmpNumber()) {
            throw $this->getRecordNotFoundException();
        }
        return $assessment;
    }

    private function currentEmployee(): ?Employee
    {
        $empNumber = $this->getAuthUser()->getEmpNumber();
        return $empNumber === null ? null : $this->getEntityManager()->find(Employee::class, $empNumber);
    }
}
