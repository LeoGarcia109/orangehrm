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

use DateTime;
use OrangeHRM\Attendance\Exception\AttendanceServiceException;

/**
 * BR: what a justified absence has to carry, and who may settle it.
 *
 * A justified absence never touches the AFD -- that file records punches, not
 * absences. This is a parallel record, for the timesheet and for whoever has
 * to decide whether the day is discounted.
 */
class AbsenceJustificationRules
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    /**
     * The reasons whose whole evidence is the document: without the file there
     * is nothing to weigh, only a claim.
     */
    private const REASONS_NEEDING_A_DOCUMENT = [
        'ATESTADO_MEDICO',
        'DECLARACAO_COMPARECIMENTO',
    ];

    private const REASONS_WITHOUT_A_DOCUMENT = [
        'FALTA_JUSTIFICADA',
        'OUTRO',
    ];

    /**
     * @return string[] in the order the form offers them
     */
    public static function reasonTypes(): array
    {
        return array_merge(self::REASONS_NEEDING_A_DOCUMENT, self::REASONS_WITHOUT_A_DOCUMENT);
    }

    /**
     * @throws AttendanceServiceException when the request may not be filed
     */
    public static function assertSubmittable(
        string $reasonType,
        DateTime $fromDate,
        DateTime $toDate,
        bool $hasAttachment
    ): void {
        if (!in_array($reasonType, self::reasonTypes(), true)) {
            throw AttendanceServiceException::absenceReasonUnknown($reasonType);
        }

        if ($toDate < $fromDate) {
            throw AttendanceServiceException::absencePeriodInverted();
        }

        if (!$hasAttachment && in_array($reasonType, self::REASONS_NEEDING_A_DOCUMENT, true)) {
            throw AttendanceServiceException::absenceDocumentRequired();
        }
    }

    /**
     * Settling a request twice would let an approval be quietly turned into a
     * refusal after the employee was told it was accepted.
     *
     * @throws AttendanceServiceException when the request is already settled
     */
    public static function assertDecidable(string $currentStatus): void
    {
        if ($currentStatus !== self::STATUS_PENDING) {
            throw AttendanceServiceException::absenceAlreadyDecided($currentStatus);
        }
    }

    /**
     * A refusal the employee cannot read a reason for is a refusal they cannot
     * answer -- with a corrected document, or by asking.
     *
     * @throws AttendanceServiceException when a refusal carries no reason
     */
    public static function assertDecisionNote(string $decision, ?string $note): void
    {
        if ($decision !== self::STATUS_REJECTED) {
            return;
        }

        if (trim((string)$note) === '') {
            throw AttendanceServiceException::absenceRejectionNeedsReason();
        }
    }
}
