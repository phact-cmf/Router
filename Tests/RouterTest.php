<?php declare(strict_types=1);

namespace Tests;

use FastRoute\Dispatcher;
use Phact\Router\Loader;
use Phact\Router\Route;
use Phact\Router\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    /**
     * Assets that addRoute works correctly
     */
    public function testAddRoute(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/example', 'someHandler', 'index');
        $router->addRoute('GET', '/example/{id:[0-9]+}', 'someOtherHandler');
        $router->addRoute('GET', '/example/named/{name:[0-9]+}', 'namedHandler', 'named');

        $this->assertEquals([
            Dispatcher::FOUND,
            'someHandler',
            []
        ], $router->dispatch('GET', '/example'));

        $this->assertEquals([
            Dispatcher::FOUND,
            'someOtherHandler',
            [
                'id' => 42
            ]
        ], $router->dispatch('GET', '/example/42'));

        $this->assertEquals([
            Dispatcher::METHOD_NOT_ALLOWED,
            ['GET']
        ], $router->dispatch('POST', '/example/42'));

        $this->assertEquals([
            Dispatcher::NOT_FOUND,
        ], $router->dispatch('GET', '/example/another'));

        $this->assertEquals('/example', $router->url('index'));
        $this->assertEquals('/example/named/foo', $router->reverse('named', [
            'name' => 'foo'
        ]));
    }

    /**
     * Assets that addGroup works correctly
     */
    public function testAddGroup(): void
    {
        $router = new Router();

        $router->addGroup('/example', static function (Router $router) {
            $router->addRoute('GET', '', 'indexHandler', 'index');
            $router->addRoute('GET', '/all', 'allHandler', 'all');
            $router->addGroup('/users', static function (Router $router) {
                $router->addRoute('GET', '/{id:[0-9]+}', 'userHandler', 'user');
            });
        }, 'example:');

        $this->assertEquals([
            Dispatcher::FOUND,
            'indexHandler',
            []
        ], $router->dispatch('GET', '/example'));

        $this->assertEquals([
            Dispatcher::FOUND,
            'allHandler',
            []
        ], $router->dispatch('GET', '/example/all'));

        $this->assertEquals([
            Dispatcher::FOUND,
            'userHandler',
            [
                'id' => 42
            ]
        ], $router->dispatch('GET', '/example/users/42'));

        $this->assertEquals([
            Dispatcher::METHOD_NOT_ALLOWED,
            ['GET']
        ], $router->dispatch('POST', '/example'));

        $this->assertEquals([
            Dispatcher::NOT_FOUND,
        ], $router->dispatch('GET', '/example/another'));

        $this->assertEquals('/example', $router->reverse('example:index'));
        $this->assertEquals('/example/users/2', $router->url('example:user', [
            'id' => 2
        ]));
    }

    /**
     * Assets that map works correctly
     */
    public function testMap(): void
    {
        $router = new Router();
        $router->map('GET', '/example', 'someHandler', 'index', [
            'firstMiddleware',
            'secondMiddleware'
        ]);
        $router->map('GET', '/example/{id:[0-9]+}', 'someOtherHandler');
        $router->map('GET', '/example/named/{name:[0-9]+}', 'namedHandler', 'named', [
            'namedMiddleware'
        ]);
        $router->map('GET', '/example/excluded/{name:[0-9]+}', 'namedHandler', null, [
            'excludedMiddleware'
        ]);
        $router->map('GET', '/example/custom', new Route('customHandler', null, [
            'rewriteMiddleware'
        ]), null, [
            'excludedMiddleware'
        ]);

        $this->assertEquals([
            Dispatcher::FOUND,
            new Route(
                'someHandler',
                'index',
                [
                    'firstMiddleware',
                    'secondMiddleware'
                ]
            ),
            []
        ], $router->dispatch('GET', '/example'));

        $this->assertEquals([
            Dispatcher::FOUND,
            new Route(
                'someOtherHandler',
                null,
                []
            ),
            [
                'id' => 42
            ]
        ], $router->dispatch('GET', '/example/42'));

        $this->assertEquals([
            Dispatcher::FOUND,
            new Route(
                'customHandler',
                null,
                [
                    'rewriteMiddleware'
                ]
            ),
            []
        ], $router->dispatch('GET', '/example/custom'));

        $this->assertEquals([
            Dispatcher::METHOD_NOT_ALLOWED,
            ['GET']
        ], $router->dispatch('POST', '/example/42'));

        $this->assertEquals([
            Dispatcher::NOT_FOUND,
        ], $router->dispatch('GET', '/example/another'));

        $this->assertEquals('/example', $router->url('index'));
        $this->assertEquals('/example/named/foo', $router->reverse('named', [
            'name' => 'foo'
        ]));
    }

    /**
     * Assets that group works correctly
     */
    public function testGroup(): void
    {
        $router = new Router();

        $router->group('/example', static function (Router $router) {
            $router->map('GET', '', 'indexHandler', 'index', [
                'indexMiddleware'
            ]);
            $router->group('/users', static function (Router $router) {
                $router->map('GET', '/{id:[0-9]+}', 'userHandler', 'user', [
                    'userMiddleware'
                ]);
            }, null, [
                'usersMiddleware'
            ]);
            $router->map('GET', '/all', 'allHandler', 'all', [
                'allMiddleware'
            ]);
        }, 'example:', [
            'totalMiddleware'
        ]);

        $this->assertEquals([
            Dispatcher::FOUND,
            new Route(
                'indexHandler',
                'example:index',
                [
                    'totalMiddleware',
                    'indexMiddleware'
                ]
            ),
            []
        ], $router->dispatch('GET', '/example'));

        $this->assertEquals([
            Dispatcher::FOUND,
            new Route(
                'allHandler',
                'example:all',
                [
                    'totalMiddleware',
                    'allMiddleware'
                ]
            ),
            []
        ], $router->dispatch('GET', '/example/all'));

        $this->assertEquals([
            Dispatcher::FOUND,
            new Route(
                'userHandler',
                'example:user',
                [
                    'totalMiddleware',
                    'usersMiddleware',
                    'userMiddleware'
                ]
            ),
            [
                'id' => 42
            ]
        ], $router->dispatch('GET', '/example/users/42'));

        $this->assertEquals([
            Dispatcher::METHOD_NOT_ALLOWED,
            ['GET']
        ], $router->dispatch('POST', '/example/users/42'));

        $this->assertEquals([
            Dispatcher::NOT_FOUND,
        ], $router->dispatch('GET', '/example/another'));

        $this->assertEquals('/example', $router->reverse('example:index'));
        $this->assertEquals('/example/users/2', $router->url('example:user', [
            'id' => 2
        ]));
    }

    /**
     * Assert that common middleware applies to routes
     */
    public function testSetMiddlewares(): void
    {
        $router = new Router();
        $router->setMiddlewares([
            'commonMiddleware'
        ]);

        $router->map('GET', '/example', 'indexHandler', 'index', [
            'indexMiddleware'
        ]);

        /** @var Route $route */
        $route = $router->dispatch('GET', '/example')[1];
        $this->assertEquals([
            'commonMiddleware',
            'indexMiddleware'
        ], $route->getMiddlewares());
    }

    /**
     * Assert that loader will not loaded if not needed
     */
    public function testLoaderNotLoadedIfNotNeeded(): void
    {
        $router = new Router();

        $loader = $this->createMock(Loader::class);
        $loader
            ->expects($this->never())
            ->method('load');

        $router->setLoader($loader);

        $router->map('GET', '/example', 'handler');
    }

    /**
     * Assert that loader will loaded when needed
     */
    public function testLoaderWillLoaded(): void
    {
        $router = new Router();

        $loader = $this->createMock(Loader::class);
        $loader
            ->expects($this->once())
            ->method('load');

        $router->setLoader($loader);

        $router->dispatch('GET', '/example');
        $router->dispatch('GET', '/another_url');
    }
}