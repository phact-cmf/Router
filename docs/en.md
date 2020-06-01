# Router

Follows PSR-7, PSR-15, and PSR-1, PSR-2, PSR-4, PSR-11, PSR-16.

Based on [FastRoute](https://github.com/nikic/FastRoute), inspired by [league/route](https://route.thephpleague.com/).

Main ideas:
- Follow PSR-7/PSR-15 conception or not
- Reversed routing (URL generation by route name)
- Almost native [FastRoute](https://github.com/nikic/FastRoute), you can use various processing strategies (CharCountBased, GroupCountBased...)
- Fast, multiple dispatch and reverse (with dynamically adding routes opportunity)
- Allows using custom Loader (allows load routes from different formats, files, etc.)
- Allows caching (PSR-16)
- Flexible (you can replace any component: Invoker, Dispatcher, Reverser, etc.)

## Оглавление

- [Использование без PSR-7/15 совместимости](#использование-без-psr-715-совместимости)
  - [Создание](#создание-router)
  - [Добавление роута, группы роутов](#добавление-роута-группы-роутов)
  - [Получение данных роута](#получение-данных-роута)
- [Использование с PSR-7/15](#использование-с-psr-715)
  - [Создание](#создание)
  - [Добавление роутов, групп роутов](#добавление-роутов-групп-роутов)
  - [Общие Middleware](#общие-middleware)
  - [Обработка запроса](#обработка-запроса)
- [URL reverse - генерация URL](#url-reverse---генерация-url)
  - [Простой массив в качестве параметров URL](#простой-массив-в-качестве-параметров-url)
  - [Генерация параметров запроса из неиспользованных параметров](#генерация-параметров-запроса-из-неиспользованных-параметров)
  - [Метод URL()](#метод-url)
  - [Изменение поведения reverse](#изменение-поведения-reverse)
- [Supported types of handlers](#supported-types-of-handlers)
  - [Строка - класс и метод, разделённые](#строка---класс-и-метод-разделенные-)
  - [Строка - класс, у которого определен метод __invoke()](#строка---класс-у-которого-определен-метод-__invoke)
  - [Массив - класс и имя метода](#массив---класс-и-имя-метода)
  - [Массив - объект и имя метода](#массив---объект-и-имя-метода)
  - [Callable-объект](#callable-объект)
  - [callback](#callback)
- [Supported types of Middleware](#supported-types-of-middleware)
  - [Имя класса](#имя-класса)
  - [Объект](#объект)
  - [Строка-идентификатор, по которой Container вернёт объект Middleware](#строка-идентификатор-по-которой-container-вернет-объект-middleware)
- [Invoker](#invoker)
- [Container](#container)
- [Loader](#loader)
- [Cache](#cache)
- [Изменение стратегий обработки FastRoute](#изменение-стратегий-обработки-fastroute)

## Usage without PSR-7/15 compatibility

Just like in [FastRoute](https://github.com/nikic/FastRoute), 
but with [reversed routing](#url-reverse---генерация-url).

### Create Router

As simple as possible
 
```php
$router = new Router();
```

See other documentation section if you need:
- cache usage
- Loader usage
- change FastRoute strategy
- change reverse logic

### Adding route, group of routes

```php
// Add route
$router->addRoute('GET', '/test', function () {
    // Route handler
}, 'test');

// Route with name
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

Router implements MiddlewareInterface, therefore easily integrates into any pipelines

### Создание

As simple as possible
 
```php
$router = new Router();
```

See other documentation section if you need:
- cache usage
- Loader usage
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

### Processing request

Since the router itself is Middleware, you must call the method ```process``` to process the router.

For simple usage without any Pipeline you can use default ```\Phact\Router\NotFoundHandler``` handler.
It will throw ```\Phact\Router\Exception\NotFoundException``` if route not found.

If route exists, but requested method not allowed, will be thrown 
```\Phact\Router\Exception\MethodNotAllowedException``` exception. 

```php
$response = $router->process($request, new NotFoundHandler());
```

## URL reverse - URL generation

If you added route with name, then you can generate URL by name and provided parameters.

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

Provided params can be a simple (not assoc.) array.

In this case, the parameter substitution will be performed in order.
 
For example, add route:

```php
// Добавление роута с двумя параметрами
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

By default, unused provided parameters will be converted to query parameters:

For example, add route:

```php
$router->addRoute('GET', '/test/{name:[a-z]+}', 'someHandler', 'test_with_name');
```

Then, generate route:

```php
$url = $router->reverse('test_with_name', [
    'name' => 'harry',
    'faculty' => 'gryffindor'
]);
```

We will get ```/test/harry?faculty=gryffindor```.


### Method url()

Instead of method ```$router->reverse(...)``` you can apply the method ```$router->url(...)``` - they are equivalent.

### Change reverse logic

If you need to define your behavior for the reverse method, then:

1. Implement your own ```\Phact\Router\ReverserFactory```, which will create your own ```\Phact\Router\Reverser```.
2. Implement your own ```\Phact\Router\Reverser```.
3. Provide your ```ReverserFactory``` object to Router constructor. Like this:

```php
$router = new Router(null, null, new MyAmazingReverserFactory());
```

## Supported types of handlers

> Only relevant if you use the PSR-7 – compatible method of work.
> If you use the router in the simplest way, then the handler can be any.

Any of the provided handlers must return an object ```\Psr\Http\Message\ResponseInterface```.

### String - class name and method name, "::" separated

Example:

```php
$router->addRoute('GET', '/test', '\App\Handlers\MyHandler::myMethod', 'test');
```

If Container are provided, then object will be requested from [Container](#container).
If are not provided, then object will be created.

### String - a class name that implements an __invoke() method

Example:

```php
$router->addRoute('GET', '/test', MyInvokableHandler::class, 'test');
```

If Container are provided, then object will be requested from [Container](#container).
If are not provided, then object will be created.

### Array - class name and method name

Example:

```php
$router->addRoute('GET', '/test', [MyHandler::class, 'myMethod'], 'test');
```

If Container are provided, then object will be requested from [Container](#container).
If are not provided, then object will be created.


### Array - object and method name

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

For change logic of handlers calling just [implement your own Invoker](#invoker)

## Supported types of Middleware

### Class name

Example:

```php
$router->map('POST', '/admin', new MyInvokableHandler(), 'admin', [
    ExampleMiddleware::class
]);
```

If Container are provided, then object will be requested from [Container](#container).
If are not provided, then object will be created.

### Object

Example:

```php
$router->map('POST', '/admin', new MyInvokableHandler(), 'admin', [
    new ExampleMiddleware()
]);
```

### String-identifier

Only relevant when [used with Container](#container).

Example:

```php
$router->map('POST', '/admin', new MyInvokableHandler(), 'admin', [
   'my_some_custom_middleware_from_container'
]);
```

## Invoker

Invoker, which implements the functionality of calling handlers and Middleware, is a replaceable part of the router.
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

> Attention! Router just provide Container to the default Invoker implementation.
> If you use your own Invoker implementation, keep that in mind.

## Loader

> By default, Router does not use any Loader.

You can implement your own class for loading routes from your own storage (file, database, etc.).

For this:
- Implement ```\Phact\Router\Loader``` for your own Loader 
- Set your Loader to Router like this:

```php
$router->setLoader($myCustomLoader);
```

Method ```load``` will be called at first ```dispatch``` or ```reverse``` call.

Method ```load``` will not be called if you [use Cache](#cache).

## Cache

> By default, Router does not use any cache.

You can use any ```\Psr\SimpleCache\CacheInterface``` (PSR-16) implementation like this:

```php
$router->setCache($myCache);
```

Routes set into the cache at the time of the first data receipt (```dispatch``` или ```reverse```), as well as after adding a route.
Getting routes from the cache occurs on the first call ```dispatch``` or ```reverse``` 
and avoids a resource-intensive call [Loader](#loader).

## Changes FastRoute strategies

You can replace any parts of Router:
- DataGenerator and Dispatcher (through DispatcherFactory)
- ReverserDataGenerator and Reverser (through ReverserFactory)
- RouteParser

By default, Router uses GroupCountBased strategy.
Any other strategy can be implemented similarly like default strategy, 
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