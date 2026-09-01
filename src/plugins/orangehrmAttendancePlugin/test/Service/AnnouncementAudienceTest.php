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

use OrangeHRM\Attendance\Service\AnnouncementAudience;
use OrangeHRM\Tests\Util\TestCase;

/**
 * Who a notice reaches, isolated from the database.
 *
 * Reach follows the same chain the geofence and the employer CNPJ walk: a
 * notice aimed at a company has to find the people in the departments under
 * it, and must not spill into the sibling company next to it. Getting this
 * wrong is not a cosmetic bug -- it is a shift change reaching the wrong
 * payroll, or not reaching anyone.
 *
 * @group Attendance
 * @group Inbox
 */
class AnnouncementAudienceTest extends TestCase
{
    // Acacia do Sul (2) under Grupo HRR (1); Producao (5) under Acacia
    private const CHAIN_PRODUCAO = [5, 2, 1];
    private const CHAIN_ACACIA = [2, 1];
    private const CHAIN_SIBLING = [3, 1];

    public function testANetworkNoticeReachesEveryone(): void
    {
        $this->assertTrue(
            AnnouncementAudience::reaches('NETWORK', null, null, self::CHAIN_PRODUCAO, 7)
        );
    }

    /**
     * Somebody whose registration has no unit yet still works here, and a
     * notice to the whole network is exactly what they should not miss.
     */
    public function testANetworkNoticeReachesSomebodyWithNoUnit(): void
    {
        $this->assertTrue(
            AnnouncementAudience::reaches('NETWORK', null, null, [], 7)
        );
    }

    public function testACompanyNoticeReachesItsOwnPeople(): void
    {
        $this->assertTrue(
            AnnouncementAudience::reaches('SUBUNIT', 2, null, self::CHAIN_ACACIA, 7)
        );
    }

    /**
     * People sit in departments below the company, which is the whole reason
     * reach has to climb instead of matching the unit exactly.
     */
    public function testACompanyNoticeReachesTheDepartmentsUnderIt(): void
    {
        $this->assertTrue(
            AnnouncementAudience::reaches('SUBUNIT', 2, null, self::CHAIN_PRODUCAO, 7)
        );
    }

    public function testACompanyNoticeDoesNotSpillIntoTheCompanyNextDoor(): void
    {
        $this->assertFalse(
            AnnouncementAudience::reaches('SUBUNIT', 2, null, self::CHAIN_SIBLING, 7)
        );
    }

    /**
     * Aiming at the root is aiming at everybody under it, which is what makes
     * "the whole network" expressible as a unit as well.
     */
    public function testANoticeAimedAtTheRootReachesTheCompaniesBelow(): void
    {
        $this->assertTrue(
            AnnouncementAudience::reaches('SUBUNIT', 1, null, self::CHAIN_PRODUCAO, 7)
        );
    }

    public function testACompanyNoticeDoesNotReachSomebodyWithNoUnit(): void
    {
        $this->assertFalse(
            AnnouncementAudience::reaches('SUBUNIT', 2, null, [], 7)
        );
    }

    public function testAPersonalNoticeReachesOnlyThatPerson(): void
    {
        $this->assertTrue(
            AnnouncementAudience::reaches('EMPLOYEE', null, 7, self::CHAIN_PRODUCAO, 7)
        );
        $this->assertFalse(
            AnnouncementAudience::reaches('EMPLOYEE', null, 7, self::CHAIN_PRODUCAO, 8)
        );
    }

    /**
     * A notice whose target was deleted must reach nobody rather than
     * everybody -- the failure has to be silence, not a broadcast.
     */
    public function testANoticeWithNoTargetReachesNobody(): void
    {
        $this->assertFalse(
            AnnouncementAudience::reaches('SUBUNIT', null, null, self::CHAIN_PRODUCAO, 7)
        );
        $this->assertFalse(
            AnnouncementAudience::reaches('EMPLOYEE', null, null, self::CHAIN_PRODUCAO, 7)
        );
    }

    public function testAnUnknownScopeReachesNobody(): void
    {
        $this->assertFalse(
            AnnouncementAudience::reaches('WHATEVER', null, null, self::CHAIN_PRODUCAO, 7)
        );
    }
}
