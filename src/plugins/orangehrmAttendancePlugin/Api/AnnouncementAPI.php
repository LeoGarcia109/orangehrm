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
use OrangeHRM\Attendance\Service\AnnouncementAudience;
use OrangeHRM\Attendance\Service\AnnouncementService;
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
use OrangeHRM\Entity\Announcement;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\Subunit;

/**
 * BR: notices from HR.
 *
 * GET  /api/v2/attendance/br/announcements          - the caller's inbox
 * GET  /api/v2/attendance/br/announcements?sent=1   - what HR published, with counts
 * POST /api/v2/attendance/br/announcements          - publish (Admin)
 */
class AnnouncementAPI extends Endpoint implements CollectionEndpoint
{
    use EntityManagerHelperTrait;
    use AuthUserTrait;

    public const PARAMETER_TITLE = 'title';
    public const PARAMETER_BODY = 'body';
    public const PARAMETER_SCOPE = 'scope';
    public const PARAMETER_SUBUNIT_ID = 'subunitId';
    public const PARAMETER_EMPLOYEE_ID = 'employeeId';
    public const PARAMETER_REQUIRES_ACK = 'requiresAck';
    public const PARAMETER_EXPIRES_AT = 'expiresAt';
    public const PARAMETER_SENT = 'sent';

    public const PARAM_RULE_TITLE_MAX_LENGTH = 150;
    public const PARAM_RULE_BODY_MAX_LENGTH = 5000;

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $sent = $this->getRequestParams()->getBooleanOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_SENT
        );

        return $sent === true ? $this->listPublished() : $this->listInbox();
    }

    /**
     * The caller's own inbox, each notice carrying what they already did with it.
     */
    private function listInbox(): EndpointResult
    {
        $service = new AnnouncementService();
        $employee = $this->getCurrentEmployee();
        if (!$employee instanceof Employee) {
            return new EndpointCollectionResult(
                ArrayModel::class,
                [],
                new ParameterBag([CommonParams::PARAMETER_TOTAL => 0])
            );
        }

        $receipts = $service->getReceiptsFor($employee);
        $items = [];
        foreach ($service->getInboxFor($employee) as $announcement) {
            $receipt = $receipts[$announcement->getId()] ?? null;
            $items[] = [
                'id' => $announcement->getId(),
                'title' => $announcement->getTitle(),
                'body' => $announcement->getBody(),
                'requiresAck' => $announcement->isRequiresAck(),
                'publishedAt' => $announcement->getPublishedAt()->format('Y-m-d H:i'),
                'readAt' => $receipt?->getReadAt()?->format('Y-m-d H:i'),
                'acknowledgedAt' => $receipt?->getAcknowledgedAt()?->format('Y-m-d H:i'),
                'pendingAck' => $service->isPendingAck($announcement, $receipt),
            ];
        }

        return new EndpointCollectionResult(
            ArrayModel::class,
            $items,
            new ParameterBag([
                CommonParams::PARAMETER_TOTAL => count($items),
                'pendingAck' => count(array_filter($items, fn ($i) => $i['pendingAck'])),
            ])
        );
    }

    /**
     * What HR published, with how many have read and acknowledged -- the point
     * of requiring acknowledgement is being able to see who is missing.
     */
    private function listPublished(): EndpointResult
    {
        $service = new AnnouncementService();
        $announcements = $this->getEntityManager()
            ->getRepository(Announcement::class)
            ->findBy([], ['publishedAt' => 'DESC']);

        $items = [];
        foreach ($announcements as $announcement) {
            $receipts = $service->getReceipts($announcement);
            $items[] = [
                'id' => $announcement->getId(),
                'title' => $announcement->getTitle(),
                'body' => $announcement->getBody(),
                'scope' => $announcement->getScope(),
                'subunitId' => $announcement->getSubunit()?->getId(),
                'subunitName' => $announcement->getSubunit()?->getName(),
                'employeeId' => $announcement->getEmployee()?->getEmpNumber(),
                'requiresAck' => $announcement->isRequiresAck(),
                'publishedAt' => $announcement->getPublishedAt()->format('Y-m-d H:i'),
                'expiresAt' => $announcement->getExpiresAt()?->format('Y-m-d'),
                'readCount' => count(array_filter($receipts, fn ($r) => $r->getReadAt() !== null)),
                'ackCount' => count(array_filter($receipts, fn ($r) => $r->getAcknowledgedAt() !== null)),
            ];
        }

        return new EndpointCollectionResult(
            ArrayModel::class,
            $items,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => count($items)])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_SENT, new Rule(Rules::BOOL_VAL))
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $scope = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_SCOPE,
            AnnouncementAudience::SCOPE_NETWORK
        );

        $announcement = new Announcement();
        $announcement->setTitle($this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_TITLE
        ));
        $announcement->setBody($this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_BODY
        ));
        $announcement->setScope($scope);
        $announcement->setRequiresAck($this->getRequestParams()->getBooleanOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_REQUIRES_ACK
        ) === true);

        // Only the target the scope names is kept: a notice that carries both a
        // unit and a person is ambiguous about who it is for.
        if ($scope === AnnouncementAudience::SCOPE_SUBUNIT) {
            $subunitId = $this->getRequestParams()->getInt(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_SUBUNIT_ID
            );
            $announcement->setSubunit($this->getEntityManager()->find(Subunit::class, $subunitId));
        } elseif ($scope === AnnouncementAudience::SCOPE_EMPLOYEE) {
            $employeeId = $this->getRequestParams()->getInt(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_EMPLOYEE_ID
            );
            $announcement->setEmployee($this->getEntityManager()->find(Employee::class, $employeeId));
        }

        $expiresAt = $this->getRequestParams()->getDateTimeOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_EXPIRES_AT
        );
        if ($expiresAt instanceof DateTime) {
            $announcement->setExpiresAt($expiresAt);
        }
        $announcement->setCreatedByEmpNumber($this->getAuthUser()->getEmpNumber());

        $this->getEntityManager()->persist($announcement);
        $this->getEntityManager()->flush();

        return new EndpointResourceResult(ArrayModel::class, [
            'id' => $announcement->getId(),
            'title' => $announcement->getTitle(),
            'scope' => $announcement->getScope(),
            'requiresAck' => $announcement->isRequiresAck(),
            'publishedAt' => $announcement->getPublishedAt()->format('Y-m-d H:i'),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_TITLE,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::LENGTH, [1, self::PARAM_RULE_TITLE_MAX_LENGTH])
            ),
            new ParamRule(
                self::PARAMETER_BODY,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::LENGTH, [1, self::PARAM_RULE_BODY_MAX_LENGTH])
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_SCOPE,
                    new Rule(Rules::IN, [[
                        AnnouncementAudience::SCOPE_NETWORK,
                        AnnouncementAudience::SCOPE_SUBUNIT,
                        AnnouncementAudience::SCOPE_EMPLOYEE,
                    ]])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_SUBUNIT_ID, new Rule(Rules::POSITIVE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EMPLOYEE_ID, new Rule(Rules::POSITIVE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_REQUIRES_ACK, new Rule(Rules::BOOL_VAL))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EXPIRES_AT, new Rule(Rules::API_DATE))
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

    private function getCurrentEmployee(): ?Employee
    {
        $empNumber = $this->getAuthUser()->getEmpNumber();
        return $empNumber === null
            ? null
            : $this->getEntityManager()->find(Employee::class, $empNumber);
    }
}
