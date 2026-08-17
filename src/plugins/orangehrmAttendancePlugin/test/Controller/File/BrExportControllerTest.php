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

namespace OrangeHRM\Tests\Attendance\Controller\File;

use DateTime;
use OrangeHRM\Attendance\Controller\File\AbstractBrExportController;
use OrangeHRM\Authentication\Auth\User;
use OrangeHRM\Core\Service\TextHelperService;
use OrangeHRM\Core\Authorization\Manager\AbstractUserRoleManager;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;
use OrangeHRM\Framework\ServiceContainer;
use OrangeHRM\Framework\Services;
use OrangeHRM\Tests\Util\TestCase;

/**
 * @group Attendance
 * @group Controller
 */
class BrExportControllerTest extends TestCase
{
    private const LOGGED_IN_EMP_NUMBER = 1;
    private const ACCESSIBLE_EMP_NUMBER = 7;
    private const FOREIGN_EMP_NUMBER = 99;

    private array $generatedWith = [];

    protected function setUp(): void
    {
        parent::setUp();

        $authUser = $this->createMock(User::class);
        $authUser->method('getEmpNumber')->willReturn(self::LOGGED_IN_EMP_NUMBER);
        ServiceContainer::getContainer()->set(Services::AUTH_USER, $authUser);

        $userRoleManager = $this->createMock(AbstractUserRoleManager::class);
        $userRoleManager->method('getAccessibleEntityIds')
            ->with(Employee::class)
            ->willReturn([self::ACCESSIBLE_EMP_NUMBER]);
        ServiceContainer::getContainer()->set(Services::USER_ROLE_MANAGER, $userRoleManager);

        ServiceContainer::getContainer()->set(Services::TEXT_HELPER_SERVICE, new TextHelperService());
    }

    private function getController(): AbstractBrExportController
    {
        $test = $this;
        return new class ($test) extends AbstractBrExportController {
            private BrExportControllerTest $test;

            public function __construct(BrExportControllerTest $test)
            {
                $this->test = $test;
            }

            protected function generate(DateTime $fromDate, DateTime $toDate, ?int $empNumber): array
            {
                $this->test->recordGenerate($fromDate, $toDate, $empNumber);
                return [
                    'filename' => 'AFD_20260801_20260817.txt',
                    'content' => "0000000001\n0000000002\n",
                    'content_type' => 'text/plain; charset=ASCII',
                ];
            }
        };
    }

    public function recordGenerate(DateTime $fromDate, DateTime $toDate, ?int $empNumber): void
    {
        $this->generatedWith = [$fromDate->format('Y-m-d'), $toDate->format('Y-m-d'), $empNumber];
    }

    private function request(array $query): Request
    {
        return new Request($query);
    }

    public function testServesTheExportAsAnAttachment(): void
    {
        $response = $this->getController()->handle($this->request([
            'fromDate' => '2026-08-01',
            'toDate' => '2026-08-17',
            'empNumber' => (string)self::LOGGED_IN_EMP_NUMBER,
        ]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString(
            'AFD_20260801_20260817.txt',
            rawurldecode($response->headers->get('Content-Disposition'))
        );
        $this->assertSame('text/plain; charset=ASCII', $response->headers->get('Content-Type'));
        $this->assertSame('22', $response->headers->get('Content-Length'));
        $this->assertSame("0000000001\n0000000002\n", $response->getContent());
    }

    public function testOmittedEmpNumberExportsEveryEmployee(): void
    {
        $response = $this->getController()->handle($this->request([
            'fromDate' => '2026-08-01',
            'toDate' => '2026-08-17',
        ]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(['2026-08-01', '2026-08-17', null], $this->generatedWith);
    }

    public function testAllowsAnAccessibleEmployee(): void
    {
        $response = $this->getController()->handle($this->request([
            'fromDate' => '2026-08-01',
            'toDate' => '2026-08-17',
            'empNumber' => (string)self::ACCESSIBLE_EMP_NUMBER,
        ]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(self::ACCESSIBLE_EMP_NUMBER, $this->generatedWith[2]);
    }

    /**
     * The REST endpoint guarded this with IN_ACCESSIBLE_EMP_NUMBERS; the file
     * controller must not be a way around it.
     */
    public function testRejectsAnEmployeeTheUserCannotAccess(): void
    {
        $response = $this->getController()->handle($this->request([
            'fromDate' => '2026-08-01',
            'toDate' => '2026-08-17',
            'empNumber' => (string)self::FOREIGN_EMP_NUMBER,
        ]));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame([], $this->generatedWith);
    }

    /**
     * @dataProvider invalidDateProvider
     */
    public function testRejectsInvalidDateRanges(array $query): void
    {
        $response = $this->getController()->handle($this->request($query));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame([], $this->generatedWith);
    }

    public function invalidDateProvider(): array
    {
        return [
            'sem datas' => [[]],
            'sem toDate' => [['fromDate' => '2026-08-01']],
            'formato invalido' => [['fromDate' => '01/08/2026', 'toDate' => '17/08/2026']],
            'data inexistente' => [['fromDate' => '2026-02-30', 'toDate' => '2026-03-01']],
            'intervalo invertido' => [['fromDate' => '2026-08-17', 'toDate' => '2026-08-01']],
        ];
    }
}
