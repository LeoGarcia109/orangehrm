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

use OrangeHRM\Attendance\Service\GeofenceService;
use OrangeHRM\Entity\GeofenceLocation;
use OrangeHRM\Tests\Util\TestCase;

/**
 * The punch-time decision, isolated from the database.
 *
 * Multi-company rule: enforcement is opt-in per company (a `geofence_required`
 * unit anywhere up the employee's chain). Once a company opts in, every gap
 * closes the gate instead of opening it -- an unconfigured company must not
 * silently accept punches from anywhere.
 *
 * @group Attendance
 * @group Geofence
 */
class GeofenceDecisionTest extends TestCase
{
    // Praca de Itajuipe-BA, matching the location registered for Acacia do Sul
    private const SITE_LAT = -14.676963;
    private const SITE_LNG = -39.377871;

    private GeofenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeofenceService();
    }

    private function location(float $lat, float $lng, int $radius): GeofenceLocation
    {
        $location = new GeofenceLocation();
        $location->setName('Sede');
        $location->setLatitude(sprintf('%.8f', $lat));
        $location->setLongitude(sprintf('%.8f', $lng));
        $location->setRadius($radius);
        return $location;
    }

    private function site(int $radius = 50): array
    {
        return [$this->location(self::SITE_LAT, self::SITE_LNG, $radius)];
    }

    public function testMasterSwitchOffSkipsEveryCheck(): void
    {
        $decision = $this->service->decide(false, false, true, [], null, null);

        $this->assertTrue($decision['valid']);
        $this->assertNull($decision['reason']);
    }

    /**
     * Without a unit there is no way to tell which company the employee belongs
     * to, so an incomplete registration must not become a way around the fence.
     */
    public function testEmployeeWithoutAUnitIsBlocked(): void
    {
        $decision = $this->service->decide(true, false, false, $this->site(), self::SITE_LAT, self::SITE_LNG);

        $this->assertFalse($decision['valid']);
        $this->assertSame('missing_subunit', $decision['reason']);
    }

    public function testCompanyThatDidNotOptInIsNotChecked(): void
    {
        $decision = $this->service->decide(true, true, false, [], null, null);

        $this->assertTrue($decision['valid']);
        $this->assertNull($decision['reason']);
    }

    /**
     * The fail-open this replaces: a company marked as requiring geofence but
     * with no location registered used to accept punches from anywhere.
     */
    public function testRequiringCompanyWithNoLocationIsBlocked(): void
    {
        $decision = $this->service->decide(true, true, true, [], self::SITE_LAT, self::SITE_LNG);

        $this->assertFalse($decision['valid']);
        $this->assertSame('geofence_not_configured', $decision['reason']);
    }

    /**
     * Reported ahead of missing coordinates: turning on GPS cannot fix a company
     * with no fence registered, so the message must point at the real problem.
     */
    public function testMissingConfigurationIsReportedBeforeMissingCoordinates(): void
    {
        $decision = $this->service->decide(true, true, true, [], null, null);

        $this->assertSame('geofence_not_configured', $decision['reason']);
    }

    public function testPunchWithoutCoordinatesIsBlocked(): void
    {
        $decision = $this->service->decide(true, true, true, $this->site(), null, null);

        $this->assertFalse($decision['valid']);
        $this->assertSame('missing_coordinates', $decision['reason']);
    }

    public function testPunchInsideTheRadiusIsAccepted(): void
    {
        $decision = $this->service->decide(
            true,
            true,
            true,
            $this->site(),
            self::SITE_LAT,
            self::SITE_LNG
        );

        $this->assertTrue($decision['valid']);
        $this->assertNull($decision['reason']);
        $this->assertLessThan(1.0, $decision['nearestDistance']);
    }

    public function testPunchOutsideTheRadiusIsBlockedAndReportsTheDistance(): void
    {
        // ~1.1 km north of the site, well beyond the 50 m radius
        $decision = $this->service->decide(
            true,
            true,
            true,
            $this->site(),
            self::SITE_LAT + 0.01,
            self::SITE_LNG
        );

        $this->assertFalse($decision['valid']);
        $this->assertSame('outside_allowed_area', $decision['reason']);
        $this->assertGreaterThan(1000.0, $decision['nearestDistance']);
        $this->assertLessThan(1200.0, $decision['nearestDistance']);
    }

    public function testNearestDistanceIsTheClosestOfSeveralSites(): void
    {
        $locations = [
            $this->location(self::SITE_LAT + 0.05, self::SITE_LNG, 50),
            $this->location(self::SITE_LAT + 0.01, self::SITE_LNG, 50),
        ];

        $decision = $this->service->decide(true, true, true, $locations, self::SITE_LAT, self::SITE_LNG);

        $this->assertFalse($decision['valid']);
        $this->assertLessThan(1200.0, $decision['nearestDistance']);
    }

    public function testAnyOneSiteInRangeAcceptsThePunch(): void
    {
        $locations = [
            $this->location(self::SITE_LAT + 0.05, self::SITE_LNG, 50),
            $this->location(self::SITE_LAT, self::SITE_LNG, 50),
        ];

        $decision = $this->service->decide(true, true, true, $locations, self::SITE_LAT, self::SITE_LNG);

        $this->assertTrue($decision['valid']);
    }
}
