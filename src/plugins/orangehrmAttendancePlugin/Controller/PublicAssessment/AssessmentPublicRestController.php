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

namespace OrangeHRM\Attendance\Controller\PublicAssessment;

use OrangeHRM\Attendance\Service\ClientIp;
use DateTime;
use OrangeHRM\Attendance\Exception\AssessmentRuleException;
use OrangeHRM\Attendance\Service\Assessment\AssessmentService;
use OrangeHRM\Core\Api\V2\Exception\BadRequestException;
use OrangeHRM\Core\Api\V2\Exception\NotImplementedException;
use OrangeHRM\Core\Api\V2\Exception\RecordNotFoundException;
use OrangeHRM\Core\Api\V2\Request;
use OrangeHRM\Core\Api\V2\Response;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Controller\PublicControllerInterface;
use OrangeHRM\Core\Controller\Rest\V2\AbstractRestController;
use OrangeHRM\Entity\Assessment;

/**
 * BR: the candidate's questionnaire, answered through the link alone.
 *
 * GET  .../assessment/{token}/api                 - what the page needs
 * PUT  .../assessment/{token}/api {consent: true}  - consent, dated
 * PUT  .../assessment/{token}/api {answers: {...}} - one page of answers
 * POST .../assessment/{token}/api {complete: true} - finish and score
 *
 * The token is the whole credential -- there is no session to forge, so no
 * CSRF token -- and every way a link can fail answers the same 404.
 */
class AssessmentPublicRestController extends AbstractRestController implements PublicControllerInterface
{
    private ?AssessmentService $service = null;

    protected function handleGetRequest(Request $request): Response
    {
        return new Response($this->getService()->state($this->openAssessment($request)));
    }

    protected function handlePutRequest(Request $request): Response
    {
        $assessment = $this->openAssessment($request);
        $body = $request->getBody();
        try {
            if ($body->get('consent') === true) {
                $http = $request->getHttpRequest();
                $this->getService()->giveConsent(
                    $assessment,
                    ClientIp::fromServer(),
                    $http->headers->get('User-Agent'),
                    new DateTime()
                );
            }
            $answers = $body->get('answers');
            if ($answers !== null) {
                if (!is_array($answers)) {
                    throw new BadRequestException('Respostas invalidas.');
                }
                $this->getService()->saveAnswers($assessment, $answers, new DateTime());
            }
        } catch (AssessmentRuleException $e) {
            throw new BadRequestException($e->getMessage());
        }
        return new Response($this->getService()->state($assessment));
    }

    protected function handlePostRequest(Request $request): Response
    {
        $assessment = $this->openAssessment($request);
        if ($request->getBody()->get('complete') !== true) {
            throw new BadRequestException('Pedido invalido.');
        }
        try {
            $this->getService()->complete($assessment, new DateTime());
        } catch (AssessmentRuleException $e) {
            throw new BadRequestException($e->getMessage());
        }
        return new Response(['completed' => true]);
    }

    protected function handleDeleteRequest(Request $request): Response
    {
        throw new NotImplementedException();
    }

    /**
     * @throws RecordNotFoundException for every link that cannot be answered
     */
    private function openAssessment(Request $request): Assessment
    {
        $assessment = $this->getService()->findOpenByToken(
            (string)$request->getAttributes()->get('token', ''),
            new DateTime()
        );
        if (!$assessment instanceof Assessment) {
            throw new RecordNotFoundException('Este link nao esta mais ativo. Fale com o RH.');
        }
        return $assessment;
    }

    private function getService(): AssessmentService
    {
        return $this->service ??= new AssessmentService();
    }

    protected function initGetValidationRule(Request $request): ?ParamRuleCollection
    {
        return null;
    }

    protected function initPostValidationRule(Request $request): ?ParamRuleCollection
    {
        return null;
    }

    protected function initPutValidationRule(Request $request): ?ParamRuleCollection
    {
        return null;
    }

    protected function initDeleteValidationRule(Request $request): ?ParamRuleCollection
    {
        return null;
    }
}
