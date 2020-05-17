<?php declare(strict_types=1);

namespace Tests;

use Phact\Router\Collector;
use Phact\Router\ReverserDataGenerator\Std as ReverserDataGenerator;
use PHPUnit\Framework\TestCase;

class CollectorTest_ extends TestCase
{
    public function testMap(): void
    {
        $collector = new Collector(
            new \FastRoute\RouteParser\Std(),
            new \FastRoute\DataGenerator\GroupCountBased(),
            new ReverserDataGenerator()
        );
        $handler = 'someHandler';
        $collector->map('GET', '/example', $handler, 'example_name');
        $collector->map('POST', '/example_not_named', $handler);
        $collector->map('GET', '/example/{id:[0-9]+}', $handler, 'example_item');

        $this->assertEquals([
            [
                'GET' => [
                    '/example' => 'someHandler'
                ],
                'POST' => [
                    '/example_not_named' => 'someHandler'
                ]
            ],
            [
                'GET' => [
                    [
                        'regex' => '~^(?|/example/([0-9]+))$~',
                        'routeMap' => [
                            2 => [
                                'someHandler',
                                [
                                    'id' => 'id'
                                ]
                            ]
                        ]
                    ],
                ]
            ]
        ], $collector->getData());

        $this->assertEquals([
            'example_name' => [
                '/example'
            ],
            'example_item' => [
                '/example/',
                ['id', '[0-9]+']
            ]
        ], $collector->getReverserData());
    }

    public function testGroup(): void
    {
        $collector = new Collector(
            new \FastRoute\RouteParser\Std(),
            new \FastRoute\DataGenerator\GroupCountBased(),
            new ReverserDataGenerator()
        );

        $handler = 'someHandler';
        $collector->group('/example', static function (Collector $collector) use ($handler) {
            $collector->map('GET', '/all', $handler, 'all');
            $collector->map('POST', '/latest', $handler);
            $collector->group('/user', static function (Collector $collector) use ($handler) {
                $collector->map('GET', '/{id:[0-9]+}', $handler, 'user');
                $collector->map('GET', '/relations/{user_id:[0-9]+}', $handler);
            });
        }, 'example:');


        $this->assertEquals([
            [
                'GET' => [
                    '/example/all' => 'someHandler',
                ],
                'POST' => [
                    '/example/latest' => 'someHandler',
                ]
            ],
            [
                'GET' => [
                    [
                        'regex' => '~^(?|/example/user/([0-9]+)|/example/user/relations/([0-9]+)())$~',
                        'routeMap' => [
                            2 => [
                                'someHandler',
                                [
                                    'id' => 'id'
                                ]
                            ],
                            3 => [
                                'someHandler',
                                [
                                    'user_id' => 'user_id'
                                ]
                            ],
                        ]
                    ],
                ]
            ]
        ], $collector->getData());

        $this->assertEquals([
            'example:all' => [
                '/example/all'
            ],
            'example:user' => [
                '/example/user/',
                ['id', '[0-9]+']
            ]
        ], $collector->getReverserData());
    }
}
