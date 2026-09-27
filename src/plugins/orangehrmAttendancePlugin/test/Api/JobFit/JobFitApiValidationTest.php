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

namespace OrangeHRM\Tests\Attendance\Api\JobFit;

use OrangeHRM\Attendance\Api\CompetencyRatingAPI;
use OrangeHRM\Attendance\Api\JobProfileAPI;
use OrangeHRM\Attendance\Api\JobProfileSuggestionAPI;
use OrangeHRM\Attendance\Api\ProfileComparisonAPI;
use OrangeHRM\Attendance\Api\ProfilePeopleAPI;
use OrangeHRM\Core\Api\V2\Exception\InvalidParamException;
use OrangeHRM\Core\Api\V2\Request;
use OrangeHRM\Core\Api\V2\Validator\Validator;
use OrangeHRM\Framework\Http\Request as HttpRequest;
use OrangeHRM\Tests\Util\TestCase;

/**
 * What the job-profile and comparison screens send.
 *
 * @group Attendance
 * @group JobFit
 * @group Validation
 */
class JobFitApiValidationTest extends TestCase
{
    private function accepted(string $class, string $method, array $payload): bool
    {
        try {
            Validator::validate($payload, (new $class(new Request(new HttpRequest())))->$method());
            return true;
        } catch (InvalidParamException $e) {
            return false;
        }
    }

    public function testSavingAProfile(): void
    {
        $body = ['id' => 3, 'behaviorWeight' => 50, 'factors' => [['instrument' => 'BIG5']], 'competencies' => []];
        $this->assertTrue($this->accepted(JobProfileAPI::class, 'getValidationRuleForUpdate', $body));
        $this->assertFalse($this->accepted(JobProfileAPI::class, 'getValidationRuleForUpdate', ['behaviorWeight' => 'x'] + $body));
        $this->assertFalse($this->accepted(JobProfileAPI::class, 'getValidationRuleForUpdate', ['factors' => 'x'] + $body));
        $this->assertTrue($this->accepted(JobProfileAPI::class, 'getValidationRuleForGetOne', ['id' => 3]));
        $this->assertTrue($this->accepted(JobProfileAPI::class, 'getValidationRuleForGetAll', []));
    }

    public function testSuggestionTakesTheReferences(): void
    {
        $this->assertTrue($this->accepted(JobProfileSuggestionAPI::class, 'getValidationRuleForGetAll', ['empNumbers' => '1,2']));
        $this->assertFalse($this->accepted(JobProfileSuggestionAPI::class, 'getValidationRuleForGetAll', []));
    }

    public function testPeopleFiltersAreOptional(): void
    {
        $this->assertTrue($this->accepted(ProfilePeopleAPI::class, 'getValidationRuleForGetAll', []));
        $this->assertTrue($this->accepted(ProfilePeopleAPI::class, 'getValidationRuleForGetAll', [
            'type' => 'e', 'vacancyId' => 2, 'subunitId' => 1, 'name' => 'ana',
        ]));
        $this->assertFalse($this->accepted(ProfilePeopleAPI::class, 'getValidationRuleForGetAll', ['type' => 'x']));
    }

    public function testComparisonNeedsJobAndPeople(): void
    {
        $this->assertTrue($this->accepted(ProfileComparisonAPI::class, 'getValidationRuleForGetAll', ['jobTitleId' => 3, 'subjects' => 'c1,e2']));
        $this->assertFalse($this->accepted(ProfileComparisonAPI::class, 'getValidationRuleForGetAll', ['subjects' => 'c1']));
        $this->assertFalse($this->accepted(ProfileComparisonAPI::class, 'getValidationRuleForGetAll', ['jobTitleId' => 3]));
    }

    public function testRatingSetOrClear(): void
    {
        $this->assertTrue($this->accepted(CompetencyRatingAPI::class, 'getValidationRuleForUpdate', ['id' => 4, 'subject' => 'c1', 'rating' => 5]));
        $this->assertTrue($this->accepted(CompetencyRatingAPI::class, 'getValidationRuleForUpdate', ['id' => 4, 'subject' => 'e2']));
        $this->assertFalse($this->accepted(CompetencyRatingAPI::class, 'getValidationRuleForUpdate', ['id' => 4, 'subject' => 'c1', 'rating' => 6]));
        $this->assertFalse($this->accepted(CompetencyRatingAPI::class, 'getValidationRuleForUpdate', ['id' => 4, 'subject' => 'x1', 'rating' => 3]));
    }
}
