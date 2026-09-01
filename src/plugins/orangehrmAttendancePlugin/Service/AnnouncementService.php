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
use OrangeHRM\Attendance\Traits\Service\SubunitChainTrait;
use OrangeHRM\Entity\Announcement;
use OrangeHRM\Entity\AnnouncementReceipt;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\Subunit;

/**
 * BR: notices from HR, and what each employee did with them.
 *
 * Reach is decided in memory by AnnouncementAudience rather than in SQL: the
 * rule has to be the same one the tests exercise, and the volume here is a
 * handful of live notices, not a feed.
 */
class AnnouncementService
{
    use SubunitChainTrait;

    /**
     * Notices currently addressed to this employee, newest first.
     *
     * @param Employee $employee
     * @return Announcement[]
     */
    public function getInboxFor(Employee $employee): array
    {
        $now = new DateTime();
        $chainIds = $this->getChainIds($employee);
        $empNumber = $employee->getEmpNumber();

        $candidates = $this->getEntityManager()->createQueryBuilder()
            ->select('a')
            ->from(Announcement::class, 'a')
            ->where('a.publishedAt <= :now')
            ->andWhere('a.expiresAt IS NULL OR a.expiresAt >= :now')
            ->setParameter('now', $now)
            ->orderBy('a.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return array_values(array_filter(
            $candidates,
            fn (Announcement $a) => AnnouncementAudience::reaches(
                $a->getScope(),
                $a->getSubunit()?->getId(),
                $a->getEmployee()?->getEmpNumber(),
                $chainIds,
                $empNumber
            )
        ));
    }

    /**
     * The reader's unit and its ancestors, as ids.
     *
     * @param Employee $employee
     * @return int[]
     */
    public function getChainIds(Employee $employee): array
    {
        $subunit = $employee->getSubDivision();
        if (!$subunit instanceof Subunit) {
            return [];
        }
        return array_map(
            fn (Subunit $unit) => $unit->getId(),
            $this->getSubunitChain($subunit)
        );
    }

    /**
     * @param Announcement $announcement
     * @param Employee $employee
     * @return AnnouncementReceipt
     */
    public function getOrCreateReceipt(Announcement $announcement, Employee $employee): AnnouncementReceipt
    {
        $receipt = $this->getEntityManager()
            ->getRepository(AnnouncementReceipt::class)
            ->findOneBy(['announcement' => $announcement, 'employee' => $employee]);

        if ($receipt instanceof AnnouncementReceipt) {
            return $receipt;
        }

        $receipt = new AnnouncementReceipt();
        $receipt->setAnnouncement($announcement);
        $receipt->setEmployee($employee);
        $this->getEntityManager()->persist($receipt);
        $this->getEntityManager()->flush();

        return $receipt;
    }

    /**
     * Opening the notice. Only the first time counts -- the question the
     * receipt answers is when it reached the employee, not how often they
     * looked at it.
     */
    public function markRead(Announcement $announcement, Employee $employee): AnnouncementReceipt
    {
        $receipt = $this->getOrCreateReceipt($announcement, $employee);
        if ($receipt->getReadAt() === null) {
            $receipt->setReadAt(new DateTime());
            $this->getEntityManager()->flush();
        }
        return $receipt;
    }

    /**
     * Pressing "estou ciente". Deliberate, and dated -- this is what turns
     * "we told them" into something that can be shown.
     */
    public function acknowledge(Announcement $announcement, Employee $employee): AnnouncementReceipt
    {
        $receipt = $this->getOrCreateReceipt($announcement, $employee);
        if ($receipt->getReadAt() === null) {
            $receipt->setReadAt(new DateTime());
        }
        if ($receipt->getAcknowledgedAt() === null) {
            $receipt->setAcknowledgedAt(new DateTime());
        }
        $this->getEntityManager()->flush();
        return $receipt;
    }

    /**
     * Whether this employee still owes an acknowledgement.
     */
    public function isPendingAck(Announcement $announcement, ?AnnouncementReceipt $receipt): bool
    {
        return $announcement->isRequiresAck()
            && ($receipt === null || $receipt->getAcknowledgedAt() === null);
    }

    /**
     * Receipts for one notice, for the HR side.
     *
     * @param Announcement $announcement
     * @return AnnouncementReceipt[]
     */
    public function getReceipts(Announcement $announcement): array
    {
        return $this->getEntityManager()
            ->getRepository(AnnouncementReceipt::class)
            ->findBy(['announcement' => $announcement]);
    }

    /**
     * @param Employee $employee
     * @return AnnouncementReceipt[] keyed by announcement id
     */
    public function getReceiptsFor(Employee $employee): array
    {
        $receipts = $this->getEntityManager()
            ->getRepository(AnnouncementReceipt::class)
            ->findBy(['employee' => $employee]);

        $byAnnouncement = [];
        foreach ($receipts as $receipt) {
            $byAnnouncement[$receipt->getAnnouncement()->getId()] = $receipt;
        }
        return $byAnnouncement;
    }
}
