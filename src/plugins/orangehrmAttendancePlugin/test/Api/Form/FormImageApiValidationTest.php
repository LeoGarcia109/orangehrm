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

namespace OrangeHRM\Tests\Attendance\Api\Form;

use OrangeHRM\Attendance\Api\FormImageAPI;
use OrangeHRM\Core\Api\V2\Exception\InvalidParamException;
use OrangeHRM\Core\Api\V2\Request;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Validator;
use OrangeHRM\Core\Service\TextHelperService;
use OrangeHRM\Framework\Http\Request as HttpRequest;
use OrangeHRM\Framework\Services;
use OrangeHRM\Framework\ServiceContainer;
use OrangeHRM\Tests\Util\TestCase;

/**
 * The image upload the builder sends, through the endpoint's real rules.
 *
 * Its own class because the attachment rule measures the decoded file through
 * the text helper service, registered here on its own -- a kernel test would
 * want an installed application.
 *
 * @group Attendance
 * @group Forms
 * @group Validation
 */
class FormImageApiValidationTest extends TestCase
{
    protected function setUp(): void
    {
        ServiceContainer::getContainer()->set(Services::TEXT_HELPER_SERVICE, new TextHelperService());
    }

    private function rules(): ParamRuleCollection
    {
        return (new FormImageAPI(new Request(new HttpRequest())))->getValidationRuleForCreate();
    }

    private function image(string $name, string $type, string $bytes): array
    {
        return ['name' => $name, 'type' => $type, 'size' => (string)strlen($bytes), 'base64' => base64_encode($bytes)];
    }

    private function isAccepted(array $payload): bool
    {
        try {
            Validator::validate($payload, $this->rules());
            return true;
        } catch (InvalidParamException $e) {
            return false;
        }
    }

    public function testAnImageUploadIsAccepted(): void
    {
        $this->assertTrue($this->isAccepted(['formId' => 4, 'image' => $this->image('extintor.png', 'image/png', 'png-bytes')]));
        $this->assertTrue($this->isAccepted(['formId' => 4, 'image' => $this->image('foto.JPG', 'image/jpeg', 'jpg')]));
    }

    public function testAnImageUploadWithoutTheFileIsRejected(): void
    {
        $this->assertFalse($this->isAccepted(['formId' => 4]));
    }

    public function testAPdfIsNotAnImage(): void
    {
        $this->assertFalse($this->isAccepted(['formId' => 4, 'image' => $this->image('a.pdf', 'application/pdf', 'pdf')]));
    }

    /**
     * SVG can carry script, and it would be served inline to everybody.
     */
    public function testAnSvgIsNotAccepted(): void
    {
        $this->assertFalse($this->isAccepted(['formId' => 4, 'image' => $this->image('a.svg', 'image/svg+xml', '<svg/>')]));
    }

    public function testAnImageOverTwoMegabytesIsRejected(): void
    {
        $this->assertFalse($this->isAccepted([
            'formId' => 4,
            'image' => $this->image('grande.png', 'image/png', str_repeat('a', 2097153)),
        ]));
    }
}
