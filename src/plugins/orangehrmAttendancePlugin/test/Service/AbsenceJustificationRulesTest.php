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

use DateTime;
use OrangeHRM\Attendance\Exception\AttendanceServiceException;
use OrangeHRM\Attendance\Service\AbsenceJustificationRules;
use OrangeHRM\Tests\Util\TestCase;

/**
 * What a justified absence has to carry, and who may settle it.
 *
 * @group Attendance
 * @group Inbox
 */
class AbsenceJustificationRulesTest extends TestCase
{
    private function day(string $date): DateTime
    {
        return new DateTime($date);
    }

    public function testASingleDayAbsenceIsAccepted(): void
    {
        $this->expectNotToPerformAssertions();
        AbsenceJustificationRules::assertSubmittable(
            'FALTA_JUSTIFICADA',
            $this->day('2026-09-01'),
            $this->day('2026-09-01'),
            false
        );
    }

    public function testAPeriodIsAccepted(): void
    {
        $this->expectNotToPerformAssertions();
        AbsenceJustificationRules::assertSubmittable(
            'ATESTADO_MEDICO',
            $this->day('2026-09-01'),
            $this->day('2026-09-03'),
            true
        );
    }

    public function testAPeriodThatEndsBeforeItStartsIsRefused(): void
    {
        $this->expectException(AttendanceServiceException::class);
        AbsenceJustificationRules::assertSubmittable(
            'FALTA_JUSTIFICADA',
            $this->day('2026-09-03'),
            $this->day('2026-09-01'),
            false
        );
    }

    /**
     * A medical certificate without the certificate is not a medical
     * certificate -- it is a claim. The document is the whole evidence.
     */
    public function testAMedicalCertificateWithoutTheDocumentIsRefused(): void
    {
        $this->expectException(AttendanceServiceException::class);
        AbsenceJustificationRules::assertSubmittable(
            'ATESTADO_MEDICO',
            $this->day('2026-09-01'),
            $this->day('2026-09-01'),
            false
        );
    }

    public function testAnAttendanceDeclarationWithoutTheDocumentIsRefused(): void
    {
        $this->expectException(AttendanceServiceException::class);
        AbsenceJustificationRules::assertSubmittable(
            'DECLARACAO_COMPARECIMENTO',
            $this->day('2026-09-01'),
            $this->day('2026-09-01'),
            false
        );
    }

    /**
     * The open-ended reasons are the ones where there may be nothing to
     * attach, so they must not demand a file.
     */
    public function testTheReasonsWithNoDocumentToShowDoNotDemandOne(): void
    {
        $this->expectNotToPerformAssertions();
        foreach (['FALTA_JUSTIFICADA', 'OUTRO'] as $reason) {
            AbsenceJustificationRules::assertSubmittable(
                $reason,
                $this->day('2026-09-01'),
                $this->day('2026-09-01'),
                false
            );
        }
    }

    public function testAnUnknownReasonIsRefused(): void
    {
        $this->expectException(AttendanceServiceException::class);
        AbsenceJustificationRules::assertSubmittable(
            'FERIAS',
            $this->day('2026-09-01'),
            $this->day('2026-09-01'),
            false
        );
    }

    public function testAPendingRequestCanBeSettled(): void
    {
        $this->expectNotToPerformAssertions();
        AbsenceJustificationRules::assertDecidable('PENDING');
    }

    /**
     * Deciding twice would let an approval be quietly turned into a refusal
     * after the employee was told it was accepted.
     */
    public function testAnAlreadySettledRequestCannotBeSettledAgain(): void
    {
        foreach (['APPROVED', 'REJECTED'] as $status) {
            try {
                AbsenceJustificationRules::assertDecidable($status);
                $this->fail("Deveria ter recusado decidir de novo um pedido {$status}");
            } catch (AttendanceServiceException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }

    public function testRefusingRequiresSayingWhy(): void
    {
        $this->expectException(AttendanceServiceException::class);
        AbsenceJustificationRules::assertDecisionNote('REJECTED', null);
    }

    public function testABlankReasonForRefusingDoesNotCount(): void
    {
        $this->expectException(AttendanceServiceException::class);
        AbsenceJustificationRules::assertDecisionNote('REJECTED', '   ');
    }

    public function testApprovingNeedsNoExplanation(): void
    {
        $this->expectNotToPerformAssertions();
        AbsenceJustificationRules::assertDecisionNote('APPROVED', null);
    }

    public function testRefusingWithAReasonGoesThrough(): void
    {
        $this->expectNotToPerformAssertions();
        AbsenceJustificationRules::assertDecisionNote('REJECTED', 'Atestado ilegivel');
    }

    public function testTheReasonsOfferedAreTheOnesTheFormShows(): void
    {
        $this->assertSame(
            [
                'ATESTADO_MEDICO',
                'DECLARACAO_COMPARECIMENTO',
                'FALTA_JUSTIFICADA',
                'OUTRO',
            ],
            AbsenceJustificationRules::reasonTypes()
        );
    }
}
