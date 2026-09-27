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

namespace OrangeHRM\Attendance\Service\Assessment;

use DateTime;
use OrangeHRM\Attendance\Exception\AssessmentRuleException;

/**
 * BR: when an assessment invite may be answered.
 */
final class AssessmentRules
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const SUBJECT_CANDIDATE = 'CANDIDATE';
    public const SUBJECT_EMPLOYEE = 'EMPLOYEE';

    public const LINK_DAYS = 7;

    /**
     * One message whatever the reason, so a link that stopped working says
     * nothing about the invite behind it.
     *
     * @throws AssessmentRuleException
     */
    public static function assertOpen(string $status, ?DateTime $expiresAt, DateTime $now): void
    {
        if ($status !== self::STATUS_PENDING || self::isExpired($status, $expiresAt, $now)) {
            throw AssessmentRuleException::because('Este link nao esta mais ativo. Fale com o RH.');
        }
    }

    public static function isExpired(string $status, ?DateTime $expiresAt, DateTime $now): bool
    {
        return $status === self::STATUS_PENDING && $expiresAt instanceof DateTime && $now > $expiresAt;
    }

    /**
     * @throws AssessmentRuleException
     */
    public static function assertConsent(string $subjectType, ?DateTime $consentAt): void
    {
        if ($subjectType === self::SUBJECT_CANDIDATE && $consentAt === null) {
            throw AssessmentRuleException::because('Confirme o consentimento antes de comecar.');
        }
    }

    /**
     * @param string[] $instruments
     * @throws AssessmentRuleException
     */
    public static function assertInstruments(array $instruments): void
    {
        if ($instruments === [] || count($instruments) !== count(array_unique($instruments))) {
            throw AssessmentRuleException::because('Escolha os inventarios do convite.');
        }
        foreach ($instruments as $instrument) {
            if (!in_array($instrument, InventoryCatalog::INSTRUMENTS, true)) {
                throw AssessmentRuleException::because('Inventario desconhecido.');
            }
        }
    }

    public static function expiryFrom(DateTime $now): DateTime
    {
        return (clone $now)->modify('+' . self::LINK_DAYS . ' days');
    }
}
