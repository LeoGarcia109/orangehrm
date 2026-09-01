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
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\AbsenceAttachment;
use OrangeHRM\Entity\AbsenceJustification;
use OrangeHRM\Entity\Employee;

/**
 * BR: absences the employee explained, and HR settling them.
 *
 * The AFD is deliberately untouched: it records punches, not absences. What
 * lands here is a parallel record, for the timesheet and for whoever decides
 * whether the day is discounted.
 */
class AbsenceJustificationService
{
    use EntityManagerHelperTrait;

    /**
     * File a justification, with its document when the reason demands one.
     *
     * @param array{filename: string, fileType: string, content: string}|null $attachment
     * @throws \OrangeHRM\Attendance\Exception\AttendanceServiceException
     */
    public function submit(
        Employee $employee,
        string $reasonType,
        DateTime $fromDate,
        DateTime $toDate,
        ?string $note,
        ?array $attachment
    ): AbsenceJustification {
        AbsenceJustificationRules::assertSubmittable(
            $reasonType,
            $fromDate,
            $toDate,
            $attachment !== null
        );

        $justification = new AbsenceJustification();
        $justification->setEmployee($employee);
        $justification->setReasonType($reasonType);
        $justification->setFromDate($fromDate);
        $justification->setToDate($toDate);
        $justification->setNote($note);
        $justification->setStatus(AbsenceJustificationRules::STATUS_PENDING);

        $this->getEntityManager()->persist($justification);
        $this->getEntityManager()->flush();

        if ($attachment !== null) {
            $this->attach($justification, $attachment);
        }

        return $justification;
    }

    /**
     * @param array{filename: string, fileType: string, content: string} $attachment
     */
    public function attach(AbsenceJustification $justification, array $attachment): AbsenceAttachment
    {
        $file = new AbsenceAttachment();
        $file->setJustification($justification);
        $file->setFilename($attachment['filename']);
        $file->setFileType($attachment['fileType']);
        $file->setContent($attachment['content']);
        $file->setFileSize(strlen($attachment['content']));

        $this->getEntityManager()->persist($file);
        $this->getEntityManager()->flush();

        return $file;
    }

    /**
     * HR settles the request. Approving needs no words; refusing does, because
     * a refusal the employee cannot read a reason for is one they cannot
     * answer -- with a corrected document, or by asking.
     *
     * @throws \OrangeHRM\Attendance\Exception\AttendanceServiceException
     */
    public function decide(
        AbsenceJustification $justification,
        string $decision,
        ?string $decisionNote,
        ?int $decidedByEmpNumber
    ): AbsenceJustification {
        AbsenceJustificationRules::assertDecidable($justification->getStatus());
        AbsenceJustificationRules::assertDecisionNote($decision, $decisionNote);

        $justification->setStatus($decision);
        $justification->setDecisionNote($decisionNote);
        $justification->setDecidedByEmpNumber($decidedByEmpNumber);
        $justification->setDecidedAt(new DateTime());

        $this->getEntityManager()->flush();

        return $justification;
    }

    /**
     * @return AbsenceJustification[]
     */
    public function getForEmployee(Employee $employee): array
    {
        return $this->getEntityManager()
            ->getRepository(AbsenceJustification::class)
            ->findBy(['employee' => $employee], ['fromDate' => 'DESC']);
    }

    /**
     * @param string|null $status null lists every request
     * @return AbsenceJustification[]
     */
    public function getByStatus(?string $status): array
    {
        $criteria = $status === null ? [] : ['status' => $status];
        return $this->getEntityManager()
            ->getRepository(AbsenceJustification::class)
            ->findBy($criteria, ['createdAt' => 'DESC']);
    }

    public function getAttachment(AbsenceJustification $justification): ?AbsenceAttachment
    {
        return $this->getEntityManager()
            ->getRepository(AbsenceAttachment::class)
            ->findOneBy(['justification' => $justification]);
    }
}
