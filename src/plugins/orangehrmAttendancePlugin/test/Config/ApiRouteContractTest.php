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

namespace OrangeHRM\Tests\Attendance\Config;

use OrangeHRM\Core\Api\V2\CollectionEndpoint;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Tests\Util\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Every HTTP verb a route accepts has to reach a method the REST controller
 * will actually call.
 *
 * GenericRestController only runs update() for a ResourceEndpoint, and only
 * getAll()/create() for a CollectionEndpoint -- anything else is a 501. The
 * HR approve/reject of absences shipped that way: the class had update(), the
 * route allowed PUT, and calling update() directly in a probe passed, while
 * every real click answered "Not Implemented".
 *
 * @group Attendance
 * @group Routes
 */
class ApiRouteContractTest extends TestCase
{
    public function routes(): array
    {
        $routes = Yaml::parseFile(__DIR__ . '/../../config/routes.yaml');
        $cases = [];
        foreach ($routes as $name => $route) {
            $api = $route['defaults']['_api'] ?? null;
            if ($api === null) {
                continue;
            }
            foreach ($route['methods'] as $method) {
                // GET goes to getOne() when the request carries an id -- in the
                // path, or as a route default (the single-config endpoints).
                $getOne = str_contains($route['path'], '{id}') || array_key_exists('id', $route['defaults']);
                $cases["{$name} {$method}"] = [$route['path'], $method, $api, $getOne];
            }
        }
        return $cases;
    }

    /**
     * @dataProvider routes
     */
    public function testTheVerbReachesAnImplementedInterface(string $path, string $method, string $api, bool $getOne): void
    {
        $this->assertTrue(class_exists($api), "{$api} nao existe");

        switch ($method) {
            case 'GET':
                $needed = $getOne ? ResourceEndpoint::class : CollectionEndpoint::class;
                break;
            case 'POST':
                $needed = CollectionEndpoint::class;
                break;
            case 'PUT':
                $needed = ResourceEndpoint::class;
                break;
            default:
                $this->assertTrue(
                    is_subclass_of($api, CollectionEndpoint::class) || is_subclass_of($api, ResourceEndpoint::class)
                );
                return;
        }

        $this->assertTrue(
            is_subclass_of($api, $needed),
            "{$method} {$path}: {$api} precisa implementar " . $needed
        );
    }
}
