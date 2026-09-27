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

/**
 * BR: a YouTube link reduced to its video id -- the same rule as
 * YoutubeLink.php, which is the one that counts. The host is compared whole:
 * "contains youtube.com" would accept youtube.com.evil.com.
 */

const ID = /^[A-Za-z0-9_-]{11}$/;
const WATCH_HOSTS = ['youtube.com', 'www.youtube.com', 'm.youtube.com'];

export function extractYoutubeId(value) {
  let url;
  try {
    url = new URL(String(value).trim());
  } catch (e) {
    return null;
  }
  if (!['http:', 'https:'].includes(url.protocol) || url.username) return null;

  const host = url.hostname.toLowerCase();
  let candidate = null;
  if (host === 'youtu.be') {
    candidate = url.pathname.slice(1);
  } else if (WATCH_HOSTS.includes(host)) {
    if (url.pathname === '/watch') {
      candidate = url.searchParams.get('v');
    } else {
      const match = url.pathname.match(/^\/(shorts|embed)\/([^/]+)$/);
      candidate = match ? match[2] : null;
    }
  }
  return candidate && ID.test(candidate) ? candidate : null;
}
