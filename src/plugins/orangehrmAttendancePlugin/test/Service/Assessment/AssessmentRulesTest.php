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

namespace OrangeHRM\Tests\Attendance\Service\Assessment;

use DateTime;
use OrangeHRM\Attendance\Exception\AssessmentRuleException;
use OrangeHRM\Attendance\Service\Assessment\AssessmentRules;
use OrangeHRM\Attendance\Service\Assessment\AssessmentToken;
use OrangeHRM\Tests\Util\TestCase;

/**
 * The public link is the candidate's only credential, so it has to be
 * unguessable, stored only as a hash, and dead once used or expired.
 *
 * @group Attendance
 * @group Assessment
 */
class AssessmentRulesTest extends TestCase
{
    public function testTokensAreLongRandomAndUrlSafe(): void
    {
        $tokens = [];
        for ($i = 0; $i < 1000; $i++) {
            $token = AssessmentToken::generate();
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $token);
            $tokens[$token] = true;
        }
        $this->assertCount(1000, $tokens);
    }

    public function testOnlyTheHashIsStored(): void
    {
        $token = AssessmentToken::generate();

        $this->assertSame(hash('sha256', $token), AssessmentToken::hash($token));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', AssessmentToken::hash($token));
    }

    public function testMalformedTokensAreRejectedBeforeAnyLookup(): void
    {
        $this->assertTrue(AssessmentToken::looksValid(AssessmentToken::generate()));
        foreach (['', 'abc', str_repeat('a', 44), str_repeat('a', 42) . '/', "' OR 1=1 --"] as $bad) {
            $this->assertFalse(AssessmentToken::looksValid($bad), $bad);
        }
    }

    public function testAPendingInviteWithinItsDeadlineIsOpen(): void
    {
        AssessmentRules::assertOpen('PENDING', new DateTime('2026-10-04 12:00:00'), new DateTime('2026-10-04 12:00:00'));
        AssessmentRules::assertOpen('PENDING', null, new DateTime());
        $this->addToAssertionCount(2);
    }

    public function testFinishedCancelledOrExpiredInvitesAreClosed(): void
    {
        foreach ([['COMPLETED', null], ['CANCELLED', null], ['PENDING', new DateTime('2026-10-04 11:59:59')]] as [$status, $expires]) {
            try {
                AssessmentRules::assertOpen($status, $expires, new DateTime('2026-10-04 12:00:00'));
                $this->fail("{$status} deveria estar fechado");
            } catch (AssessmentRuleException $e) {
                // The same words whatever the reason: a closed link tells nothing about itself.
                $this->assertSame('Este link nao esta mais ativo. Fale com o RH.', $e->getMessage());
            }
        }
    }

    public function testExpiry(): void
    {
        $now = new DateTime('2026-10-04 12:00:00');
        $this->assertTrue(AssessmentRules::isExpired('PENDING', new DateTime('2026-10-04 11:00:00'), $now));
        $this->assertFalse(AssessmentRules::isExpired('PENDING', null, $now));
        $this->assertFalse(AssessmentRules::isExpired('COMPLETED', new DateTime('2026-10-01'), $now));
    }

    public function testACandidateAnswersOnlyAfterConsenting(): void
    {
        AssessmentRules::assertConsent('CANDIDATE', new DateTime());
        AssessmentRules::assertConsent('EMPLOYEE', null);
        $this->addToAssertionCount(2);

        $this->expectException(AssessmentRuleException::class);
        AssessmentRules::assertConsent('CANDIDATE', null);
    }

    public function testInstrumentsMustBeKnownAndPresent(): void
    {
        AssessmentRules::assertInstruments(['BIG5', 'DISC']);
        AssessmentRules::assertInstruments(['DISC']);
        $this->addToAssertionCount(2);

        foreach ([[], ['ENNEAGRAM'], ['BIG5', 'BIG5']] as $bad) {
            try {
                AssessmentRules::assertInstruments($bad);
                $this->fail(json_encode($bad));
            } catch (AssessmentRuleException $e) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testExpiryIsSevenDaysAhead(): void
    {
        $this->assertEquals(
            new DateTime('2026-10-11 12:00:00'),
            AssessmentRules::expiryFrom(new DateTime('2026-10-04 12:00:00'))
        );
    }
}
