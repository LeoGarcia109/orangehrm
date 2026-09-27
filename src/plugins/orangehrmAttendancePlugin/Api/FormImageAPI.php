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

use finfo;
use OrangeHRM\Attendance\Service\Form\FormTypes;
use OrangeHRM\Core\Api\V2\CollectionEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\Form;
use OrangeHRM\Entity\FormImage;

/**
 * BR: an image for a form question.
 *
 * POST /api/v2/attendance/br/forms/images  {formId, image: {name, type, size, base64}}
 *
 * The declared type is checked by the attachment rule, and then the bytes
 * themselves: the image is served back inline to every employee the form
 * reaches, so a file that only claims to be a PNG must not get through.
 */
class FormImageAPI extends Endpoint implements CollectionEndpoint
{
    use EntityManagerHelperTrait;

    public const PARAMETER_FORM_ID = 'formId';
    public const PARAMETER_IMAGE = 'image';

    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $form = $this->getEntityManager()->find(
            Form::class,
            $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_FORM_ID)
        );
        $this->throwRecordNotFoundExceptionIfNotExist($form, Form::class);
        if ($form->getStatus() !== FormTypes::STATUS_DRAFT) {
            throw $this->getBadRequestException('Formulario publicado nao pode ser editado: use Duplicar.');
        }

        $attachment = $this->getRequestParams()->getAttachment(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_IMAGE);
        $content = (string)$attachment->getContent();
        $realType = (new finfo(FILEINFO_MIME_TYPE))->buffer($content);
        if (!in_array($realType, FormTypes::IMAGE_TYPES, true)) {
            throw $this->getBadRequestException('A imagem precisa ser JPG, PNG ou WebP.');
        }

        $image = new FormImage();
        $image->setForm($form);
        $image->setFilename($attachment->getFilename());
        $image->setFileType($realType);
        $image->setFileSize(strlen($content));
        $image->setContent($content);
        $this->getEntityManager()->persist($image);
        $this->getEntityManager()->flush();

        return new EndpointResourceResult(ArrayModel::class, [
            'id' => $image->getId(),
            'url' => '/attendance/brFormImage/' . $image->getId(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_FORM_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(
                self::PARAMETER_IMAGE,
                new Rule(Rules::BASE_64_ATTACHMENT, [
                    FormTypes::IMAGE_TYPES,
                    self::EXTENSIONS,
                    200,
                    1,
                    true,
                    FormTypes::IMAGE_MAX_BYTES,
                ])
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
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
}
