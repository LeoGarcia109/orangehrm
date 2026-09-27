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
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OrangeHRM\Attendance\Exception\FormRuleException;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\Form;
use OrangeHRM\Entity\FormAnswer;
use OrangeHRM\Entity\FormCompletion;
use OrangeHRM\Entity\FormItem;
use OrangeHRM\Entity\FormRetake;
use OrangeHRM\Entity\FormSubmission;

/**
 * BR: the employee's side of the forms -- what is waiting, opening one, and
 * sending the answers.
 */
class FormSubmissionService
{
    use EntityManagerHelperTrait;

    public const SECTION_PENDING = 'pending';
    public const SECTION_ANSWERED = 'answered';

    private FormService $formService;

    public function __construct()
    {
        $this->formService = new FormService();
    }

    /**
     * Forms that reach this employee: still to answer, or already answered.
     * A closed or expired form nobody answered is simply gone.
     *
     * @return array[] each with section 'pending' or 'answered'
     */
    public function myForms(Employee $employee, DateTime $now): array
    {
        $forms = $this->getEntityManager()->getRepository(Form::class)->findBy(
            ['status' => [FormTypes::STATUS_PUBLISHED, FormTypes::STATUS_CLOSED], 'template' => false],
            ['publishedAt' => 'DESC']
        );

        $list = [];
        foreach ($forms as $form) {
            if (!$this->formService->reaches($form, $employee)) {
                continue;
            }
            $used = $this->attemptsUsed($form, $employee);
            $allowed = FormSubmissionRules::allowedAttempts($this->retakesGranted($form, $employee));
            $open = $form->getStatus() === FormTypes::STATUS_PUBLISHED
                && ($form->getDueAt() === null || $now <= $form->getDueAt());

            if ($open && $used < $allowed) {
                $section = self::SECTION_PENDING;
            } elseif ($used > 0) {
                $section = self::SECTION_ANSWERED;
            } else {
                continue;
            }

            $list[] = [
                'id' => $form->getId(),
                'title' => $form->getTitle(),
                'description' => $form->getDescription(),
                'kind' => $form->getKind(),
                'anonymous' => $form->isAnonymous(),
                'dueAt' => $form->getDueAt()?->format('Y-m-d'),
                'section' => $section,
                'attemptsUsed' => $used,
                'attemptsAllowed' => $allowed,
                'result' => $used > 0 ? $this->resultOf($form, $this->latestSubmission($form, $employee)) : null,
            ];
        }
        return $list;
    }

    /**
     * The form ready to be answered -- without its answer key.
     *
     * @throws FormRuleException
     */
    public function fillView(Form $form, Employee $employee): array
    {
        $this->assertReaches($form, $employee);
        if ($form->getStatus() !== FormTypes::STATUS_PUBLISHED) {
            throw FormRuleException::form('Este formulario nao esta aberto para respostas.');
        }

        return [
            'id' => $form->getId(),
            'title' => $form->getTitle(),
            'description' => $form->getDescription(),
            'dueAt' => $form->getDueAt()?->format('Y-m-d'),
        ] + FormFillSerializer::forFilling($this->formService->toDefinition($form));
    }

    /**
     * Checks, grades and records the answers in one flush -- all of it or
     * none. A double tap that slips past the attempt check still meets the
     * unique (form, employee, attempt) index and is turned away.
     *
     * @param array<int, array> $answers keyed by item id
     * @return array{status: string, percent: ?float, passed: ?bool}
     * @throws FormRuleException
     */
    public function submit(Form $form, Employee $employee, array $answers, DateTime $now): array
    {
        $this->assertReaches($form, $employee);
        FormSubmissionRules::assertAccepting($form->getStatus(), $form->getDueAt(), $now);
        $used = $this->attemptsUsed($form, $employee);
        FormSubmissionRules::assertAttemptLeft($used, $this->retakesGranted($form, $employee));

        $definition = $this->formService->toDefinition($form);
        FormSubmissionRules::assertAnswers($definition['items'], $answers);
        $grade = FormGrading::grade($form->getKind(), $definition['items'], $answers);

        $completion = new FormCompletion();
        $completion->setForm($form);
        $completion->setEmployee($employee);
        $completion->setAttempt($used + 1);
        $completion->setCompletedAt(clone $now);
        $this->getEntityManager()->persist($completion);

        $submission = new FormSubmission();
        $submission->setForm($form);
        $submission->setStatus($grade['status']);
        $submission->setScorePoints($grade['scorePoints']);
        $submission->setMaxPoints($grade['maxPoints']);
        // Anonymous: nothing that could be lined up with the completion row.
        if (!$form->isAnonymous()) {
            $submission->setEmployee($employee);
            $submission->setAttempt($used + 1);
            $submission->setSubmittedAt(clone $now);
        }

        foreach ($form->getItems() as $item) {
            foreach ($this->answerRows($item, $answers[$item->getId()] ?? null, $grade['awarded']) as $row) {
                $submission->addAnswer($row);
            }
        }
        $this->getEntityManager()->persist($submission);

        try {
            $this->getEntityManager()->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw FormRuleException::form('Voce ja respondeu este formulario.');
        }

        return $this->resultOf($form, $submission);
    }

    public function latestSubmission(Form $form, Employee $employee): ?FormSubmission
    {
        if ($form->isAnonymous()) {
            return null;
        }
        return $this->getEntityManager()->getRepository(FormSubmission::class)->findOneBy(
            ['form' => $form, 'employee' => $employee],
            ['attempt' => 'DESC']
        );
    }

    /**
     * What the employee is told: the grade once it is final, never before.
     *
     * @return array{status: string, percent: ?float, passed: ?bool}
     */
    public function resultOf(Form $form, ?FormSubmission $submission): array
    {
        if ($submission === null || $submission->getStatus() === FormTypes::SUBMISSION_RECORDED) {
            return ['status' => FormTypes::SUBMISSION_RECORDED, 'percent' => null, 'passed' => null];
        }
        if ($submission->getStatus() === FormTypes::SUBMISSION_PENDING_REVIEW) {
            return ['status' => FormTypes::SUBMISSION_PENDING_REVIEW, 'percent' => null, 'passed' => null];
        }
        $score = (float)$submission->getScorePoints();
        $max = (float)$submission->getMaxPoints();
        return [
            'status' => FormTypes::SUBMISSION_GRADED,
            'percent' => FormGrading::percent($score, $max),
            'passed' => FormGrading::passed($score, $max, (int)$form->getPassPercent()),
        ];
    }

    public function attemptsUsed(Form $form, Employee $employee): int
    {
        return $this->getEntityManager()->getRepository(FormCompletion::class)
            ->count(['form' => $form, 'employee' => $employee]);
    }

    public function retakesGranted(Form $form, Employee $employee): int
    {
        return $this->getEntityManager()->getRepository(FormRetake::class)
            ->count(['form' => $form, 'employee' => $employee]);
    }

    /**
     * @throws FormRuleException
     */
    private function assertReaches(Form $form, Employee $employee): void
    {
        if ($form->isTemplate() || !$this->formService->reaches($form, $employee)) {
            throw FormRuleException::form('Este formulario nao e para voce.');
        }
    }

    /**
     * One row per ticked option (points on the first), or one row for the
     * text, scale or yes/no. Blank answers leave no row.
     *
     * @return FormAnswer[]
     */
    private function answerRows(FormItem $item, ?array $answer, array $awarded): array
    {
        if ($answer === null || $item->getType() === FormTypes::TYPE_CONTENT) {
            return [];
        }
        $points = $awarded[$item->getId()] ?? null;
        $rows = [];

        if (in_array($item->getType(), FormTypes::CHOICE_TYPES, true)) {
            $options = [];
            foreach ($item->getOptions() as $option) {
                $options[$option->getId()] = $option;
            }
            foreach (array_map('intval', $answer['optionIds'] ?? []) as $index => $optionId) {
                $row = $this->row($item, $index === 0 ? $points : null);
                $row->setOption($options[$optionId] ?? null);
                $rows[] = $row;
            }
            return $rows;
        }

        if (in_array($item->getType(), FormTypes::TEXT_TYPES, true)) {
            $text = trim((string)($answer['text'] ?? ''));
            if ($text === '') {
                return [];
            }
            $row = $this->row($item, $points);
            $row->setTextValue($text);
            return [$row];
        }

        if ($item->getType() === FormTypes::TYPE_SCALE) {
            if (!isset($answer['scale'])) {
                return [];
            }
            $row = $this->row($item, null);
            $row->setScaleValue((int)$answer['scale']);
            return [$row];
        }

        if (!isset($answer['yesNo'])) {
            return [];
        }
        $row = $this->row($item, $points);
        $row->setYesNoValue((bool)$answer['yesNo']);
        return [$row];
    }

    private function row(FormItem $item, ?float $points): FormAnswer
    {
        $row = new FormAnswer();
        $row->setItem($item);
        $row->setPointsAwarded($points);
        return $row;
    }
}
