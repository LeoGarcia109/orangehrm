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

use OrangeHRM\Attendance\Service\ClientIp;
use OrangeHRM\Tests\Util\TestCase;

/**
 * The IP kept as evidence (consent, timesheet signature) must be the
 * person's, not the proxy's. The site sits behind Cloudflare and a Docker
 * bridge, so REMOTE_ADDR is always 172.x -- but the forwarding headers are
 * only believed when the connection really came through that proxy.
 *
 * @group Attendance
 */
class ClientIpTest extends TestCase
{
    public function testBehindTheProxyTheCloudflareHeaderWins(): void
    {
        $this->assertSame(
            '2804:14d:ed2a:8619::5f25',
            ClientIp::resolve('172.24.0.1', '2804:14d:ed2a:8619::5f25', '203.0.113.7, 10.0.0.1')
        );
    }

    public function testWithoutCloudflareTheFirstForwardedAddressIsUsed(): void
    {
        $this->assertSame('203.0.113.7', ClientIp::resolve('172.24.0.1', null, ' 203.0.113.7 , 10.0.0.1'));
    }

    /**
     * A request that reaches the server directly cannot choose its own IP by
     * sending the headers itself.
     */
    public function testHeadersFromAPublicConnectionAreIgnored(): void
    {
        $this->assertSame('198.51.100.20', ClientIp::resolve('198.51.100.20', '1.2.3.4', '5.6.7.8'));
    }

    public function testGarbageInTheHeadersFallsBack(): void
    {
        $this->assertSame('172.24.0.1', ClientIp::resolve('172.24.0.1', 'nao-e-ip', "<script>"));
    }

    public function testNothingKnown(): void
    {
        $this->assertNull(ClientIp::resolve(null, null, null));
        $this->assertSame('127.0.0.1', ClientIp::resolve('127.0.0.1', null, null));
    }
}
