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

namespace OrangeHRM\Attendance\Api;

use DateTime;
use OrangeHRM\Attendance\Exception\FormRuleException;
use OrangeHRM\Attendance\Service\Form\FormService;
use OrangeHRM\Attendance\Service\Form\FormTypes;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\Form;

/**
 * BR: HR's side of the forms -- build, publish, close, duplicate.
 *
 * GET  /api/v2/attendance/br/forms        - forms, with templates in meta
 * POST /api/v2/attendance/br/forms        - {title, description, definition} new draft
 *                                           {sourceId} copy (how a template is used)
 * GET  /api/v2/attendance/br/forms/{id}   - one form, answer key included
 * PUT  /api/v2/attendance/br/forms/{id}   - {action: save|publish|close}
 *
 * Admin only (migration 014). Broken form rules come back as 400 with the
 * message -- "Bloco N: ..." when a block is at fault, for the builder to point at.
 */
class FormAPI extends Endpoint implements CrudEndpoint
{
    use EntityManagerHelperTrait;
    use AuthUserTrait;

    public const PARAMETER_TITLE = 'title';
    public const PARAMETER_DESCRIPTION = 'description';
    public const PARAMETER_DEFINITION = 'definition';
    public const PARAMETER_SOURCE_ID = 'sourceId';
    public const PARAMETER_ACTION = 'action';

    public const ACTION_SAVE = 'save';
    public const ACTION_PUBLISH = 'publish';
    public const ACTION_CLOSE = 'close';

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $service = new FormService();
        $items = [];
        foreach ($service->listForHr() as $form) {
            $live = $form->getStatus() !== FormTypes::STATUS_DRAFT;
            $items[] = $this->summary($form) + [
                'respondedCount' => $live ? $service->respondedCount($form) : null,
                'audienceCount' => $live ? count($service->audience($form)) : null,
            ];
        }

        $templates = array_map(fn (Form $form) => $this->summary($form), $service->listTemplates());

        return new EndpointCollectionResult(
            ArrayModel::class,
            $items,
            new ParameterBag([
                CommonParams::PARAMETER_TOTAL => count($items),
                'templates' => $templates,
            ])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection();
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $service = new FormService();
        $sourceId = $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_SOURCE_ID);

        try {
            if ($sourceId !== null) {
                $form = $service->duplicate($this->findForm($sourceId), $this->getAuthUser()->getEmpNumber());
            } else {
                $title = $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_TITLE);
                $definition = $this->getRequestParams()->getArrayOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_DEFINITION);
                if ($title === null || $definition === null) {
                    throw $this->getBadRequestException('Informe o titulo e o formulario.');
                }
                $form = $service->createDraft(
                    $this->normalizeDefinition($definition),
                    $title,
                    $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_DESCRIPTION),
                    $this->getAuthUser()->getEmpNumber()
                );
            }
        } catch (FormRuleException $e) {
            throw $this->getBadRequestException($e->getMessage());
        }

        return new EndpointResourceResult(ArrayModel::class, ['id' => $form->getId()]);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_SOURCE_ID, new Rule(Rules::POSITIVE))
            ),
            ...$this->contentRules()
        );
    }

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $form = $this->findForm($this->getAttributeId());
        $definition = (new FormService())->toDefinition($form);
        $definition['dueAt'] = $definition['dueAt'] instanceof DateTime ? $definition['dueAt']->format('Y-m-d') : null;

        return new EndpointResourceResult(ArrayModel::class, [
            'id' => $form->getId(),
            'title' => $form->getTitle(),
            'description' => $form->getDescription(),
            'status' => $form->getStatus(),
            'subunitName' => $form->getSubunit()?->getName(),
            'employeeName' => $form->getEmployee() === null ? null
                : trim($form->getEmployee()->getFirstName() . ' ' . $form->getEmployee()->getLastName()),
            'definition' => $definition,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE))
        );
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $service = new FormService();
        $form = $this->findForm($this->getAttributeId());
        $action = $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_ACTION);

        try {
            switch ($action) {
                case self::ACTION_PUBLISH:
                    $announcement = $service->publish($form, new DateTime());
                    return new EndpointResourceResult(ArrayModel::class, [
                        'id' => $form->getId(),
                        'status' => $form->getStatus(),
                        'announcementId' => $announcement->getId(),
                    ]);
                case self::ACTION_CLOSE:
                    $service->close($form);
                    break;
                default:
                    $title = $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_TITLE);
                    $definition = $this->getRequestParams()->getArrayOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_DEFINITION);
                    if ($title === null || $definition === null) {
                        throw $this->getBadRequestException('Informe o titulo e o formulario.');
                    }
                    $service->saveDraft(
                        $form,
                        $this->normalizeDefinition($definition),
                        $title,
                        $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_DESCRIPTION)
                    );
            }
        } catch (FormRuleException $e) {
            throw $this->getBadRequestException($e->getMessage());
        }

        return new EndpointResourceResult(ArrayModel::class, [
            'id' => $form->getId(),
            'status' => $form->getStatus(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(
                self::PARAMETER_ACTION,
                new Rule(Rules::IN, [[self::ACTION_SAVE, self::ACTION_PUBLISH, self::ACTION_CLOSE]])
            ),
            ...$this->contentRules()
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @return ParamRule[]
     */
    private function contentRules(): array
    {
        return [
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_TITLE,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [1, FormTypes::TITLE_MAX])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_DESCRIPTION,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [0, FormTypes::DESCRIPTION_MAX])
                )
            ),
            // Its insides are checked by the form rules, which can say which
            // block is wrong; a flat param rule could only say "definition".
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_DEFINITION, new Rule(Rules::ARRAY_TYPE))
            ),
        ];
    }

    /**
     * The deadline arrives as a date; it closes at the end of that day.
     */
    private function normalizeDefinition(array $definition): array
    {
        $due = $definition['dueAt'] ?? null;
        $definition['dueAt'] = null;
        if (is_string($due) && $due !== '') {
            $date = DateTime::createFromFormat('!Y-m-d', $due);
            if ($date === false) {
                throw $this->getBadRequestException('Prazo invalido.');
            }
            $definition['dueAt'] = $date->setTime(23, 59, 59);
        }
        return $definition;
    }

    private function findForm(int $id): Form
    {
        $form = $this->getEntityManager()->find(Form::class, $id);
        $this->throwRecordNotFoundExceptionIfNotExist($form, Form::class);
        return $form;
    }

    private function summary(Form $form): array
    {
        return [
            'id' => $form->getId(),
            'title' => $form->getTitle(),
            'description' => $form->getDescription(),
            'kind' => $form->getKind(),
            'anonymous' => $form->isAnonymous(),
            'status' => $form->getStatus(),
            'scope' => $form->getScope(),
            'subunitName' => $form->getSubunit()?->getName(),
            'employeeName' => $form->getEmployee() === null ? null
                : trim($form->getEmployee()->getFirstName() . ' ' . $form->getEmployee()->getLastName()),
            'dueAt' => $form->getDueAt()?->format('Y-m-d'),
            'publishedAt' => $form->getPublishedAt()?->format('Y-m-d H:i'),
            'itemCount' => $form->getItems()->count(),
        ];
    }
}
