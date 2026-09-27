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

use OrangeHRM\Attendance\Exception\FormRuleException;
use OrangeHRM\Attendance\Service\Form\FormResultService;
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
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\Form;
use OrangeHRM\Entity\FormSubmission;

/**
 * BR: a form's results, for HR.
 *
 * GET  /api/v2/attendance/br/form-results?formId=N                  - summary, per question, per person
 * GET  /api/v2/attendance/br/form-results?formId=N&submissionId=X   - one person's answers and the key
 * PUT  /api/v2/attendance/br/form-results  {submissionId, points}   - grade written answers
 * POST /api/v2/attendance/br/form-results  {formId, employeeId}     - allow another attempt
 *
 * Admin only (migration 014); the people lists still go through the role's
 * accessible employees, like the other HR queues.
 */
class FormResultAPI extends Endpoint implements CrudEndpoint
{
    use EntityManagerHelperTrait;
    use AuthUserTrait;
    use UserRoleManagerTrait;

    public const PARAMETER_FORM_ID = 'formId';
    public const PARAMETER_SUBMISSION_ID = 'submissionId';
    public const PARAMETER_POINTS = 'points';
    public const PARAMETER_EMPLOYEE_ID = 'employeeId';

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $form = $this->findForm($this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_FORM_ID));
        $submissionId = $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_SUBMISSION_ID);
        $service = new FormResultService();

        try {
            $data = $submissionId === null
                ? $service->results($form, $this->getUserRoleManager()->getAccessibleEntityIds(Employee::class))
                : $service->detail($form, $submissionId);
        } catch (FormRuleException $e) {
            throw $this->getBadRequestException($e->getMessage());
        }
        return new EndpointResourceResult(ArrayModel::class, $data);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_FORM_ID, new Rule(Rules::POSITIVE)),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_SUBMISSION_ID, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [32, 32]))
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $submission = $this->getEntityManager()->find(
            FormSubmission::class,
            $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_SUBMISSION_ID)
        );
        $this->throwRecordNotFoundExceptionIfNotExist($submission, FormSubmission::class);

        try {
            $result = (new FormResultService())->review(
                $submission->getForm(),
                $submission->getId(),
                $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_POINTS),
                $this->getAuthUser()->getEmpNumber()
            );
        } catch (FormRuleException $e) {
            throw $this->getBadRequestException($e->getMessage());
        }
        return new EndpointResourceResult(ArrayModel::class, $result);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_SUBMISSION_ID, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [32, 32])),
            new ParamRule(self::PARAMETER_POINTS, new Rule(Rules::ARRAY_TYPE)),
        );
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $form = $this->findForm($this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_FORM_ID));
        $employee = $this->getEntityManager()->find(
            Employee::class,
            $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_EMPLOYEE_ID)
        );
        $this->throwRecordNotFoundExceptionIfNotExist($employee, Employee::class);

        try {
            $result = (new FormResultService())->grantRetake($form, $employee, $this->getAuthUser()->getEmpNumber());
        } catch (FormRuleException $e) {
            throw $this->getBadRequestException($e->getMessage());
        }
        return new EndpointResourceResult(ArrayModel::class, $result);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_FORM_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(self::PARAMETER_EMPLOYEE_ID, new Rule(Rules::POSITIVE)),
        );
    }

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
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

    private function findForm(int $id): Form
    {
        $form = $this->getEntityManager()->find(Form::class, $id);
        $this->throwRecordNotFoundExceptionIfNotExist($form, Form::class);
        return $form;
    }
}
