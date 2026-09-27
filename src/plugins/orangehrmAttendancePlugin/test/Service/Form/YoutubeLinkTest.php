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

namespace OrangeHRM\Tests\Attendance\Service\Form;

use OrangeHRM\Attendance\Service\Form\YoutubeLink;
use OrangeHRM\Tests\Util\TestCase;

/**
 * A video link becomes an iframe on every employee's phone, so only a real
 * YouTube id may come out of it -- never a URL somebody typed.
 *
 * @group Attendance
 * @group Forms
 */
class YoutubeLinkTest extends TestCase
{
    /**
     * @dataProvider validLinks
     */
    public function testExtractsTheIdFromEveryShapeOfLink(string $url): void
    {
        $this->assertSame('dQw4w9WgXcQ', YoutubeLink::extractId($url));
    }

    public function validLinks(): array
    {
        return [
            'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            'watch sem www' => ['https://youtube.com/watch?v=dQw4w9WgXcQ&t=42s'],
            'mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ'],
            'curto' => ['https://youtu.be/dQw4w9WgXcQ?si=abc'],
            'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ'],
            'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ'],
            'http' => ['http://youtu.be/dQw4w9WgXcQ'],
            'espacos' => ['  https://youtu.be/dQw4w9WgXcQ  '],
            'host em maiusculas' => ['https://WWW.YOUTUBE.COM/watch?v=dQw4w9WgXcQ'],
        ];
    }

    /**
     * @dataProvider invalidLinks
     */
    public function testRefusesAnythingThatIsNotYoutube(string $url): void
    {
        $this->assertNull(YoutubeLink::extractId($url));
    }

    public function invalidLinks(): array
    {
        return [
            'dominio imitando' => ['https://youtube.com.evil.com/watch?v=dQw4w9WgXcQ'],
            'prefixo' => ['https://evilyoutube.com/watch?v=dQw4w9WgXcQ'],
            'outro site' => ['https://vimeo.com/123456'],
            'id curto' => ['https://youtu.be/abc'],
            'id com lixo' => ['https://www.youtube.com/watch?v=dQw4w9WgXc<'],
            'javascript' => ['javascript:alert(1)//youtu.be/dQw4w9WgXcQ'],
            'sem v' => ['https://www.youtube.com/watch?list=PL123'],
            'v como lista' => ['https://www.youtube.com/watch?v[]=dQw4w9WgXcQ'],
            'usuario no host' => ['https://youtube.com@evil.com/watch?v=dQw4w9WgXcQ'],
            'vazio' => [''],
            'so o id' => ['dQw4w9WgXcQ'],
        ];
    }

    public function testEmbedsThroughTheNoCookieDomain(): void
    {
        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            YoutubeLink::embedUrl('dQw4w9WgXcQ')
        );
    }
}
