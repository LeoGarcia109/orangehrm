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
use OrangeHRM\Attendance\Exception\FormRuleException;
use OrangeHRM\Attendance\Service\Form\FormSubmissionService;
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
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\Form;

/**
 * BR: sending the answers to a form.
 *
 * POST /api/v2/attendance/br/my-forms/submissions
 *      {formId, answers: [{itemId, optionIds?, text?, scale?, yesNo?}]}
 *
 * Everything is checked again on the server -- audience, deadline, attempts,
 * required questions, option ids -- and graded here, never on the phone.
 */
class FormSubmissionAPI extends Endpoint implements CollectionEndpoint
{
    use EntityManagerHelperTrait;
    use AuthUserTrait;

    public const PARAMETER_FORM_ID = 'formId';
    public const PARAMETER_ANSWERS = 'answers';

    private const ANSWER_KEYS = ['optionIds', 'text', 'scale', 'yesNo'];

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $empNumber = $this->getAuthUser()->getEmpNumber();
        $employee = $empNumber === null ? null : $this->getEntityManager()->find(Employee::class, $empNumber);
        if (!$employee instanceof Employee) {
            throw $this->getForbiddenException();
        }
        $form = $this->getEntityManager()->find(
            Form::class,
            $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_FORM_ID)
        );
        $this->throwRecordNotFoundExceptionIfNotExist($form, Form::class);

        try {
            $result = (new FormSubmissionService())->submit(
                $form,
                $employee,
                $this->indexAnswers($this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_ANSWERS)),
                new DateTime()
            );
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
            new ParamRule(self::PARAMETER_ANSWERS, new Rule(Rules::ARRAY_TYPE)),
        );
    }

    /**
     * The phone sends a list; the rules want it keyed by question. Only the
     * known keys are kept, and a question answered twice is refused rather
     * than silently keeping one of the two.
     *
     * @throws FormRuleException
     */
    private function indexAnswers(array $answers): array
    {
        $indexed = [];
        foreach ($answers as $answer) {
            if (!is_array($answer) || !isset($answer['itemId']) || !is_numeric($answer['itemId'])) {
                throw FormRuleException::form('Resposta sem a questao.');
            }
            $itemId = (int)$answer['itemId'];
            if (isset($indexed[$itemId])) {
                throw FormRuleException::form('Resposta repetida para a mesma questao.');
            }
            $indexed[$itemId] = array_intersect_key($answer, array_flip(self::ANSWER_KEYS));
        }
        return $indexed;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
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
