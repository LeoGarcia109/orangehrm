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
use OrangeHRM\Attendance\Service\Form\FormService;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\Assessment;
use OrangeHRM\Entity\AssessmentAnswer;
use OrangeHRM\Entity\AssessmentResult;
use OrangeHRM\Entity\Candidate;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\Subunit;
use OrangeHRM\Entity\Vacancy;

/**
 * BR: behavioural-assessment invites -- creating them, answering them
 * (through the candidate's link or the employee's app), and the profile
 * they produce.
 *
 * The rules live in the pure classes next to this one; this class keeps the
 * invite, the answers and the results, and ties them to the native
 * recruitment records.
 */
class AssessmentService
{
    use EntityManagerHelperTrait;

    public const DEFAULT_INSTRUMENTS = [InventoryCatalog::BIG5, InventoryCatalog::DISC];

    /**
     * A candidate gets one open invite at a time: inviting again re-issues the
     * pending one with a fresh link instead of piling up duplicates.
     *
     * @return array{0: Assessment, 1: string} the invite and its plain token
     */
    public function inviteCandidate(Candidate $candidate, ?Vacancy $vacancy, ?int $createdBy, DateTime $now): array
    {
        $pending = $this->getEntityManager()->getRepository(Assessment::class)->findOneBy([
            'candidate' => $candidate,
            'status' => [AssessmentRules::STATUS_PENDING, AssessmentRules::STATUS_EXPIRED],
        ], ['createdAt' => 'DESC']);

        if ($pending instanceof Assessment) {
            if ($vacancy instanceof Vacancy) {
                $pending->setVacancy($vacancy);
            }
            return [$pending, $this->resend($pending, $now)];
        }

        $assessment = new Assessment();
        $assessment->setSubjectType(AssessmentRules::SUBJECT_CANDIDATE);
        $assessment->setCandidate($candidate);
        $assessment->setVacancy($vacancy);
        $assessment->setInstruments(implode(',', self::DEFAULT_INSTRUMENTS));
        $assessment->setCreatedByEmpNumber($createdBy);
        $token = $this->issueToken($assessment, $now);
        $this->getEntityManager()->persist($assessment);
        $this->getEntityManager()->flush();

        return [$assessment, $token];
    }

    /**
     * One invite per active employee the audience reaches -- the same reach as
     * forms and notices. Whoever already has one open is skipped.
     *
     * @return int invites created
     */
    public function inviteEmployees(string $scope, ?int $subunitId, ?int $employeeId, ?int $createdBy): int
    {
        $subunit = $subunitId === null ? null : $this->getEntityManager()->find(Subunit::class, $subunitId);
        $target = $employeeId === null ? null : $this->getEntityManager()->find(Employee::class, $employeeId);
        $created = 0;

        foreach ((new FormService())->audienceFor($scope, $subunit, $target) as $employee) {
            $open = $this->getEntityManager()->getRepository(Assessment::class)->findOneBy([
                'employee' => $employee,
                'subjectType' => AssessmentRules::SUBJECT_EMPLOYEE,
                'status' => AssessmentRules::STATUS_PENDING,
            ]);
            if ($open instanceof Assessment) {
                continue;
            }
            $assessment = new Assessment();
            $assessment->setSubjectType(AssessmentRules::SUBJECT_EMPLOYEE);
            $assessment->setEmployee($employee);
            $assessment->setInstruments(implode(',', self::DEFAULT_INSTRUMENTS));
            $assessment->setCreatedByEmpNumber($createdBy);
            $this->getEntityManager()->persist($assessment);
            $created++;
        }
        $this->getEntityManager()->flush();

        return $created;
    }

    /**
     * A new link for a candidate invite; the old one stops working at once.
     *
     * @throws AssessmentRuleException
     */
    public function resend(Assessment $assessment, DateTime $now): string
    {
        if ($assessment->getSubjectType() !== AssessmentRules::SUBJECT_CANDIDATE
            || $assessment->getStatus() === AssessmentRules::STATUS_COMPLETED) {
            throw AssessmentRuleException::because('So convite de candidato ainda nao respondido pode ser reenviado.');
        }
        $assessment->setStatus(AssessmentRules::STATUS_PENDING);
        $token = $this->issueToken($assessment, $now);
        $this->getEntityManager()->flush();
        return $token;
    }

    public function cancel(Assessment $assessment): void
    {
        if ($assessment->getStatus() === AssessmentRules::STATUS_COMPLETED) {
            throw AssessmentRuleException::because('Um perfil ja respondido nao e cancelado.');
        }
        $assessment->setStatus(AssessmentRules::STATUS_CANCELLED);
        $assessment->setTokenHash(null);
        $this->getEntityManager()->flush();
    }

    /**
     * The invite behind a public link, if it can still be answered. Anything
     * else -- malformed, unknown, used, cancelled, expired -- is null, and the
     * page says the same thing in every case.
     */
    public function findOpenByToken(string $token, DateTime $now): ?Assessment
    {
        if (!AssessmentToken::looksValid($token)) {
            return null;
        }
        $assessment = $this->getEntityManager()->getRepository(Assessment::class)
            ->findOneBy(['tokenHash' => AssessmentToken::hash($token)]);
        if (!$assessment instanceof Assessment) {
            return null;
        }
        if (AssessmentRules::isExpired($assessment->getStatus(), $assessment->getExpiresAt(), $now)) {
            $assessment->setStatus(AssessmentRules::STATUS_EXPIRED);
            $this->getEntityManager()->flush();
        }
        return $assessment->getStatus() === AssessmentRules::STATUS_PENDING ? $assessment : null;
    }

    /**
     * The candidate's consent, dated, with where it came from -- and the
     * native "consent to keep data" flag, so the recruitment purge honours it.
     */
    public function giveConsent(Assessment $assessment, ?string $ip, ?string $userAgent, DateTime $now): void
    {
        AssessmentRules::assertOpen($assessment->getStatus(), $assessment->getExpiresAt(), $now);
        if ($assessment->getConsentAt() === null) {
            $assessment->setConsentAt(clone $now);
            $assessment->setConsentIp($ip === null ? null : substr($ip, 0, 45));
            $assessment->setConsentUserAgent($userAgent === null ? null : mb_substr($userAgent, 0, 255));
        }
        $assessment->getCandidate()?->setConsentToKeepData(true);
        $this->getEntityManager()->flush();
    }

    /**
     * Saves a page of answers; answering the same statement again replaces it.
     *
     * @param array<string, mixed> $answers item code => 1..5
     * @throws AssessmentRuleException
     */
    public function saveAnswers(Assessment $assessment, array $answers, DateTime $now): void
    {
        AssessmentRules::assertOpen($assessment->getStatus(), $assessment->getExpiresAt(), $now);
        AssessmentRules::assertConsent($assessment->getSubjectType(), $assessment->getConsentAt());

        $allowed = array_flip(InventoryCatalog::sequence($this->instruments($assessment)));
        $existing = [];
        foreach ($assessment->getAnswers() as $row) {
            $existing[$row->getItemCode()] = $row;
        }

        foreach ($answers as $code => $value) {
            if (!isset($allowed[$code])) {
                throw AssessmentRuleException::because('Frase que nao faz parte deste questionario.');
            }
            if (!is_int($value) || $value < 1 || $value > 5) {
                throw AssessmentRuleException::because('Resposta fora da escala de 1 a 5.');
            }
            $row = $existing[$code] ?? null;
            if (!$row instanceof AssessmentAnswer) {
                $row = new AssessmentAnswer();
                $row->setItemCode($code);
                $assessment->addAnswer($row);
                $existing[$code] = $row;
            }
            $row->setValue($value);
            $row->setAnsweredAt(clone $now);
        }
        if ($assessment->getStartedAt() === null) {
            $assessment->setStartedAt(clone $now);
        }
        $this->getEntityManager()->flush();
    }

    /**
     * Scores every instrument, keeps the results, and closes the invite --
     * the link dies with it.
     *
     * @throws AssessmentRuleException when a statement is missing
     */
    public function complete(Assessment $assessment, DateTime $now): void
    {
        AssessmentRules::assertOpen($assessment->getStatus(), $assessment->getExpiresAt(), $now);
        AssessmentRules::assertConsent($assessment->getSubjectType(), $assessment->getConsentAt());

        $answers = $this->answerMap($assessment);
        foreach ($this->instruments($assessment) as $instrument) {
            foreach (InventoryScoring::score($instrument, $answers) as $factor => $score) {
                $result = new AssessmentResult();
                $result->setInstrument($instrument);
                $result->setVersion(InventoryCatalog::version($instrument));
                $result->setFactor($factor);
                $result->setRaw($score['raw']);
                $result->setScore($score['score']);
                $assessment->addResult($result);
            }
        }
        $assessment->setStatus(AssessmentRules::STATUS_COMPLETED);
        $assessment->setCompletedAt(clone $now);
        $assessment->setTokenHash(null);
        $this->getEntityManager()->flush();
    }

    /**
     * What the answering screen needs -- and nothing about the person beyond
     * a first name, since whoever holds the link sees it.
     */
    public function state(Assessment $assessment): array
    {
        $instruments = $this->instruments($assessment);
        $answers = $this->answerMap($assessment);
        $pages = array_map(
            static fn (array $codes) => array_map(
                static fn (string $code) => ['code' => $code, 'text' => InventoryCatalog::item($code)['text']],
                $codes
            ),
            InventoryCatalog::pages($instruments)
        );

        $nextPage = count($pages) - 1;
        foreach ($pages as $index => $page) {
            foreach ($page as $item) {
                if (!isset($answers[$item['code']])) {
                    $nextPage = $index;
                    break 2;
                }
            }
        }

        $person = $assessment->getCandidate() ?? $assessment->getEmployee();
        return [
            'id' => $assessment->getId(),
            'subjectType' => $assessment->getSubjectType(),
            'firstName' => $person?->getFirstName(),
            'vacancyName' => $assessment->getVacancy()?->getName(),
            'consentGiven' => $assessment->getConsentAt() !== null,
            'instruments' => $instruments,
            'pages' => $pages,
            'answers' => $answers,
            'nextPage' => $nextPage,
        ];
    }

    /**
     * The profile HR reads.
     */
    public function profile(Assessment $assessment): array
    {
        $byInstrument = [];
        foreach ($assessment->getResults() as $result) {
            $byInstrument[$result->getInstrument()]['version'] = $result->getVersion();
            $byInstrument[$result->getInstrument()]['factors'][$result->getFactor()] = [
                'factor' => $result->getFactor(),
                'raw' => $result->getRaw(),
                'score' => $result->getScore(),
                'band' => InventoryScoring::band($result->getScore()),
            ];
        }
        foreach ($byInstrument as $instrument => $data) {
            $order = InventoryCatalog::factors($instrument);
            $factors = [];
            foreach ($order as $factor) {
                if (isset($data['factors'][$factor])) {
                    $factors[] = $data['factors'][$factor];
                }
            }
            $byInstrument[$instrument]['factors'] = $factors;
            if ($instrument === InventoryCatalog::DISC) {
                $byInstrument[$instrument]['styles'] = InventoryScoring::discStyles(
                    array_column($factors, 'score', 'factor')
                );
            }
        }

        return $this->summary($assessment) + ['instruments' => $byInstrument];
    }

    /**
     * A hired candidate's profiles follow them to the employee the hiring
     * created.
     */
    public function linkHiredEmployee(Candidate $candidate, Employee $employee): void
    {
        foreach ($this->getEntityManager()->getRepository(Assessment::class)->findBy([
            'candidate' => $candidate,
            'status' => AssessmentRules::STATUS_COMPLETED,
        ]) as $assessment) {
            $assessment->setEmployee($employee);
        }
        $this->getEntityManager()->flush();
    }

    /**
     * @return Assessment[] open invites of this employee
     */
    public function pendingForEmployee(Employee $employee, DateTime $now): array
    {
        return $this->getEntityManager()->getRepository(Assessment::class)->findBy([
            'employee' => $employee,
            'subjectType' => AssessmentRules::SUBJECT_EMPLOYEE,
            'status' => AssessmentRules::STATUS_PENDING,
        ], ['createdAt' => 'DESC']);
    }

    /**
     * @param array{subjectType?: string, status?: string, vacancyId?: int} $filters
     */
    public function listForHr(array $filters): array
    {
        $criteria = [];
        if (!empty($filters['subjectType'])) {
            $criteria['subjectType'] = $filters['subjectType'];
        }
        if (!empty($filters['status'])) {
            $criteria['status'] = $filters['status'];
        }
        if (!empty($filters['vacancyId'])) {
            $criteria['vacancy'] = (int)$filters['vacancyId'];
        }
        $now = new DateTime();
        return array_map(function (Assessment $assessment) use ($now) {
            // Shown as expired as soon as it is, even before anyone touches the link
            $row = $this->summary($assessment);
            if (AssessmentRules::isExpired($assessment->getStatus(), $assessment->getExpiresAt(), $now)) {
                $row['status'] = AssessmentRules::STATUS_EXPIRED;
            }
            return $row;
        }, $this->getEntityManager()->getRepository(Assessment::class)->findBy($criteria, ['createdAt' => 'DESC']));
    }

    private function summary(Assessment $assessment): array
    {
        $person = $assessment->getSubjectType() === AssessmentRules::SUBJECT_CANDIDATE
            ? $assessment->getCandidate()
            : $assessment->getEmployee();
        return [
            'id' => $assessment->getId(),
            'subjectType' => $assessment->getSubjectType(),
            'candidateId' => $assessment->getCandidate()?->getId(),
            'employeeId' => $assessment->getEmployee()?->getEmpNumber(),
            'name' => $person === null ? null : trim($person->getFirstName() . ' ' . $person->getLastName()),
            'phone' => $assessment->getCandidate()?->getContactNumber(),
            'vacancyId' => $assessment->getVacancy()?->getId(),
            'vacancyName' => $assessment->getVacancy()?->getName(),
            'status' => $assessment->getStatus(),
            'createdAt' => $assessment->getCreatedAt()->format('Y-m-d H:i'),
            'expiresAt' => $assessment->getExpiresAt()?->format('Y-m-d H:i'),
            'completedAt' => $assessment->getCompletedAt()?->format('Y-m-d H:i'),
            'hasLink' => $assessment->getTokenHash() !== null,
        ];
    }

    /**
     * @return string[]
     */
    private function instruments(Assessment $assessment): array
    {
        return array_values(array_filter(explode(',', $assessment->getInstruments())));
    }

    /**
     * @return array<string, int>
     */
    private function answerMap(Assessment $assessment): array
    {
        $map = [];
        foreach ($assessment->getAnswers() as $row) {
            $map[$row->getItemCode()] = $row->getValue();
        }
        return $map;
    }

    private function issueToken(Assessment $assessment, DateTime $now): string
    {
        $token = AssessmentToken::generate();
        $assessment->setTokenHash(AssessmentToken::hash($token));
        $assessment->setExpiresAt(AssessmentRules::expiryFrom($now));
        return $token;
    }
}
