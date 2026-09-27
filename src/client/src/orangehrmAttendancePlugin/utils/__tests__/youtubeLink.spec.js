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

import {extractYoutubeId} from '../youtubeLink';

/**
 * The builder's instant check of a video link. The server checks again with
 * the same cases (YoutubeLinkTest.php); this one only spares HR a round trip.
 */
describe('extractYoutubeId', () => {
  it.each([
    'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'https://youtube.com/watch?v=dQw4w9WgXcQ&t=42s',
    'https://m.youtube.com/watch?v=dQw4w9WgXcQ',
    'https://youtu.be/dQw4w9WgXcQ?si=abc',
    'https://www.youtube.com/shorts/dQw4w9WgXcQ',
    'https://www.youtube.com/embed/dQw4w9WgXcQ',
    'http://youtu.be/dQw4w9WgXcQ',
    '  https://youtu.be/dQw4w9WgXcQ  ',
    'https://WWW.YOUTUBE.COM/watch?v=dQw4w9WgXcQ',
  ])('reads %s', (url) => {
    expect(extractYoutubeId(url)).toBe('dQw4w9WgXcQ');
  });

  it.each([
    'https://youtube.com.evil.com/watch?v=dQw4w9WgXcQ',
    'https://evilyoutube.com/watch?v=dQw4w9WgXcQ',
    'https://vimeo.com/123456',
    'https://youtu.be/abc',
    'https://www.youtube.com/watch?v=dQw4w9WgXc<',
    'javascript:alert(1)//youtu.be/dQw4w9WgXcQ',
    'https://www.youtube.com/watch?list=PL123',
    'https://youtube.com@evil.com/watch?v=dQw4w9WgXcQ',
    '',
    'dQw4w9WgXcQ',
  ])('refuses %s', (url) => {
    expect(extractYoutubeId(url)).toBeNull();
  });
});
