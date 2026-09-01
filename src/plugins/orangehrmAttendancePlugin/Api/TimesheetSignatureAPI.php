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
use OrangeHRM\Attendance\Exception\AttendanceServiceException;
use OrangeHRM\Attendance\Service\TimesheetSignatureRules;
use OrangeHRM\Attendance\Service\TimesheetSignatureService;
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
use OrangeHRM\Core\Utility\PasswordHash;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\TimesheetSignature;
use OrangeHRM\Entity\User;

/**
 * BR: the employee signing their own month.
 *
 * GET  /api/v2/attendance/br/timesheet-signature?month=2026-08  - my sheet and its state
 * GET  ...?month=2026-08&queue=1                                - who signed (Admin/Supervisor)
 * POST /api/v2/attendance/br/timesheet-signature                - sign, confirming the password
 */
class TimesheetSignatureAPI extends Endpoint implements CollectionEndpoint
{
    use EntityManagerHelperTrait;
    use AuthUserTrait;

    public const PARAMETER_MONTH = 'month';
    public const PARAMETER_PASSWORD = 'password';
    public const PARAMETER_QUEUE = 'queue';

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $month = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_MONTH,
            (new DateTime('first day of last month'))->format('Y-m')
        );
        $queue = $this->getRequestParams()->getBooleanOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_QUEUE
        );

        return $queue === true ? $this->listSigned($month) : $this->mySheet($month);
    }

    private function mySheet(string $month): EndpointResult
    {
        $employee = $this->getCurrentEmployee();
        if (!$employee instanceof Employee) {
            throw $this->getRecordNotFoundException();
        }

        $service = new TimesheetSignatureService();
        $records = $service->getMonthRecords($employee, $month);
        $signature = $service->getSignature($employee, $month);

        // Why the button is disabled matters more than that it is: an employee
        // who cannot sign needs to know whether to close a punch or to wait
        // for the month to end.
        $blocker = null;
        try {
            TimesheetSignatureRules::assertPeriodClosed($month, new DateTime());
            TimesheetSignatureRules::assertSignable(
                array_column($records, 'hash')
            );
        } catch (AttendanceServiceException $e) {
            $blocker = $e->getMessage();
        }

        return new EndpointResourceResult(ArrayModel::class, [
            'month' => $month,
            'recordCount' => count($records),
            'totalSeconds' => (int)array_sum(array_column($records, 'seconds')),
            'records' => $records,
            'signedAt' => $signature?->getSignedAt()->format('d/m/Y H:i'),
            'intact' => $signature === null ? null : $service->isIntact($signature),
            'canSign' => $signature === null && $blocker === null,
            'blocker' => $signature === null ? $blocker : null,
        ]);
    }

    private function listSigned(string $month): EndpointResult
    {
        $service = new TimesheetSignatureService();
        $items = array_map(
            fn (TimesheetSignature $s) => [
                'employeeId' => $s->getEmployee()->getEmpNumber(),
                'employeeName' => trim(
                    $s->getEmployee()->getFirstName() . ' ' . $s->getEmployee()->getLastName()
                ),
                'month' => $s->getReferenceMonth(),
                'signedAt' => $s->getSignedAt()->format('d/m/Y H:i'),
                'recordCount' => $s->getRecordCount(),
                'totalSeconds' => $s->getTotalSeconds(),
                'ipAddress' => $s->getIpAddress(),
                'intact' => $service->isIntact($s),
            ],
            $service->getSignaturesForMonth($month)
        );

        return new EndpointCollectionResult(
            ArrayModel::class,
            $items,
            new ParameterBag([
                CommonParams::PARAMETER_TOTAL => count($items),
                'month' => $month,
                'broken' => count(array_filter($items, fn ($i) => !$i['intact'])),
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
                new ParamRule(self::PARAMETER_MONTH, new Rule(Rules::STRING_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_QUEUE, new Rule(Rules::BOOL_VAL))
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

        $this->assertPasswordConfirmed(
            $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_PASSWORD
            )
        );

        $signature = (new TimesheetSignatureService())->sign(
            $employee,
            $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_MONTH
            ),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );

        return new EndpointResourceResult(ArrayModel::class, [
            'month' => $signature->getReferenceMonth(),
            'signedAt' => $signature->getSignedAt()->format('d/m/Y H:i'),
            'recordCount' => $signature->getRecordCount(),
            'totalSeconds' => $signature->getTotalSeconds(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_MONTH, new Rule(Rules::STRING_TYPE)),
            new ParamRule(self::PARAMETER_PASSWORD, new Rule(Rules::STRING_TYPE)),
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
     * Re-typing the password is what makes this a deliberate act rather than a
     * click: "somebody left my session open" is the first thing a signature
     * gets challenged with.
     *
     * @throws AttendanceServiceException when the password does not match
     */
    private function assertPasswordConfirmed(string $password): void
    {
        $user = $this->getEntityManager()->find(User::class, $this->getAuthUser()->getUserId());
        $hash = $user?->getUserPassword();

        if ($hash === null || !(new PasswordHash())->verify($password, $hash)) {
            throw AttendanceServiceException::timesheetPasswordMismatch();
        }
    }

    private function getCurrentEmployee(): ?Employee
    {
        $empNumber = $this->getAuthUser()->getEmpNumber();
        return $empNumber === null
            ? null
            : $this->getEntityManager()->find(Employee::class, $empNumber);
    }
}
