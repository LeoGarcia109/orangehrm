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

namespace OrangeHRM\Attendance\Service\Form;

/**
 * BR: a YouTube link reduced to the one thing worth keeping, the video id.
 *
 * The host is compared whole, never with "contains" or "ends with": a check that
 * accepts youtube.com.evil.com or evilyoutube.com puts somebody else's page in
 * an iframe on every employee's phone.
 */
final class YoutubeLink
{
    private const ID_PATTERN = '/^[A-Za-z0-9_-]{11}$/';
    private const WATCH_HOSTS = ['youtube.com', 'www.youtube.com', 'm.youtube.com'];
    private const SHORT_HOST = 'youtu.be';

    public static function extractId(string $url): ?string
    {
        $parts = parse_url(trim($url));
        if (!is_array($parts) || !in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
            return null;
        }
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';

        $candidate = null;
        if ($host === self::SHORT_HOST) {
            $candidate = ltrim($path, '/');
        } elseif (in_array($host, self::WATCH_HOSTS, true)) {
            if ($path === '/watch') {
                parse_str($parts['query'] ?? '', $query);
                $candidate = is_string($query['v'] ?? null) ? $query['v'] : null;
            } elseif (preg_match('#^/(shorts|embed)/([^/]+)$#', $path, $matches) === 1) {
                $candidate = $matches[2];
            }
        }

        return $candidate !== null && preg_match(self::ID_PATTERN, $candidate) === 1
            ? $candidate
            : null;
    }

    public static function embedUrl(string $id): string
    {
        return 'https://www.youtube-nocookie.com/embed/' . $id;
    }
}
