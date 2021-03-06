# Router

Follows PSR-7, PSR-15, and PSR-1, PSR-2, PSR-4, PSR-11, PSR-16.

Based on [FastRoute](https://github.com/nikic/FastRoute), inspired by [league/route](https://route.thephpleague.com/).

Main ideas:
- follow PSR-7/PSR-15 or using FastRoute approach
- reversed routing (URL generation by route name)
- almost native [FastRoute](https://github.com/nikic/FastRoute) with possibility of using various processing strategies (CharCountBased, GroupCountBased...)
- fast multiple dispatch and reverse (with ability to add routes dynamically)
- allows using custom Loader (allows to load routes from different formats, files, etc.)
- allows caching (PSR-16)
- flexible (you can replace any component: Invoker, Dispatcher, Reverser, etc.)

## Оглавление

- [Usage without PSR-7/15 compatibility](#usage-without-psr-715-compatibility)
  - [Create](#create-router)
  - [Adding route, group of routes](#adding-route-group-of-routes)
  - [Getting route data](#getting-route-data)
- [Usage with PSR-7/15 compatibility](#usage-with-psr-715-compatibility)
  - [Creating](#creating)
  - [Adding routes, groups of routes](#adding-routes-groups-of-routes)
  - [Common Middleware](#common-middleware)
  - [Request processing](#request-processing)
- [URL reverse - URL generation](#url-reverse---url-generation)
  - [A simple array as URL parameters](#a-simple-array-as-url-parameters)
  - [Generating query parameters from unused provided parameters](#generating-query-parameters-from-unused-provided-parameters)
  - [Method url()](#method-url)
  - [Change reverse behavior](#change-reverse-behavior)
- [Supported types of handlers](#supported-types-of-handlers)
  - [String containing a class name and a method name separated by "::"](#string-containing-a-class-name-and-a-method-name-separated-by-)
  - [String containing a class name that implements an __invoke() method](#string-containing-a-class-name-that-implements-an-__invoke-method)
  - [Array containing a class name and a method name](#array-containing-a-class-name-and-a-method-name)
  - [Array containing an object and a method name](#array-containing-an-object-and-a-method-name)
  - [Callable-object](#callable-object)
  - [callback](#callback)
  - [Changing supported handlers](#changing-supported-handlers)
- [Supported types of Middleware](#supported-types-of-middleware)
  - [Class name](#class-name)
  - [Object](#object)
  - [String identifier of the Middleware object Container's definition](#string-identifier-of-the-middleware-object-containers-definition)
- [Invoker](#invoker)
- [Container](#container)
- [Loader](#loader)
- [Cache](#cache)
- [Changes FastRoute strategies](#changes-fastroute-strategies)

## Usage without PSR-7/15 compatibility

Almost like [FastRoute](https://github.com/nikic/FastRoute), 
but with [reversed routing](#url-reverse---url-generation).

### Create Router

As simple as possible
 
```php
$router = new Router();
```

See other documentation sections if you need to:
- use cache
- use Loader
- change FastRoute strategy
- change reverse logic

### Adding route, group of routes

```php
// Add route
$router->addRoute('GET', '/test', function () {
    // Route handler
}, 'test');

// Add route with name
$router->addRoute('GET', '/test/{name:[a-z]+}', function () {
    // Handler for route with name
}, 'test_with_name');

// Group of routes
$router->addGroup('GET', '/api', function (Router $router) {
    $router->addRoute('GET', '/users', function () {
        // Handler api users
    }, 'users');
}, 'api:');
$reversedRoute = $router->reverse('test_with_name', [
    'name' => 'somename'
]);
```

### Getting route data

Same as FastRoute, see here: [Basic usage FastRoute](https://github.com/nikic/FastRoute#usage)

```php
$data = $router->dispatch('GET','/test');
```

## Usage with PSR-7/15 compatibility

Router implements MiddlewareInterface, therefore it integrates into any Pipelines easily

### Creating

As simple as possible
 
```php
$router = new Router();
```

See other documentation section if you need to:
- use cache
- use Loader
- change FastRoute strategy
- change reverse logic
- change Invoker strategy (processing handlers and middlewares)

### Adding routes, groups of routes

Simple adding routes.
The types of possible handlers are limited. [Supported types of handlers](#supported-types-of-handlers).

```php
$router->map('GET', '/test/{name:[a-z]+}', function () {
    // Handler
}, 'test_with_name');
```

Of course, you can use Middleware. [Supported types of Middleware](#supported-types-of-middleware).

```php
$router->map('POST', '/admin', function () {
    // Handler
}, 'admin', [
    AuthMiddleware::class,
    CSRFValidationMiddleware::class
]);
```

Groups are also supported with Middleware.

```php
$router->group('GET', '/api', function (Router $router) {
    $router->map('GET', '/users', [UsersHandlerController::class, 'all'], 'users', [
        UsersGuardMiddleware::class
    ]);
}, 'api:', [
    ApiAuthMiddleware::class
]);
```

### Common Middleware

You can set a list of Middleware to be applied to all routes.

```php
$router->setMiddlewares([
    MyCustomMiddleware::class
]);
```

### Request processing

Since the router itself is Middleware, you must call the method ```process``` to process the route.

For simple usage without any Pipeline you can use default ```\Phact\Router\NotFoundHandler``` handler.
It will throw ```\Phact\Router\Exception\NotFoundException``` if route is not found.

If route exists but requested method is not allowed,
the ```\Phact\Router\Exception\MethodNotAllowedException``` exception will be thrown. 

```php
$response = $router->process($request, new NotFoundHandler());
```

## URL reverse - URL generation

If you added route with a name, then you can generate URL by name and provided parameters.

For example, add route:

```php
$router->addRoute('GET', '/test/{name:[a-z]+}', 'someHandler', 'test_with_name');
```

Then you can generate URL like this:

```php
$url = $router->reverse('test_with_name', [
    'name' => 'harry'
]);
```

You will get ```/test/harry```.

### A simple array as URL parameters

Provided parameters can be a simple (not assoc.) array.

In this case, the parameter substitution will be performed in order.
 
For example, add route:

```php
$router->addRoute('GET', '/test/{name:[a-z]+}/{id:[0-9]+}', 'someHandler', 'test_double');
```

Provide a simple array for URL generation:

```php
$url = $router->reverse('test_with_name', [
    'harry',
    12
]);
```

We will get ```/test/harry/12```.

### Generating query parameters from unused provided parameters

By default, unused provided parameters will be converted to query parameters.

For example, add route:

```php
$router->addRoute('GET', '/test/{name:[a-z]+}', 'someHandler', 'test_with_name');
```

Then, generate URL:

```php
$url = $router->reverse('test_with_name', [
    'name' => 'harry',
    'faculty' => 'gryffindor'
]);
```

We will get ```/test/harry?faculty=gryffindor```.


### Method url()

Instead of method ```$router->reverse(...)``` you can apply the method ```$router->url(...)``` as they are equivalent.

### Change reverse behavior

If you need to define your behavior for the reverse method, then:

1. Implement your own ```\Phact\Router\ReverserFactory```, which will create your own ```\Phact\Router\Reverser```.
2. Implement your own ```\Phact\Router\Reverser```.
3. Provide your ```ReverserFactory``` object to Router constructor. Like this:

```php
$router = new Router(null, null, new MyAmazingReverserFactory());
```

## Supported types of handlers

> Only relevant if you use the PSR-7 compatible method of work.
> If you use the router in the simplest way, then you can use any type of handler.

Any of the handlers presented below must return an object ```\Psr\Http\Message\ResponseInterface```.

### String containing a class name and a method name separated by "::"

Example:

```php
$router->addRoute('GET', '/test', '\App\Handlers\MyHandler::myMethod', 'test');
```

If Container is provided, the object will be requested from [Container](#container).
If Container is not provided, the object will be created.

### String containing a class name that implements an __invoke() method

Example:

```php
$router->addRoute('GET', '/test', MyInvokableHandler::class, 'test');
```

If Container is provided, the object will be requested from [Container](#container).
If Container is not provided, the object will be created.

### Array containing a class name and a method name

Example:

```php
$router->addRoute('GET', '/test', [MyHandler::class, 'myMethod'], 'test');
```

If Container is provided, the object will be requested from [Container](#container).
If Container is not provided, the object will be created.

### Array containing an object and a method name

Example:

```php
$router->addRoute('GET', '/test', [new MyHandler(), 'myMethod'], 'test');
```

### Callable-object

Example:

```php
$router->addRoute('GET', '/test', new MyInvokableHandler(), 'test');
```

### callback

Example:

```php
$router->addRoute('GET', '/test', function(ServerRequestInterface $request, array $variables) : ResponseInterface {
    // Handler
}, 'test');
```

### Changing supported handlers

To change the logic of handlers call just [implement your own Invoker](#invoker)

## Supported types of Middleware

### Class name

Example:

```php
$router->map('POST', '/admin', new MyInvokableHandler(), 'admin', [
    ExampleMiddleware::class
]);
```

If Container is provided, the object will be requested from [Container](#container).
If Container is not provided, the object will be created.

### Object

Example:

```php
$router->map('POST', '/admin', new MyInvokableHandler(), 'admin', [
    new ExampleMiddleware()
]);
```

### String identifier of the Middleware object Container's defenition 

Only relevant when [used with Container](#container).

Example:

```php
$router->map('POST', '/admin', new MyInvokableHandler(), 'admin', [
   'my_some_custom_middleware_from_container'
]);
```

## Invoker

Invoker, which implements the handlers' and Middleware call functionality, is a replaceable part of the router.
You can replace Invoker. Just implement ```\Phact\Router\Invoker``` interface and set it to the Router like this:

```php
$router->setInvoker(new MyCustomInvoker());
```

## Container

If you want to use your own Container, and it will implement ```Psr\Container\ContainerInterface```,
just set it to the router like this:

```php
$router->setContainer($myContainer);
```

> Attention! Router just provides Container to the default Invoker implementation.
> If you use your own Invoker implementation, keep that in mind.

## Loader

> By default, Router does not use any Loader.

You can implement your own class for loading routes from your own storage (file, database, etc.).

To do that:
- implement ```\Phact\Router\Loader``` for your own Loader 
- set your Loader to Router like this:

```php
$router->setLoader($myCustomLoader);
```

Method ```load``` will be called at first ```dispatch``` or ```reverse``` call.

Method ```load``` will not be called if you [use Cache](#cache).

## Cache

> By default, Router does not use any cache.

You can use your ```\Psr\SimpleCache\CacheInterface``` (PSR-16) implementation like this:

```php
$router->setCache($myCache);
```

Routes are set into the cache at the time of the first data receipt (```dispatch``` or ```reverse```), as well as after adding a route.
Getting routes from the cache occurs on the first call of ```dispatch``` or ```reverse``` 
and avoids a resource-intensive call of [Loader](#loader).

## Changes FastRoute strategies

You can replace any parts of Router:
- DataGenerator and Dispatcher (through DispatcherFactory)
- ReverserDataGenerator and Reverser (through ReverserFactory)
- RouteParser

By default, Router uses GroupCountBased strategy.
Any other strategy can be implemented similarly to default strategy, 
just provide your own implementations of needed objects:

```php
$route = new Router(
    new Collector(
        new MyCustomRouteParser(),
        new MyCustomDataGenerator(),
        new MyCustomReverserDataGenerator()
    ),
    new MyCustomDispatcherFactory(),
    new MyCustomReverserFactory()
);
```