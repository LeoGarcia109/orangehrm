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

use OrangeHRM\Attendance\Exception\AttendanceServiceException;
use OrangeHRM\Attendance\Service\ProxyPunchGuard;
use OrangeHRM\Tests\Util\TestCase;

/**
 * Punching on behalf of somebody else, at a company that requires geofence.
 *
 * The geofence cannot be checked here -- the coordinates belong to whoever is
 * operating the screen, not to the worker being recorded -- so the punch would
 * otherwise be the one way around a fence the company made mandatory. It stays
 * allowed, because forgotten punches have to be fixable, but it has to say why.
 *
 * @group Attendance
 * @group Geofence
 */
class ProxyPunchGuardTest extends TestCase
{
    public function testYourOwnPunchNeedsNoJustification(): void
    {
        $this->expectNotToPerformAssertions();
        ProxyPunchGuard::assertJustified(true, true, null);
    }

    /**
     * Without a mandatory fence there is nothing being worked around, so the
     * proxy punch stays as unceremonious as it was before.
     */
    public function testAProxyPunchAtACompanyWithoutGeofenceNeedsNoJustification(): void
    {
        $this->expectNotToPerformAssertions();
        ProxyPunchGuard::assertJustified(false, false, null);
    }

    public function testAProxyPunchAtAGeofencedCompanyWithoutAReasonIsRefused(): void
    {
        $this->expectException(AttendanceServiceException::class);
        ProxyPunchGuard::assertJustified(false, true, null);
    }

    public function testAProxyPunchWithAReasonGoesThrough(): void
    {
        $this->expectNotToPerformAssertions();
        ProxyPunchGuard::assertJustified(false, true, 'Funcionario esqueceu de bater na saida');
    }

    /**
     * Whitespace is what gets typed to get past a required field.
     */
    public function testABlankReasonDoesNotCount(): void
    {
        $this->expectException(AttendanceServiceException::class);
        ProxyPunchGuard::assertJustified(false, true, '   ');
    }

    /**
     * A couple of characters is not a reason either -- the note is what an
     * auditor reads months later to understand why the fence was bypassed.
     */
    public function testAReasonTooShortToMeanAnythingIsRefused(): void
    {
        $this->expectException(AttendanceServiceException::class);
        ProxyPunchGuard::assertJustified(false, true, 'ok');
    }

    public function testTheRefusalSaysWhatIsMissing(): void
    {
        try {
            ProxyPunchGuard::assertJustified(false, true, null);
            $this->fail('Deveria ter recusado a batida');
        } catch (AttendanceServiceException $e) {
            $this->assertStringContainsString('justificativa', $e->getMessage());
        }
    }
}
