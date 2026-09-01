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

use OrangeHRM\Attendance\Service\AbsenceJustificationRules;
use OrangeHRM\Attendance\Service\AbsenceJustificationService;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CollectionEndpoint;
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
use OrangeHRM\Entity\AbsenceJustification;
use OrangeHRM\Entity\Employee;

/**
 * BR: absences the employee explained, and HR settling them.
 *
 * GET  /api/v2/attendance/br/absences            - the caller's own requests
 * GET  /api/v2/attendance/br/absences?queue=1    - the HR queue (Admin/Supervisor)
 * POST /api/v2/attendance/br/absences            - file one, with its document
 * PUT  /api/v2/attendance/br/absences            - settle one (Admin/Supervisor)
 */
class AbsenceJustificationAPI extends Endpoint implements CollectionEndpoint
{
    use EntityManagerHelperTrait;
    use AuthUserTrait;

    public const PARAMETER_ID = 'id';
    public const PARAMETER_REASON_TYPE = 'reasonType';
    public const PARAMETER_FROM_DATE = 'fromDate';
    public const PARAMETER_TO_DATE = 'toDate';
    public const PARAMETER_NOTE = 'note';
    public const PARAMETER_ATTACHMENT = 'attachment';
    public const PARAMETER_FILENAME = 'filename';
    public const PARAMETER_QUEUE = 'queue';
    public const PARAMETER_STATUS = 'status';
    public const PARAMETER_DECISION = 'decision';
    public const PARAMETER_DECISION_NOTE = 'decisionNote';

    public const PARAM_RULE_NOTE_MAX_LENGTH = 2000;
    public const PARAM_RULE_FILENAME_MAX_LENGTH = 200;

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $service = new AbsenceJustificationService();
        $queue = $this->getRequestParams()->getBooleanOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_QUEUE
        );

        if ($queue === true) {
            $status = $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                self::PARAMETER_STATUS
            );
            $justifications = $service->getByStatus($status);
        } else {
            $employee = $this->getCurrentEmployee();
            $justifications = $employee instanceof Employee ? $service->getForEmployee($employee) : [];
        }

        $items = array_map(
            fn (AbsenceJustification $j) => $this->present($j, $service),
            $justifications
        );

        return new EndpointCollectionResult(
            ArrayModel::class,
            $items,
            new ParameterBag([
                CommonParams::PARAMETER_TOTAL => count($items),
                'pending' => count(array_filter(
                    $items,
                    fn ($i) => $i['status'] === AbsenceJustificationRules::STATUS_PENDING
                )),
                'reasonTypes' => AbsenceJustificationRules::reasonTypes(),
            ])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_QUEUE, new Rule(Rules::BOOL_VAL))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_STATUS,
                    new Rule(Rules::IN, [[
                        AbsenceJustificationRules::STATUS_PENDING,
                        AbsenceJustificationRules::STATUS_APPROVED,
                        AbsenceJustificationRules::STATUS_REJECTED,
                    ]])
                )
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $employee = $this->getCurrentEmployee();
        if (!$employee instanceof Employee) {
            throw $this->getRecordNotFoundException();
        }

        $base64 = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_ATTACHMENT
        );
        $attachment = $base64 === null || $base64 === '' ? null : [
            'filename' => $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_FILENAME,
                'documento'
            ),
            'fileType' => $this->detectFileType($base64),
            'content' => $this->decodeAttachment($base64),
        ];

        $service = new AbsenceJustificationService();
        $justification = $service->submit(
            $employee,
            $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_REASON_TYPE
            ),
            $this->getRequestParams()->getDateTime(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_FROM_DATE
            ),
            $this->getRequestParams()->getDateTime(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_TO_DATE
            ),
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_NOTE
            ),
            $attachment
        );

        return new EndpointResourceResult(ArrayModel::class, $this->present($justification, $service));
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_REASON_TYPE,
                new Rule(Rules::IN, [AbsenceJustificationRules::reasonTypes()])
            ),
            new ParamRule(self::PARAMETER_FROM_DATE, new Rule(Rules::API_DATE)),
            new ParamRule(self::PARAMETER_TO_DATE, new Rule(Rules::API_DATE)),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_NOTE,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_NOTE_MAX_LENGTH])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_ATTACHMENT, new Rule(Rules::STRING_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_FILENAME,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_FILENAME_MAX_LENGTH])
                )
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_ID
        );
        $justification = $this->getEntityManager()->find(AbsenceJustification::class, $id);
        if (!$justification instanceof AbsenceJustification) {
            throw $this->getRecordNotFoundException();
        }

        $service = new AbsenceJustificationService();
        $service->decide(
            $justification,
            $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_DECISION
            ),
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_DECISION_NOTE
            ),
            $this->getAuthUser()->getEmpNumber()
        );

        return new EndpointResourceResult(ArrayModel::class, $this->present($justification, $service));
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(
                self::PARAMETER_DECISION,
                new Rule(Rules::IN, [[
                    AbsenceJustificationRules::STATUS_APPROVED,
                    AbsenceJustificationRules::STATUS_REJECTED,
                ]])
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_DECISION_NOTE,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_NOTE_MAX_LENGTH])
                )
            ),
        );
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

    /**
     * @return array<string, mixed>
     */
    private function present(AbsenceJustification $j, AbsenceJustificationService $service): array
    {
        $attachment = $service->getAttachment($j);
        return [
            'id' => $j->getId(),
            'employeeId' => $j->getEmployee()->getEmpNumber(),
            'employeeName' => trim(
                $j->getEmployee()->getFirstName() . ' ' . $j->getEmployee()->getLastName()
            ),
            'reasonType' => $j->getReasonType(),
            'fromDate' => $j->getFromDate()->format('Y-m-d'),
            'toDate' => $j->getToDate()->format('Y-m-d'),
            'note' => $j->getNote(),
            'status' => $j->getStatus(),
            'decisionNote' => $j->getDecisionNote(),
            'decidedAt' => $j->getDecidedAt()?->format('Y-m-d H:i'),
            'createdAt' => $j->getCreatedAt()->format('Y-m-d H:i'),
            'hasAttachment' => $attachment !== null,
            'attachmentName' => $attachment?->getFilename(),
        ];
    }

    /**
     * Accepts a bare base64 payload or a data: URI, which is what a camera
     * capture in the browser produces.
     */
    private function decodeAttachment(string $value): string
    {
        if (str_contains($value, ',')) {
            $value = substr($value, strpos($value, ',') + 1);
        }
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            throw $this->getBadRequestException('Documento anexado invalido');
        }
        return $decoded;
    }

    private function detectFileType(string $value): string
    {
        if (preg_match('#^data:([\w/\-.+]+);base64,#', $value, $matches) === 1) {
            return $matches[1];
        }
        return 'application/octet-stream';
    }

    private function getCurrentEmployee(): ?Employee
    {
        $empNumber = $this->getAuthUser()->getEmpNumber();
        return $empNumber === null
            ? null
            : $this->getEntityManager()->find(Employee::class, $empNumber);
    }
}
