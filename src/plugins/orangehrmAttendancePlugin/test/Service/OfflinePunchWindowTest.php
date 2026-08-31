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

namespace OrangeHRM\Tests\Attendance\Service;

use DateTime;
use OrangeHRM\Attendance\Exception\AttendanceServiceException;
use OrangeHRM\Attendance\Service\OfflinePunchWindow;
use OrangeHRM\Tests\Util\TestCase;

/**
 * How far back a punch synced from the offline queue may reach.
 *
 * A punch taken without signal is, by definition, backdated: only the device
 * witnessed it. Normal self punches are pinned to the server clock within
 * three minutes, so accepting these at all means loosening that control --
 * the window is how far the loosening goes, and it is the employer's choice.
 *
 * @group Attendance
 * @group OfflinePunch
 */
class OfflinePunchWindowTest extends TestCase
{
    private const MAX_HOURS = 24;

    private function now(): DateTime
    {
        return new DateTime('2026-08-28 18:00:00');
    }

    public function testAPunchTakenMinutesAgoIsAccepted(): void
    {
        $this->expectNotToPerformAssertions();
        OfflinePunchWindow::assertAcceptable(
            true,
            new DateTime('2026-08-28 17:40:00'),
            $this->now(),
            self::MAX_HOURS
        );
    }

    public function testAPunchFromEarlierInTheWindowIsAccepted(): void
    {
        $this->expectNotToPerformAssertions();
        OfflinePunchWindow::assertAcceptable(
            true,
            new DateTime('2026-08-27 20:00:00'),
            $this->now(),
            self::MAX_HOURS
        );
    }

    /**
     * Past the window the punch stops being a sync and becomes a correction,
     * which belongs to the retification flow with its approval trail.
     */
    public function testAPunchOlderThanTheWindowIsRefused(): void
    {
        $this->expectException(AttendanceServiceException::class);
        OfflinePunchWindow::assertAcceptable(
            true,
            new DateTime('2026-08-27 17:00:00'),
            $this->now(),
            self::MAX_HOURS
        );
    }

    /**
     * A device clock can drift, but a punch from the future is not a queued
     * punch -- it is a clock that was set forward.
     */
    public function testAPunchFromTheFutureIsRefused(): void
    {
        $this->expectException(AttendanceServiceException::class);
        OfflinePunchWindow::assertAcceptable(
            true,
            new DateTime('2026-08-28 18:30:00'),
            $this->now(),
            self::MAX_HOURS
        );
    }

    public function testSmallClockDriftForwardIsTolerated(): void
    {
        $this->expectNotToPerformAssertions();
        OfflinePunchWindow::assertAcceptable(
            true,
            new DateTime('2026-08-28 18:01:00'),
            $this->now(),
            self::MAX_HOURS
        );
    }

    /**
     * The employer has to opt in: switching this on is what allows a punch to
     * carry a time the server never saw.
     */
    public function testTheQueueIsRefusedWhileTheFeatureIsOff(): void
    {
        $this->expectException(AttendanceServiceException::class);
        OfflinePunchWindow::assertAcceptable(
            false,
            new DateTime('2026-08-28 17:40:00'),
            $this->now(),
            self::MAX_HOURS
        );
    }

    public function testTheRefusalExplainsTheWindow(): void
    {
        try {
            OfflinePunchWindow::assertAcceptable(
                true,
                new DateTime('2026-08-26 09:00:00'),
                $this->now(),
                self::MAX_HOURS
            );
            $this->fail('Deveria ter recusado a sincronizacao');
        } catch (AttendanceServiceException $e) {
            $this->assertStringContainsString('24', $e->getMessage());
        }
    }
}
