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

namespace OrangeHRM\Attendance\Service\JobFit;

use DateTime;
use OrangeHRM\Attendance\Exception\JobFitRuleException;
use OrangeHRM\Attendance\Service\Assessment\AssessmentRules;
use OrangeHRM\Attendance\Service\Assessment\InventoryCatalog;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\Assessment;
use OrangeHRM\Entity\Candidate;
use OrangeHRM\Entity\CandidateVacancy;
use OrangeHRM\Entity\CompetencyRating;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\JobCompetency;
use OrangeHRM\Entity\JobProfile;
use OrangeHRM\Entity\JobProfileFactor;
use OrangeHRM\Entity\JobTitle;
use OrangeHRM\Entity\Subunit;
use OrangeHRM\Entity\Vacancy;

/**
 * BR: job-title profiles and how people compare against them.
 *
 * A person is a candidate ("c12") or an employee ("e5"); a hired candidate's
 * profile test already points to the employee, so from then on they are the
 * employee. Each person counts with their most recent completed profile test.
 * The fit itself is JobFit's; this class fetches and keeps.
 */
class JobFitService
{
    use EntityManagerHelperTrait;

    public const TYPE_CANDIDATE = 'c';
    public const TYPE_EMPLOYEE = 'e';

    /**
     * Native job titles in use, with whether they already have a profile.
     */
    public function jobTitles(): array
    {
        $profiles = [];
        foreach ($this->getEntityManager()->getRepository(JobProfile::class)->findAll() as $profile) {
            $profiles[$profile->getJobTitle()->getId()] = $profile;
        }

        $rows = [];
        foreach ($this->getEntityManager()->getRepository(JobTitle::class)->findBy(
            ['isDeleted' => false],
            ['jobTitleName' => 'ASC']
        ) as $jobTitle) {
            $profile = $profiles[$jobTitle->getId()] ?? null;
            $rows[] = [
                'id' => $jobTitle->getId(),
                'name' => $jobTitle->getJobTitleName(),
                'hasProfile' => $profile !== null,
                'competencies' => $profile === null ? 0 : $profile->getCompetencies()->count(),
                'updatedAt' => $profile?->getUpdatedAt()->format('Y-m-d H:i'),
            ];
        }
        return $rows;
    }

    /**
     * The saved profile, or the open one a job title starts with.
     */
    public function profileFor(JobTitle $jobTitle): array
    {
        $profile = $this->findProfile($jobTitle);
        $data = $profile === null ? JobProfileRules::defaultProfile() : $this->profileArray($profile);

        return $data + [
            'exists' => $profile !== null,
            'updatedAt' => $profile?->getUpdatedAt()->format('Y-m-d H:i'),
            'jobTitle' => ['id' => $jobTitle->getId(), 'name' => $jobTitle->getJobTitleName()],
        ];
    }

    /**
     * Competencies sent back with their id keep their ratings; the ones left
     * out are deleted, ratings and all.
     *
     * @throws JobFitRuleException
     */
    public function saveProfile(JobTitle $jobTitle, array $payload, ?int $by): array
    {
        $data = JobProfileRules::normalizeProfile($payload);

        $profile = $this->findProfile($jobTitle);
        if ($profile === null) {
            $profile = new JobProfile();
            $profile->setJobTitle($jobTitle);
            $this->getEntityManager()->persist($profile);
        }
        $profile->setBehaviorWeight($data['behaviorWeight']);
        $profile->setUpdatedByEmpNumber($by);
        $profile->setUpdatedAt(new DateTime());

        $factors = [];
        foreach ($profile->getFactors() as $factor) {
            $factors[$factor->getInstrument() . '|' . $factor->getFactor()] = $factor;
        }
        foreach ($data['factors'] as $row) {
            $factor = $factors[$row['instrument'] . '|' . $row['factor']] ?? null;
            if ($factor === null) {
                $factor = new JobProfileFactor();
                $factor->setInstrument($row['instrument']);
                $factor->setFactor($row['factor']);
                $profile->addFactor($factor);
            }
            $factor->setMinScore($row['min']);
            $factor->setMaxScore($row['max']);
            $factor->setWeight($row['weight']);
        }

        $existing = [];
        foreach ($profile->getCompetencies() as $competency) {
            $existing[$competency->getId()] = $competency;
        }
        $kept = [];
        foreach ($data['competencies'] as $row) {
            $competency = $row['id'] === null ? null : ($existing[$row['id']] ?? null);
            if ($competency === null) {
                $competency = new JobCompetency();
                $profile->addCompetency($competency);
            } else {
                $kept[$competency->getId()] = true;
            }
            $competency->setName($row['name']);
            $competency->setWeight($row['weight']);
            $competency->setMinLevel($row['minLevel']);
            $competency->setSortOrder($row['sortOrder']);
        }
        foreach ($existing as $id => $competency) {
            if (!isset($kept[$id])) {
                $profile->removeCompetency($competency);
            }
        }

        $this->getEntityManager()->flush();
        $this->getEntityManager()->refresh($profile);
        return $this->profileFor($jobTitle);
    }

    /**
     * The most recent completed profile test of a person.
     *
     * @return array{assessmentId: int, completedAt: string, scores: array}|null
     */
    public function latestScores(string $type, int $id): ?array
    {
        $assessment = $this->getEntityManager()->getRepository(Assessment::class)->findOneBy(
            [
                $type === self::TYPE_EMPLOYEE ? 'employee' : 'candidate' => $id,
                'status' => AssessmentRules::STATUS_COMPLETED,
            ],
            ['completedAt' => 'DESC', 'id' => 'DESC']
        );
        if (!$assessment instanceof Assessment) {
            return null;
        }
        return [
            'assessmentId' => $assessment->getId(),
            'completedAt' => $assessment->getCompletedAt()?->format('Y-m-d H:i'),
            'scores' => $this->scoresOf($assessment),
        ];
    }

    /**
     * Everyone who can be compared: one row per person with a completed
     * profile test.
     *
     * @param array{type?: ?string, vacancyId?: ?int, subunitId?: ?int, name?: ?string} $filters
     */
    public function people(array $filters): array
    {
        $type = $filters['type'] ?? null;
        $vacancyId = $filters['vacancyId'] ?? null;
        $name = trim((string)($filters['name'] ?? ''));
        $subunit = empty($filters['subunitId'])
            ? null
            : $this->getEntityManager()->find(Subunit::class, (int)$filters['subunitId']);

        $assessments = $this->getEntityManager()->getRepository(Assessment::class)->findBy(
            ['status' => AssessmentRules::STATUS_COMPLETED],
            ['completedAt' => 'DESC', 'id' => 'DESC']
        );

        $rows = [];
        foreach ($assessments as $assessment) {
            [$personType, $person] = $this->personOf($assessment);
            if ($person === null) {
                continue;
            }
            $key = $personType . $this->personId($person);
            if (isset($rows[$key])) {
                continue;
            }
            if ($person instanceof Employee && $person->getPurgedAt() !== null) {
                continue;
            }
            if ($type !== null && $type !== $personType) {
                continue;
            }
            if ($subunit instanceof Subunit && !$this->withinSubunit($person, $subunit)) {
                continue;
            }
            $fullName = $this->fullName($person);
            if ($name !== '' && mb_stripos($fullName, $name) === false) {
                continue;
            }
            $vacancies = $this->vacanciesOf($assessment);
            if ($vacancyId !== null && !isset($vacancies[$vacancyId])) {
                continue;
            }
            $rows[$key] = [
                'key' => $key,
                'type' => $personType,
                'id' => $this->personId($person),
                'name' => $fullName,
                'subunit' => $person instanceof Employee ? $person->getSubDivision()?->getName() : null,
                'vacancies' => array_values($vacancies),
                'completedAt' => $assessment->getCompletedAt()?->format('Y-m-d H:i'),
                'assessmentId' => $assessment->getId(),
            ];
        }

        $rows = array_values($rows);
        usort($rows, static fn (array $a, array $b) => strcmp(mb_strtolower($a['name']), mb_strtolower($b['name'])));
        return $rows;
    }

    /**
     * @param int[] $empNumbers
     * @throws JobFitRuleException when someone has no completed profile test
     */
    public function suggest(array $empNumbers): array
    {
        $scoreSets = [];
        foreach ($empNumbers as $empNumber) {
            $latest = $this->latestScores(self::TYPE_EMPLOYEE, $empNumber);
            if ($latest === null) {
                $employee = $this->getEntityManager()->find(Employee::class, $empNumber);
                throw JobFitRuleException::because(
                    ($employee instanceof Employee ? $this->fullName($employee) : 'Funcionario ' . $empNumber)
                    . ' ainda nao concluiu o teste de perfil.'
                );
            }
            $scoreSets[] = $latest['scores'];
        }
        return JobProfileSuggestion::suggest($scoreSets);
    }

    /**
     * @param array[] $subjects from JobProfileRules::parseSubjects
     * @throws JobFitRuleException
     */
    public function compare(JobTitle $jobTitle, array $subjects): array
    {
        $profile = $this->findProfile($jobTitle);
        if ($profile === null) {
            throw JobFitRuleException::because('Este cargo ainda nao tem perfil definido.');
        }
        $profileData = $this->profileArray($profile);
        $competencyIds = array_column($profileData['competencies'], 'id');

        $people = [];
        foreach ($subjects as $subject) {
            $person = $this->findPerson($subject['type'], $subject['id']);
            $latest = $this->latestScores($subject['type'], $subject['id']);
            if ($latest === null) {
                throw JobFitRuleException::because($this->fullName($person) . ' ainda nao concluiu o teste de perfil.');
            }
            $ratings = $this->ratingsOf($subject['type'], $subject['id'], $competencyIds);
            $people[] = [
                'key' => $subject['type'] . $subject['id'],
                'type' => $subject['type'],
                'id' => $subject['id'],
                'name' => $this->fullName($person),
                'subunit' => $person instanceof Employee ? $person->getSubDivision()?->getName() : null,
                'assessmentId' => $latest['assessmentId'],
                'completedAt' => $latest['completedAt'],
                'scores' => $latest['scores'],
                'ratings' => $ratings,
            ] + JobFit::evaluate($profileData, $latest['scores'], $ratings);
        }

        return [
            'jobTitle' => ['id' => $jobTitle->getId(), 'name' => $jobTitle->getJobTitleName()],
            'profile' => $profileData,
            'people' => JobFit::rank($people),
        ];
    }

    /**
     * Sets (1..5) or clears (null) a person's rating in a competency.
     *
     * @throws JobFitRuleException
     */
    public function rate(string $type, int $id, JobCompetency $competency, ?int $rating, ?int $by): void
    {
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            throw JobFitRuleException::because('A nota vai de 1 a 5.');
        }
        $person = $this->findPerson($type, $id);
        $field = $type === self::TYPE_EMPLOYEE ? 'employee' : 'candidate';
        $row = $this->getEntityManager()->getRepository(CompetencyRating::class)
            ->findOneBy(['competency' => $competency, $field => $person]);

        if ($rating === null) {
            if ($row instanceof CompetencyRating) {
                $this->getEntityManager()->remove($row);
                $this->getEntityManager()->flush();
            }
            return;
        }
        if (!$row instanceof CompetencyRating) {
            $row = new CompetencyRating();
            $row->setCompetency($competency);
            if ($person instanceof Employee) {
                $row->setEmployee($person);
            } else {
                $row->setCandidate($person);
            }
            $this->getEntityManager()->persist($row);
        }
        $row->setRating($rating);
        $row->setRatedByEmpNumber($by);
        $row->setRatedAt(new DateTime());
        $this->getEntityManager()->flush();
    }

    /**
     * A hired candidate's ratings follow them to the employee -- unless the
     * employee was already rated in that competency.
     */
    public function moveRatingsToEmployee(Candidate $candidate, Employee $employee): void
    {
        $taken = [];
        foreach ($this->getEntityManager()->getRepository(CompetencyRating::class)->findBy(['employee' => $employee]) as $row) {
            $taken[$row->getCompetency()->getId()] = true;
        }
        foreach ($this->getEntityManager()->getRepository(CompetencyRating::class)->findBy([
            'candidate' => $candidate,
            'employee' => null,
        ]) as $row) {
            if (!isset($taken[$row->getCompetency()->getId()])) {
                $row->setEmployee($employee);
            }
        }
        $this->getEntityManager()->flush();
    }

    public function jobTitleIdForVacancy(int $vacancyId): ?int
    {
        $vacancy = $this->getEntityManager()->find(Vacancy::class, $vacancyId);
        return $vacancy instanceof Vacancy ? $vacancy->getJobTitle()->getId() : null;
    }

    private function findProfile(JobTitle $jobTitle): ?JobProfile
    {
        return $this->getEntityManager()->getRepository(JobProfile::class)->findOneBy(['jobTitle' => $jobTitle]);
    }

    private function profileArray(JobProfile $profile): array
    {
        $byKey = [];
        foreach ($profile->getFactors() as $factor) {
            $byKey[$factor->getInstrument() . '|' . $factor->getFactor()] = $factor;
        }
        $factors = [];
        foreach (InventoryCatalog::INSTRUMENTS as $instrument) {
            foreach (InventoryCatalog::factors($instrument) as $code) {
                $factor = $byKey[$instrument . '|' . $code] ?? null;
                $factors[] = [
                    'instrument' => $instrument,
                    'factor' => $code,
                    'min' => $factor?->getMinScore() ?? 0,
                    'max' => $factor?->getMaxScore() ?? 100,
                    'weight' => $factor?->getWeight() ?? JobFit::WEIGHT_IGNORE,
                ];
            }
        }

        $competencies = [];
        foreach ($profile->getCompetencies() as $competency) {
            $competencies[] = [
                'id' => $competency->getId(),
                'name' => $competency->getName(),
                'weight' => $competency->getWeight(),
                'minLevel' => $competency->getMinLevel(),
            ];
        }

        return [
            'behaviorWeight' => $profile->getBehaviorWeight(),
            'factors' => $factors,
            'competencies' => $competencies,
        ];
    }

    private function scoresOf(Assessment $assessment): array
    {
        $scores = [];
        foreach ($assessment->getResults() as $result) {
            $scores[$result->getInstrument()][$result->getFactor()] = $result->getScore();
        }
        return $scores;
    }

    /**
     * @return array<int, int> competency id => rating
     */
    private function ratingsOf(string $type, int $id, array $competencyIds): array
    {
        if ($competencyIds === []) {
            return [];
        }
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(r.competency) AS competency', 'r.rating')
            ->from(CompetencyRating::class, 'r')
            ->where($type === self::TYPE_EMPLOYEE ? 'r.employee = :person' : 'r.candidate = :person')
            ->andWhere('r.competency IN (:ids)')
            ->setParameter('person', $id)
            ->setParameter('ids', $competencyIds)
            ->getQuery()
            ->getArrayResult();

        $ratings = [];
        foreach ($rows as $row) {
            $ratings[(int)$row['competency']] = (int)$row['rating'];
        }
        return $ratings;
    }

    /**
     * @return Candidate|Employee
     * @throws JobFitRuleException
     */
    private function findPerson(string $type, int $id)
    {
        $person = $this->getEntityManager()->find(
            $type === self::TYPE_EMPLOYEE ? Employee::class : Candidate::class,
            $id
        );
        if ($person === null) {
            throw JobFitRuleException::because('Pessoa nao encontrada.');
        }
        return $person;
    }

    /**
     * @return array{0: string, 1: Candidate|Employee|null}
     */
    private function personOf(Assessment $assessment): array
    {
        if ($assessment->getEmployee() instanceof Employee) {
            return [self::TYPE_EMPLOYEE, $assessment->getEmployee()];
        }
        return [self::TYPE_CANDIDATE, $assessment->getCandidate()];
    }

    /**
     * @param Candidate|Employee $person
     */
    private function personId($person): int
    {
        return $person instanceof Employee ? $person->getEmpNumber() : $person->getId();
    }

    /**
     * @param Candidate|Employee $person
     */
    private function fullName($person): string
    {
        return trim($person->getFirstName() . ' ' . $person->getLastName());
    }

    /**
     * @param Candidate|Employee $person
     */
    private function withinSubunit($person, Subunit $subunit): bool
    {
        if (!$person instanceof Employee || !$person->getSubDivision() instanceof Subunit) {
            return false;
        }
        $own = $person->getSubDivision();
        return $own->getLft() >= $subunit->getLft() && $own->getRgt() <= $subunit->getRgt();
    }

    /**
     * The vacancies behind a profile: the one it was sent for and every one
     * the candidate applied to.
     *
     * @return array<int, string> id => name
     */
    private function vacanciesOf(Assessment $assessment): array
    {
        $vacancies = [];
        if ($assessment->getVacancy() instanceof Vacancy) {
            $vacancies[$assessment->getVacancy()->getId()] = $assessment->getVacancy()->getName();
        }
        if ($assessment->getCandidate() instanceof Candidate) {
            foreach ($assessment->getCandidate()->getCandidateVacancy() as $candidateVacancy) {
                if ($candidateVacancy instanceof CandidateVacancy) {
                    $vacancies[$candidateVacancy->getVacancy()->getId()] = $candidateVacancy->getVacancy()->getName();
                }
            }
        }
        return $vacancies;
    }
}
