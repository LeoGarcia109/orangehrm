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

namespace OrangeHRM\Tests\Attendance\Api;

use OrangeHRM\Attendance\Api\AbsenceJustificationAPI;
use OrangeHRM\Attendance\Api\AnnouncementAckAPI;
use OrangeHRM\Attendance\Api\AnnouncementAPI;
use OrangeHRM\Attendance\Api\MyAttendanceRecordAPI;
use OrangeHRM\Attendance\Api\TimesheetSignatureAPI;
use OrangeHRM\Core\Api\V2\Exception\InvalidParamException;
use OrangeHRM\Core\Api\V2\Request;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Validator;
use OrangeHRM\Framework\Http\Request as HttpRequest;
use OrangeHRM\Tests\Util\TestCase;

/**
 * Optional flags have to be optional.
 *
 * `notRequiredParamRule` wraps a rule as ONE_OF(NotRequired, rule): exactly one
 * side may pass. BOOL_VAL reads an absent value (null) as `false` and accepts
 * it, so with the flag missing both sides pass and ONE_OF rejects the request.
 * That took down every mobile punch -- the punch never sends `offlineSync`
 * unless it comes from the offline queue -- and every inbox tab with it.
 *
 * These run the real rule collections of each endpoint, not a copy of them.
 *
 * @group Attendance
 * @group Validation
 */
class BrOptionalFlagValidationTest extends TestCase
{
    private function api(string $class)
    {
        return new $class(new Request(new HttpRequest()));
    }

    private function assertAccepted(array $payload, ParamRuleCollection $rules): void
    {
        try {
            Validator::validate($payload, $rules);
            $this->addToAssertionCount(1);
        } catch (InvalidParamException $e) {
            $this->fail('Recusado: ' . implode(', ', array_keys($e->getErrorBag())));
        }
    }

    private function punch(): array
    {
        return [
            'date' => '2026-09-27',
            'time' => '09:49',
            'timezoneOffset' => -3,
            'timezoneName' => 'America/Bahia',
            'latitude' => -14.676963,
            'longitude' => -39.377871,
        ];
    }

    /**
     * The regression itself: an ordinary online punch from the phone.
     */
    public function testAnOrdinaryPunchWithoutTheOfflineFlagIsAccepted(): void
    {
        $this->assertAccepted(
            $this->punch(),
            $this->api(MyAttendanceRecordAPI::class)->getValidationRuleForCreate()
        );
    }

    public function testAPunchFromTheOfflineQueueIsAccepted(): void
    {
        $this->assertAccepted(
            array_merge($this->punch(), ['offlineSync' => true]),
            $this->api(MyAttendanceRecordAPI::class)->getValidationRuleForCreate()
        );
    }

    public function testThePunchOutWithoutTheOfflineFlagIsAccepted(): void
    {
        $this->assertAccepted(
            $this->punch(),
            $this->api(MyAttendanceRecordAPI::class)->getValidationRuleForUpdate()
        );
    }

    public function testTheInboxLoadsWithoutTheSentFlag(): void
    {
        $this->assertAccepted([], $this->api(AnnouncementAPI::class)->getValidationRuleForGetAll());
    }

    public function testTheHrListOfPublishedNoticesLoads(): void
    {
        $this->assertAccepted(
            ['sent' => 'true'],
            $this->api(AnnouncementAPI::class)->getValidationRuleForGetAll()
        );
    }

    public function testANoticeCanBePublishedWithoutAskingForAcknowledgement(): void
    {
        $this->assertAccepted(
            ['title' => 'Aviso', 'body' => 'Corpo'],
            $this->api(AnnouncementAPI::class)->getValidationRuleForCreate()
        );
    }

    public function testANoticeCanBePublishedAskingForAcknowledgement(): void
    {
        $this->assertAccepted(
            ['title' => 'Aviso', 'body' => 'Corpo', 'requiresAck' => true],
            $this->api(AnnouncementAPI::class)->getValidationRuleForCreate()
        );
    }

    public function testOpeningANoticeRecordsTheReadWithoutTheAcknowledgeFlag(): void
    {
        $this->assertAccepted(
            ['announcementId' => 1],
            $this->api(AnnouncementAckAPI::class)->getValidationRuleForCreate()
        );
    }

    public function testAcknowledgingANoticeIsAccepted(): void
    {
        $this->assertAccepted(
            ['announcementId' => 1, 'acknowledge' => true],
            $this->api(AnnouncementAckAPI::class)->getValidationRuleForCreate()
        );
    }

    public function testMyAbsencesLoadWithoutTheQueueFlag(): void
    {
        $this->assertAccepted([], $this->api(AbsenceJustificationAPI::class)->getValidationRuleForGetAll());
    }

    public function testTheHrAbsenceQueueLoads(): void
    {
        $this->assertAccepted(
            ['queue' => 'true', 'status' => 'PENDING'],
            $this->api(AbsenceJustificationAPI::class)->getValidationRuleForGetAll()
        );
    }

    public function testMyTimesheetLoadsWithoutTheQueueFlag(): void
    {
        $this->assertAccepted(
            ['month' => '2026-08'],
            $this->api(TimesheetSignatureAPI::class)->getValidationRuleForGetAll()
        );
    }

    public function testTheHrTimesheetListLoads(): void
    {
        $this->assertAccepted(
            ['month' => '2026-08', 'queue' => 'true'],
            $this->api(TimesheetSignatureAPI::class)->getValidationRuleForGetAll()
        );
    }
}
