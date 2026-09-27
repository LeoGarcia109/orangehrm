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

namespace OrangeHRM\Attendance\Service;

/**
 * BR: the IP address kept as evidence -- punch audit, timesheet signature,
 * candidate consent.
 *
 * The site is behind Cloudflare and a Docker bridge, so REMOTE_ADDR is always
 * the bridge (172.x). The real address travels in CF-Connecting-IP and
 * X-Forwarded-For, and those are believed only when the connection itself
 * came from a private address -- our own proxy. A request that reaches the
 * server directly cannot pick its own IP by sending the headers.
 */
final class ClientIp
{
    public static function fromServer(): ?string
    {
        return self::resolve(
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null
        );
    }

    public static function resolve(?string $remoteAddr, ?string $cfConnectingIp, ?string $forwardedFor): ?string
    {
        if ($remoteAddr === null || !self::isProxy($remoteAddr)) {
            return $remoteAddr;
        }
        foreach ([$cfConnectingIp, explode(',', (string)$forwardedFor)[0]] as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
                return $candidate;
            }
        }
        return $remoteAddr;
    }

    /**
     * Private or loopback: a hop inside our own network.
     */
    private static function isProxy(string $address): bool
    {
        return filter_var($address, FILTER_VALIDATE_IP) !== false
            && filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
