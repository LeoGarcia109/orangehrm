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

namespace OrangeHRM\Attendance\Service\Form;

use DateTime;
use OrangeHRM\Attendance\Exception\FormRuleException;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\Form;
use OrangeHRM\Entity\FormAnswer;
use OrangeHRM\Entity\FormCompletion;
use OrangeHRM\Entity\FormRetake;
use OrangeHRM\Entity\FormSubmission;

/**
 * BR: HR's view of a form's answers -- who is missing, how each question
 * went, each person's grade -- and settling what needs a person: grading
 * written answers and allowing another attempt.
 *
 * Only the latest attempt of each person counts. An anonymous survey shows no
 * people at all, and nothing until it has enough answers to hide in.
 */
class FormResultService
{
    use EntityManagerHelperTrait;

    private FormService $formService;

    public function __construct()
    {
        $this->formService = new FormService();
    }

    /**
     * @param array<int|string>|null $accessibleEmpNumbers who the caller may see; null = everybody
     */
    public function results(Form $form, ?array $accessibleEmpNumbers = null): array
    {
        $definition = $this->formService->toDefinition($form);
        $submissions = $this->countedSubmissions($form);
        $count = count($submissions);
        $hidden = !FormResultAggregator::mayShow($form->isAnonymous(), $count);

        return [
            'formId' => $form->getId(),
            'title' => $form->getTitle(),
            'kind' => $form->getKind(),
            'anonymous' => $form->isAnonymous(),
            'status' => $form->getStatus(),
            'passPercent' => $form->getPassPercent(),
            'summary' => $this->summary($form, $submissions, $accessibleEmpNumbers),
            'hidden' => $hidden,
            'perItem' => $hidden ? [] : FormResultAggregator::perItem(
                $form->getKind(),
                $definition['items'],
                $this->answerRows($submissions),
                $count
            ),
            'people' => $form->isAnonymous() ? [] : $this->restrict(
                array_map(fn (FormSubmission $s) => $this->personRow($form, $s), $submissions),
                $accessibleEmpNumbers
            ),
        ];
    }

    /**
     * One person's answers next to the answer key.
     *
     * @throws FormRuleException
     */
    public function detail(Form $form, string $submissionId): array
    {
        $submission = $this->findSubmission($form, $submissionId);
        $byItem = [];
        foreach ($submission->getAnswers() as $row) {
            $byItem[$row->getItem()->getId()][] = $row;
        }

        $answers = [];
        foreach ($this->formService->toDefinition($form)['items'] as $position => $item) {
            if (!in_array($item['type'], FormTypes::QUESTION_TYPES, true)) {
                continue;
            }
            $rows = $byItem[$item['id']] ?? [];
            $first = $rows[0] ?? null;
            $answers[] = [
                'itemId' => $item['id'],
                'position' => $position + 1,
                'type' => $item['type'],
                'prompt' => $item['prompt'],
                'points' => $item['points'],
                'options' => $item['options'],
                'optionIds' => array_values(array_filter(array_map(
                    static fn (FormAnswer $r) => $r->getOption()?->getId(),
                    $rows
                ))),
                'correctOptionIds' => array_values(array_column(
                    array_filter($item['options'], static fn ($o) => $o['isCorrect']),
                    'id'
                )),
                'text' => $first?->getTextValue(),
                'scale' => $first?->getScaleValue(),
                'yesNo' => $first?->getYesNoValue(),
                'correctYesNo' => $item['correctYesNo'],
                'awarded' => $this->awardedFor($item, $rows),
            ];
        }

        return $this->personRow($form, $submission) + ['answers' => $answers];
    }

    /**
     * HR grades the written answers; the grade is recomputed from every row.
     *
     * @param array<int|string, float> $points keyed by item id
     * @throws FormRuleException
     */
    public function review(Form $form, string $submissionId, array $points, ?int $reviewerEmpNumber): array
    {
        $submission = $this->findSubmission($form, $submissionId);
        $definition = $this->formService->toDefinition($form);
        $items = [];
        foreach ($definition['items'] as $position => $item) {
            $items[$item['id']] = $item + ['position' => $position + 1];
        }

        foreach ($points as $itemId => $given) {
            $item = $items[(int)$itemId] ?? null;
            if ($item === null || !in_array($item['type'], FormTypes::TEXT_TYPES, true)) {
                throw FormRuleException::form('So as respostas escritas sao corrigidas pelo RH.');
            }
            FormGrading::assertReviewPoints((float)$given, (float)$item['points'], $item['position']);
            foreach ($submission->getAnswers() as $row) {
                if ($row->getItem()->getId() === (int)$itemId) {
                    $row->setPointsAwarded((float)$given);
                }
            }
        }

        $awarded = [];
        $byItem = [];
        foreach ($submission->getAnswers() as $row) {
            $byItem[$row->getItem()->getId()][] = $row;
        }
        foreach ($definition['items'] as $item) {
            if (in_array($item['type'], FormTypes::SCORED_TYPES, true)) {
                $awarded[$item['id']] = $this->awardedFor($item, $byItem[$item['id']] ?? []);
            }
        }
        $totals = FormGrading::totals($awarded, (float)$submission->getMaxPoints());
        $submission->setScorePoints($totals['scorePoints']);
        $submission->setStatus($totals['status']);
        $submission->setReviewedByEmpNumber($reviewerEmpNumber);
        $submission->setReviewedAt(new DateTime());
        $this->getEntityManager()->flush();

        return (new FormSubmissionService())->resultOf($form, $submission);
    }

    /**
     * @throws FormRuleException
     */
    public function grantRetake(Form $form, Employee $employee, ?int $grantedBy): array
    {
        if ($form->isAnonymous()) {
            throw FormRuleException::form('Pesquisa anonima nao tem nova tentativa.');
        }
        $submissions = new FormSubmissionService();
        if ($submissions->attemptsUsed($form, $employee) === 0) {
            throw FormRuleException::form('Esta pessoa ainda nao respondeu.');
        }

        $retake = new FormRetake();
        $retake->setForm($form);
        $retake->setEmployee($employee);
        $retake->setGrantedByEmpNumber($grantedBy);
        $this->getEntityManager()->persist($retake);
        $this->getEntityManager()->flush();

        return [
            'employeeId' => $employee->getEmpNumber(),
            'attemptsAllowed' => FormSubmissionRules::allowedAttempts($submissions->retakesGranted($form, $employee)),
        ];
    }

    /**
     * One line per person (latest attempt), one column per question. For an
     * anonymous survey, no name, unit or date, and the lines shuffled.
     */
    public function csv(Form $form): string
    {
        $items = array_values(array_filter(
            $this->formService->toDefinition($form)['items'],
            static fn ($i) => in_array($i['type'], FormTypes::QUESTION_TYPES, true)
        ));
        $anonymous = $form->isAnonymous();
        $header = $anonymous ? [] : ['Nome', 'Unidade', 'Data', 'Tentativa', 'Nota %', 'Situacao'];
        foreach ($items as $item) {
            $header[] = $item['prompt'];
        }

        $lines = [];
        foreach ($this->countedSubmissions($form) as $submission) {
            $line = [];
            if (!$anonymous) {
                $person = $this->personRow($form, $submission);
                $line = [$person['name'], $person['unit'], $person['submittedAt'], $person['attempt'],
                    $person['percent'], $this->statusLabel($person)];
            }
            $byItem = [];
            foreach ($submission->getAnswers() as $row) {
                $byItem[$row->getItem()->getId()][] = $row;
            }
            foreach ($items as $item) {
                $line[] = $this->cellFor($item, $byItem[$item['id']] ?? []);
            }
            $lines[] = $line;
        }
        if ($anonymous) {
            shuffle($lines);
        }
        return FormCsv::build($header, $lines);
    }

    /**
     * @return FormSubmission[] the latest attempt of each person; every
     *     submission of an anonymous survey
     */
    private function countedSubmissions(Form $form): array
    {
        $all = $this->getEntityManager()->getRepository(FormSubmission::class)->findBy(['form' => $form]);
        if ($form->isAnonymous()) {
            return $all;
        }
        $latest = [];
        foreach ($all as $submission) {
            $key = $submission->getEmployee()?->getEmpNumber();
            if ($key === null) {
                continue;
            }
            if (!isset($latest[$key]) || $submission->getAttempt() > $latest[$key]->getAttempt()) {
                $latest[$key] = $submission;
            }
        }
        usort($latest, static fn ($a, $b) => strcmp(self::name($a->getEmployee()), self::name($b->getEmployee())));
        return array_values($latest);
    }

    private function summary(Form $form, array $submissions, ?array $accessible): array
    {
        $answered = [];
        foreach ($this->getEntityManager()->getRepository(FormCompletion::class)->findBy(['form' => $form]) as $c) {
            $answered[$c->getEmployee()->getEmpNumber()] = true;
        }
        $audience = $this->formService->audience($form);
        $pending = array_map(static fn (Employee $e) => [
            'employeeId' => $e->getEmpNumber(),
            'name' => self::name($e),
            'unit' => $e->getSubDivision()?->getName(),
        ], array_values(array_filter($audience, static fn (Employee $e) => !isset($answered[$e->getEmpNumber()]))));

        $summary = [
            'audienceCount' => count($audience),
            'respondedCount' => count($answered),
            'pendingPeople' => $this->restrict($pending, $accessible),
            'average' => null,
            'passedPercent' => null,
            'pendingReviewCount' => 0,
        ];
        if ($form->getKind() !== FormTypes::KIND_QUIZ) {
            return $summary;
        }

        $percents = [];
        $passed = 0;
        foreach ($submissions as $s) {
            if ($s->getStatus() === FormTypes::SUBMISSION_PENDING_REVIEW) {
                $summary['pendingReviewCount']++;
            } elseif ($s->getStatus() === FormTypes::SUBMISSION_GRADED) {
                $percents[] = FormGrading::percent((float)$s->getScorePoints(), (float)$s->getMaxPoints());
                $passed += FormGrading::passed((float)$s->getScorePoints(), (float)$s->getMaxPoints(), (int)$form->getPassPercent()) ? 1 : 0;
            }
        }
        if ($percents !== []) {
            $summary['average'] = round(array_sum($percents) / count($percents), 1);
            $summary['passedPercent'] = round($passed / count($percents) * 100, 1);
        }
        return $summary;
    }

    private function personRow(Form $form, FormSubmission $submission): array
    {
        $employee = $submission->getEmployee();
        return [
            'submissionId' => $submission->getId(),
            'employeeId' => $employee?->getEmpNumber(),
            'name' => $employee === null ? null : self::name($employee),
            'unit' => $employee?->getSubDivision()?->getName(),
            'submittedAt' => $submission->getSubmittedAt()?->format('d/m/Y H:i'),
            'attempt' => $submission->getAttempt(),
        ] + (new FormSubmissionService())->resultOf($form, $submission);
    }

    /**
     * @param FormSubmission[] $submissions
     */
    private function answerRows(array $submissions): array
    {
        $rows = [];
        foreach ($submissions as $submission) {
            foreach ($submission->getAnswers() as $answer) {
                $rows[] = [
                    'submissionId' => $submission->getId(),
                    'itemId' => $answer->getItem()->getId(),
                    'optionId' => $answer->getOption()?->getId(),
                    'text' => $answer->getTextValue(),
                    'scale' => $answer->getScaleValue(),
                    'yesNo' => $answer->getYesNoValue(),
                    'points' => $answer->getPointsAwarded(),
                ];
            }
        }
        return $rows;
    }

    /**
     * Points a question got: a text row still waiting is null; an unanswered
     * question is 0; a choice carries its points on the first row.
     *
     * @param FormAnswer[] $rows
     */
    private function awardedFor(array $item, array $rows): ?float
    {
        if ($rows === []) {
            return 0.0;
        }
        if (in_array($item['type'], FormTypes::TEXT_TYPES, true)) {
            return $rows[0]->getPointsAwarded();
        }
        return (float)array_sum(array_map(static fn (FormAnswer $r) => (float)$r->getPointsAwarded(), $rows));
    }

    private function cellFor(array $item, array $rows): ?string
    {
        if ($rows === []) {
            return null;
        }
        switch ($item['type']) {
            case FormTypes::TYPE_SINGLE:
            case FormTypes::TYPE_MULTIPLE:
                return implode(', ', array_filter(array_map(static fn (FormAnswer $r) => $r->getOption()?->getLabel(), $rows)));
            case FormTypes::TYPE_SCALE:
                return (string)$rows[0]->getScaleValue();
            case FormTypes::TYPE_YES_NO:
                return $rows[0]->getYesNoValue() ? 'Sim' : 'Nao';
            default:
                return $rows[0]->getTextValue();
        }
    }

    private function statusLabel(array $person): string
    {
        switch ($person['status']) {
            case FormTypes::SUBMISSION_GRADED:
                return $person['passed'] ? 'Aprovado' : 'Nao aprovado';
            case FormTypes::SUBMISSION_PENDING_REVIEW:
                return 'Aguardando correcao';
            default:
                return 'Respondido';
        }
    }

    /**
     * @throws FormRuleException
     */
    private function findSubmission(Form $form, string $submissionId): FormSubmission
    {
        if ($form->isAnonymous()) {
            throw FormRuleException::form('Pesquisa anonima nao mostra respostas individuais.');
        }
        $submission = $this->getEntityManager()->find(FormSubmission::class, $submissionId);
        if (!$submission instanceof FormSubmission || $submission->getForm()->getId() !== $form->getId()) {
            throw FormRuleException::form('Resposta nao encontrada.');
        }
        return $submission;
    }

    private function restrict(array $rows, ?array $accessible): array
    {
        if ($accessible === null) {
            return $rows;
        }
        $ids = array_map('intval', $accessible);
        return array_values(array_filter($rows, static fn ($r) => in_array((int)$r['employeeId'], $ids, true)));
    }

    private static function name(?Employee $employee): string
    {
        return $employee === null ? '' : trim($employee->getFirstName() . ' ' . $employee->getLastName());
    }
}
