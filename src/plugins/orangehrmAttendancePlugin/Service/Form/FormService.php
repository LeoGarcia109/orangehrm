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
use OrangeHRM\Attendance\Service\AnnouncementAudience;
use OrangeHRM\Attendance\Service\AnnouncementService;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\Announcement;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\Form;
use OrangeHRM\Entity\FormCompletion;
use OrangeHRM\Entity\FormImage;
use OrangeHRM\Entity\FormItem;
use OrangeHRM\Entity\FormOption;
use OrangeHRM\Entity\Subunit;

/**
 * BR: a form's life on the HR side -- draft, publish, close, duplicate -- and
 * who it reaches.
 *
 * The rules themselves live in the pure classes next to this one; this class
 * turns entities into the plain "definition" they work on, and back.
 */
class FormService
{
    use EntityManagerHelperTrait;

    public const POINTS_MAX = 999.0;

    /**
     * The form as the plain array the rule classes work on.
     */
    public function toDefinition(Form $form): array
    {
        return [
            'kind' => $form->getKind(),
            'anonymous' => $form->isAnonymous(),
            'passPercent' => $form->getPassPercent(),
            'scope' => $form->getScope(),
            'subunitId' => $form->getSubunit()?->getId(),
            'employeeId' => $form->getEmployee()?->getEmpNumber(),
            'dueAt' => $form->getDueAt(),
            'isTemplate' => $form->isTemplate(),
            'items' => array_map(static fn (FormItem $item) => [
                'id' => $item->getId(),
                'type' => $item->getType(),
                'prompt' => $item->getPrompt(),
                'helpText' => $item->getHelpText(),
                'required' => $item->isRequired(),
                'points' => $item->getPoints(),
                'imageId' => $item->getImage()?->getId(),
                'youtubeId' => $item->getYoutubeId(),
                'correctYesNo' => $item->getCorrectYesNo(),
                'options' => array_map(static fn (FormOption $option) => [
                    'id' => $option->getId(),
                    'label' => $option->getLabel(),
                    'isCorrect' => $option->isCorrect(),
                ], array_values($item->getOptions()->toArray())),
            ], array_values($form->getItems()->toArray())),
        ];
    }

    /**
     * @throws FormRuleException
     */
    public function createDraft(array $definition, string $title, ?string $description, ?int $createdBy): Form
    {
        $form = new Form();
        $form->setCreatedByEmpNumber($createdBy);
        $this->apply($form, $definition, $title, $description);
        $this->getEntityManager()->persist($form);
        $this->getEntityManager()->flush();
        return $form;
    }

    /**
     * A draft is rewritten whole: the blocks are dropped and rebuilt in the
     * order HR left them. Nothing has been answered yet, so nothing points at
     * the old rows.
     *
     * @throws FormRuleException
     */
    public function saveDraft(Form $form, array $definition, string $title, ?string $description): void
    {
        if ($form->getStatus() !== FormTypes::STATUS_DRAFT) {
            throw FormRuleException::form('Formulario publicado nao pode ser editado: use Duplicar.');
        }
        $this->apply($form, $definition, $title, $description);
        $this->getEntityManager()->flush();
    }

    /**
     * @throws FormRuleException
     */
    public function publish(Form $form, DateTime $now): Announcement
    {
        if ($form->getStatus() !== FormTypes::STATUS_DRAFT) {
            throw FormRuleException::form('Este formulario ja foi publicado.');
        }
        FormPublication::assertPublishable($this->toDefinition($form), $now);

        $form->setStatus(FormTypes::STATUS_PUBLISHED);
        $form->setPublishedAt(clone $now);

        // The notice is how people find out: same audience, a "Responder"
        // button through form_id, and gone from the inbox with the deadline.
        $announcement = new Announcement();
        $prefix = $form->getKind() === FormTypes::KIND_QUIZ ? 'Nova prova: ' : 'Nova pesquisa: ';
        $announcement->setTitle(mb_substr($prefix . $form->getTitle(), 0, FormTypes::TITLE_MAX));
        $body = trim((string)$form->getDescription());
        if ($form->getDueAt() instanceof DateTime) {
            $body .= ($body === '' ? '' : "\n\n") . 'Prazo: ' . $form->getDueAt()->format('d/m/Y');
        }
        $announcement->setBody($body === '' ? $form->getTitle() : $body);
        $announcement->setScope($form->getScope());
        $announcement->setSubunit($form->getSubunit());
        $announcement->setEmployee($form->getEmployee());
        $announcement->setExpiresAt($form->getDueAt());
        $announcement->setCreatedByEmpNumber($form->getCreatedByEmpNumber());
        $announcement->setForm($form);

        $this->getEntityManager()->persist($announcement);
        $this->getEntityManager()->flush();
        return $announcement;
    }

    /**
     * @throws FormRuleException
     */
    public function close(Form $form): void
    {
        if ($form->getStatus() !== FormTypes::STATUS_PUBLISHED) {
            throw FormRuleException::form('So um formulario publicado pode ser encerrado.');
        }
        $form->setStatus(FormTypes::STATUS_CLOSED);
        $form->setClosedAt(new DateTime());
        $this->getEntityManager()->flush();
    }

    /**
     * A fresh draft with the same blocks and images, no audience and no
     * deadline -- how a template is used, and how a published form is changed.
     */
    public function duplicate(Form $source, ?int $createdBy): Form
    {
        $copy = new Form();
        $copy->setTitle($source->isTemplate()
            ? $source->getTitle()
            : mb_substr($source->getTitle(), 0, FormTypes::TITLE_MAX - 8) . ' (copia)');
        $copy->setDescription($source->getDescription());
        $copy->setKind($source->getKind());
        $copy->setAnonymous($source->isAnonymous());
        $copy->setPassPercent($source->getPassPercent());
        $copy->setCreatedByEmpNumber($createdBy);
        $this->getEntityManager()->persist($copy);

        $images = [];
        foreach ($source->getItems() as $item) {
            $image = $item->getImage();
            if ($image instanceof FormImage && !isset($images[$image->getId()])) {
                $newImage = new FormImage();
                $newImage->setForm($copy);
                $newImage->setFilename($image->getFilename());
                $newImage->setFileType($image->getFileType());
                $newImage->setFileSize($image->getFileSize());
                $newImage->setContent($image->getContent());
                $this->getEntityManager()->persist($newImage);
                $images[$image->getId()] = $newImage;
            }

            $newItem = new FormItem();
            $newItem->setPosition($item->getPosition());
            $newItem->setType($item->getType());
            $newItem->setPrompt($item->getPrompt());
            $newItem->setHelpText($item->getHelpText());
            $newItem->setRequired($item->isRequired());
            $newItem->setPoints($item->getPoints());
            $newItem->setImage($image instanceof FormImage ? $images[$image->getId()] : null);
            $newItem->setYoutubeId($item->getYoutubeId());
            $newItem->setCorrectYesNo($item->getCorrectYesNo());
            foreach ($item->getOptions() as $option) {
                $newOption = new FormOption();
                $newOption->setPosition($option->getPosition());
                $newOption->setLabel($option->getLabel());
                $newOption->setCorrect($option->isCorrect());
                $newItem->addOption($newOption);
            }
            $copy->addItem($newItem);
        }

        $this->getEntityManager()->flush();
        return $copy;
    }

    public function reaches(Form $form, Employee $employee): bool
    {
        return AnnouncementAudience::reaches(
            $form->getScope(),
            $form->getSubunit()?->getId(),
            $form->getEmployee()?->getEmpNumber(),
            (new AnnouncementService())->getChainIds($employee),
            $employee->getEmpNumber()
        );
    }

    /**
     * Active employees the form reaches today.
     *
     * Same rule as AnnouncementAudience, written as a query: a unit's chain
     * contains the target exactly when the target's nested-set bounds enclose
     * the unit's.
     *
     * @return Employee[]
     */
    public function audience(Form $form): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('e')
            ->from(Employee::class, 'e')
            ->where('e.employeeTerminationRecord IS NULL')
            ->andWhere('e.purgedAt IS NULL')
            ->orderBy('e.firstName', 'ASC')
            ->addOrderBy('e.lastName', 'ASC');

        switch ($form->getScope()) {
            case FormTypes::SCOPE_SUBUNIT:
                $target = $form->getSubunit();
                if (!$target instanceof Subunit) {
                    return [];
                }
                $qb->innerJoin('e.subDivision', 's')
                    ->andWhere('s.lft >= :lft')
                    ->andWhere('s.rgt <= :rgt')
                    ->setParameter('lft', $target->getLft())
                    ->setParameter('rgt', $target->getRgt());
                break;
            case FormTypes::SCOPE_EMPLOYEE:
                if (!$form->getEmployee() instanceof Employee) {
                    return [];
                }
                $qb->andWhere('e.empNumber = :empNumber')
                    ->setParameter('empNumber', $form->getEmployee()->getEmpNumber());
                break;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * How many different people answered (not attempts).
     */
    public function respondedCount(Form $form): int
    {
        return (int)$this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(DISTINCT IDENTITY(c.employee))')
            ->from(FormCompletion::class, 'c')
            ->where('c.form = :form')
            ->setParameter('form', $form)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Form[] newest first, templates left out
     */
    public function listForHr(): array
    {
        return $this->getEntityManager()->getRepository(Form::class)
            ->findBy(['template' => false], ['createdAt' => 'DESC']);
    }

    /**
     * @return Form[]
     */
    public function listTemplates(): array
    {
        return $this->getEntityManager()->getRepository(Form::class)
            ->findBy(['template' => true], ['title' => 'ASC']);
    }

    /**
     * Writes the header and rebuilds the blocks. Drafts may be incomplete --
     * publishing is where completeness is checked -- but never malformed.
     *
     * @throws FormRuleException
     */
    private function apply(Form $form, array $definition, string $title, ?string $description): void
    {
        $kind = $definition['kind'] ?? null;
        if (!in_array($kind, [FormTypes::KIND_QUIZ, FormTypes::KIND_SURVEY], true)) {
            throw FormRuleException::form('Escolha Prova ou Pesquisa.');
        }
        $quiz = $kind === FormTypes::KIND_QUIZ;

        $form->setTitle(mb_substr(trim($title), 0, FormTypes::TITLE_MAX));
        $form->setDescription($description === null || trim($description) === '' ? null : $description);
        $form->setKind($kind);
        $form->setAnonymous(!$quiz && !empty($definition['anonymous']));
        $form->setPassPercent($quiz && isset($definition['passPercent']) ? (int)$definition['passPercent'] : null);
        $form->setDueAt($definition['dueAt'] ?? null);
        $this->applyAudience($form, $definition);

        // Nothing is flushed until every block is built: a block that fails
        // validation must not leave the draft with its old blocks deleted.
        foreach ($form->getItems()->toArray() as $old) {
            $form->getItems()->removeElement($old);
        }

        foreach (array_values($definition['items'] ?? []) as $index => $data) {
            $form->addItem($this->buildItem($form, $data, $index + 1, $quiz));
        }
    }

    private function applyAudience(Form $form, array $definition): void
    {
        $scope = $definition['scope'] ?? FormTypes::SCOPE_NETWORK;
        if (!in_array($scope, [FormTypes::SCOPE_NETWORK, FormTypes::SCOPE_SUBUNIT, FormTypes::SCOPE_EMPLOYEE], true)) {
            throw FormRuleException::form('Publico invalido.');
        }
        $form->setScope($scope);
        $form->setSubunit(null);
        $form->setEmployee(null);

        if ($scope === FormTypes::SCOPE_SUBUNIT && !empty($definition['subunitId'])) {
            $subunit = $this->getEntityManager()->find(Subunit::class, (int)$definition['subunitId']);
            if (!$subunit instanceof Subunit) {
                throw FormRuleException::form('Empresa/posto nao encontrado.');
            }
            $form->setSubunit($subunit);
        }
        if ($scope === FormTypes::SCOPE_EMPLOYEE && !empty($definition['employeeId'])) {
            $employee = $this->getEntityManager()->find(Employee::class, (int)$definition['employeeId']);
            if (!$employee instanceof Employee) {
                throw FormRuleException::form('Funcionario nao encontrado.');
            }
            $form->setEmployee($employee);
        }
    }

    private function buildItem(Form $form, array $data, int $position, bool $quiz): FormItem
    {
        $type = $data['type'] ?? null;
        if (!in_array($type, FormTypes::ITEM_TYPES, true)) {
            throw FormRuleException::at($position, 'tipo de bloco desconhecido.');
        }
        $content = $type === FormTypes::TYPE_CONTENT;
        $scored = $quiz && in_array($type, FormTypes::SCORED_TYPES, true);

        $points = (float)($data['points'] ?? 0);
        if ($scored && ($points < 0 || $points > self::POINTS_MAX)) {
            throw FormRuleException::at($position, 'pontos de 0 a 999.');
        }

        $item = new FormItem();
        $item->setPosition($position);
        $item->setType($type);
        $item->setPrompt((string)($data['prompt'] ?? ''));
        $help = $data['helpText'] ?? null;
        $item->setHelpText($help === null || trim((string)$help) === '' ? null : (string)$help);
        $item->setRequired(!$content && !empty($data['required']));
        $item->setPoints($scored ? $points : 0.0);
        $item->setImage($this->findOwnImage($form, $data['imageId'] ?? null, $position));
        $item->setYoutubeId($this->checkYoutubeId($data['youtubeId'] ?? null, $position));
        $item->setCorrectYesNo(
            $quiz && $type === FormTypes::TYPE_YES_NO && isset($data['correctYesNo'])
                ? (bool)$data['correctYesNo']
                : null
        );

        if (in_array($type, FormTypes::CHOICE_TYPES, true)) {
            foreach (array_values($data['options'] ?? []) as $index => $optionData) {
                $label = (string)($optionData['label'] ?? '');
                if (mb_strlen($label) > FormTypes::OPTION_MAX) {
                    throw FormRuleException::at($position, 'opcao com mais de 255 caracteres.');
                }
                $option = new FormOption();
                $option->setPosition($index + 1);
                $option->setLabel($label);
                $option->setCorrect($quiz && !empty($optionData['isCorrect']));
                $item->addOption($option);
            }
        }
        return $item;
    }

    /**
     * Only an image uploaded to this same form: an id from another form would
     * show somebody else's picture -- or a photo nobody meant to publish.
     */
    private function findOwnImage(Form $form, $imageId, int $position): ?FormImage
    {
        if ($imageId === null || $imageId === '') {
            return null;
        }
        $image = $this->getEntityManager()->find(FormImage::class, (int)$imageId);
        if (!$image instanceof FormImage
            || !$this->getEntityManager()->contains($form)
            || $image->getForm()->getId() !== $form->getId()) {
            throw FormRuleException::at($position, 'imagem invalida.');
        }
        return $image;
    }

    private function checkYoutubeId($youtubeId, int $position): ?string
    {
        if ($youtubeId === null || $youtubeId === '') {
            return null;
        }
        if (!is_string($youtubeId) || preg_match('/^[A-Za-z0-9_-]{11}$/', $youtubeId) !== 1) {
            throw FormRuleException::at($position, 'video do YouTube invalido.');
        }
        return $youtubeId;
    }
}
