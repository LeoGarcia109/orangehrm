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
use OrangeHRM\Attendance\Service\Form\FormTypes;
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
use OrangeHRM\Entity\Candidate;
use OrangeHRM\Entity\CandidateVacancy;
use OrangeHRM\Entity\Vacancy;

/**
 * BR: behavioural-assessment invites, for HR.
 *
 * GET  /api/v2/attendance/br/assessments                    - list (?subjectType, ?status, ?vacancyId)
 * POST /api/v2/attendance/br/assessments {candidateId, vacancyId?}      - invite a candidate, returns the link path
 * POST /api/v2/attendance/br/assessments {scope, subunitId?, employeeId?} - invite employees
 * PUT  /api/v2/attendance/br/assessments/{id} {action: resend|cancel}
 *
 * Admin only (migration 017). The link path is relative: the screen builds
 * the full URL from where it is served, so a proxy in front does not matter.
 */
class AssessmentAPI extends Endpoint implements CrudEndpoint
{
    use EntityManagerHelperTrait;
    use AuthUserTrait;

    public const PUBLIC_PATH = '/recruitmentApply/assessment/';

    public function getAll(): EndpointResult
    {
        $items = (new AssessmentService())->listForHr([
            'subjectType' => $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_QUERY, 'subjectType'),
            'status' => $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_QUERY, 'status'),
            'vacancyId' => $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_QUERY, 'vacancyId'),
        ]);
        return new EndpointCollectionResult(
            ArrayModel::class,
            $items,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => count($items)])
        );
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule('subjectType', new Rule(Rules::IN, [[AssessmentRules::SUBJECT_CANDIDATE, AssessmentRules::SUBJECT_EMPLOYEE]]))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule('status', new Rule(Rules::IN, [[
                    AssessmentRules::STATUS_PENDING,
                    AssessmentRules::STATUS_COMPLETED,
                    AssessmentRules::STATUS_EXPIRED,
                    AssessmentRules::STATUS_CANCELLED,
                ]]))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule('vacancyId', new Rule(Rules::POSITIVE))
            ),
        );
    }

    public function create(): EndpointResult
    {
        $service = new AssessmentService();
        $createdBy = $this->getAuthUser()->getEmpNumber();
        $candidateId = $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_BODY, 'candidateId');

        try {
            if ($candidateId !== null) {
                $candidate = $this->getEntityManager()->find(Candidate::class, $candidateId);
                $this->throwRecordNotFoundExceptionIfNotExist($candidate, Candidate::class);
                [$assessment, $token] = $service->inviteCandidate(
                    $candidate,
                    $this->vacancyFor($candidate),
                    $createdBy,
                    new DateTime()
                );
                return new EndpointResourceResult(ArrayModel::class, [
                    'id' => $assessment->getId(),
                    'path' => self::PUBLIC_PATH . $token,
                    'phone' => $candidate->getContactNumber(),
                    'firstName' => $candidate->getFirstName(),
                ]);
            }

            $scope = $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, 'scope');
            if ($scope === null) {
                throw $this->getBadRequestException('Escolha o candidato ou os funcionarios.');
            }
            $created = $service->inviteEmployees(
                $scope,
                $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_BODY, 'subunitId'),
                $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_BODY, 'employeeId'),
                $createdBy
            );
            return new EndpointResourceResult(ArrayModel::class, ['created' => $created]);
        } catch (AssessmentRuleException $e) {
            throw $this->getBadRequestException($e->getMessage());
        }
    }

    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule('candidateId', new Rule(Rules::POSITIVE))),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule('vacancyId', new Rule(Rules::POSITIVE))),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule('scope', new Rule(Rules::IN, [[FormTypes::SCOPE_NETWORK, FormTypes::SCOPE_SUBUNIT, FormTypes::SCOPE_EMPLOYEE]]))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule('subunitId', new Rule(Rules::POSITIVE))),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule('employeeId', new Rule(Rules::POSITIVE))),
        );
    }

    public function update(): EndpointResult
    {
        $assessment = $this->getEntityManager()->find(Assessment::class, $this->getAttributeId());
        $this->throwRecordNotFoundExceptionIfNotExist($assessment, Assessment::class);
        $service = new AssessmentService();

        try {
            if ($this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, 'action') === 'resend') {
                $token = $service->resend($assessment, new DateTime());
                return new EndpointResourceResult(ArrayModel::class, [
                    'id' => $assessment->getId(),
                    'path' => self::PUBLIC_PATH . $token,
                    'phone' => $assessment->getCandidate()?->getContactNumber(),
                    'firstName' => $assessment->getCandidate()?->getFirstName(),
                ]);
            }
            $service->cancel($assessment);
        } catch (AssessmentRuleException $e) {
            throw $this->getBadRequestException($e->getMessage());
        }
        return new EndpointResourceResult(ArrayModel::class, ['id' => $assessment->getId(), 'status' => $assessment->getStatus()]);
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)),
            new ParamRule('action', new Rule(Rules::IN, [['resend', 'cancel']])),
        );
    }

    public function getOne(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForGetOne(): ParamRuleCollection
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

    /**
     * The vacancy asked for, or else the one the candidate applied to last.
     */
    private function vacancyFor(Candidate $candidate): ?Vacancy
    {
        $vacancyId = $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_BODY, 'vacancyId');
        if ($vacancyId !== null) {
            return $this->getEntityManager()->find(Vacancy::class, $vacancyId);
        }
        $latest = null;
        foreach ($candidate->getCandidateVacancy() as $candidateVacancy) {
            if ($candidateVacancy instanceof CandidateVacancy) {
                $latest = $candidateVacancy->getVacancy();
            }
        }
        return $latest;
    }
}
