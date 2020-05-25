# Роутер

Следует стандартам: PSR-7, PSR-15, а так же PSR-1, PSR-2, PSR-4, PSR-11, PSR-16

Использует [FastRoute](https://github.com/nikic/FastRoute), вдохновлено [league/route](https://route.thephpleague.com/).

Основные идеи:
- возможность работать как в концепции PSR-7/PSR-15, так и вне их, используя концепцию FastRoute 
- Reversed routing (генерация url по имени роута)
- практически нативный FastRoute с возможностью использовать различные стратегии обработки (CharCountBased, GroupCountBased...)
- быстрый множественный dispatch и reverse (при остающейся возможности динамического добавления роутов)
- возможность подключить свой загрузчик роутов из любых форматов
- возможность подключить кеш (PSR-16)
- возможность гибко настраивать любой из компонентов роутера (заменять обработку handler, middleware)

## Оглавление

- [Использование без PSR-7/15 совместимости](#использование-без-PSR-7/15-совместимости)
  - [Создание](#создание-router)
  - [Добавление роута, группы роутов](#добавление-роута-группы-роутов)
  - [Получение данных роута](#получение-данных-роута)
- [Использование с PSR-7/15](#использование-с-PSR-7/15)
  - [Создание](#создание)
  - [Добавление роутов, групп роутов](#добавление-роутов-групп-роутов)
  - [Общие Middleware](#общие-middleware)
  - [Обработка запроса](#обработка-запроса)
- [Url reverse - генерация url](#url-reverse---генерация-url)
  - [Простой массив в качестве параметров url](#простой-массив-в-качестве-параметров-url)
  - [Генерация параметров запроса из неиспользованных параметров](#генерация-параметров-запроса-из-неиспользованных-параметров)
  - [Метод url()](#метод-url)
  - [Изменение поведения reverse](#изменение-поведения-reverse)
- [Поддерживаемые виды обработчиков](#поддерживаемые-виды-обработчиков)
  - [Строка - класс и метод, разделенные](#строка---класс-и-метод-разделенные-)
  - [Строка - класс, у которого определен метод __invoke()](#строка---класс-у-которого-определен-метод-__invoke())
  - [Массив - класс и имя метода](#массив---класс-и-имя-метода)
  - [Массив - объект и имя метода](#массив---объект-и-имя-метода)
  - [Callable-объект](#callable-объект)
  - [callback](#callback)
- [Поддерживаемые виды Middleware](#поддерживаемые-виды-middleware)
  - [Имя класса](#имя-класса)
  - [Объект](#объект)
  - [Строка-идентификатор, по которой Container вернет объект Middleware](#строка-идентификатор-по-которой-container-вернет-объект-middleware)
- [Invoker](#invoker)
- [Container](#container)
- [Loader](#loader)
- [Cache](#cache)
- [Изменение стратегий обработки FastRoute](#изменение-стратегий-обработки-fastroute)

## Использование без PSR-7/15 совместимости

Практически так же, как и [FastRoute](https://github.com/nikic/FastRoute), 
но с возможностью [reversed routing](#url-reverse---генерация-url).

### Создание Router

Максимально простое
 
```php
$router = new Router();
```

Смотрите остальные разделы документации, если вам необходимо:
- применять кеш
- использовать Loader
- поменять стратегию FastRoute, 
- поменять логику reverse

### Добавление роута, группы роутов

```php
// Добавление роута
$router->addRoute('GET', '/test', function () {
    // Это обработчик роута
}, 'test');

// Добавление роута с именем
$router->addRoute('GET', '/test/{name:[a-z]+}', function () {
    // Это обработчик роута с именем
}, 'test_with_name');

// Добавление группы роутов
$router->addGroup('GET', '/api', function (Router $router) {
    $router->addRoute('GET', '/users', function () {
        // Тут обработчик роута с пользователями api
    }, 'users');
}, 'api:');
$reversedRoute = $router->reverse('test_with_name', [
    'name' => 'somename'
]);

$reversedRoute = $router->reverse('test_with_name', [
    'name' => 'somename',
    'additional' => 'variable'
]);
// '/test/somename?additional=variable'

// Just like with FastRoute

```

### Получение данных роута

Тут всё точно так же как в FastRoute, см [Basic usage FastRoute](https://github.com/nikic/FastRoute#usage)

```php
$data = $router->dispatch('GET','/test');
```

## Использование с PSR-7/15

Router имплементирует MiddlewareInterface, поэтому легко встраивается в любые Pipelines

### Создание

Максимально простое
 
```php
$router = new Router();
```

Смотрите остальные разделы документации, если вам необходимо:
- применять кеш
- использовать Loader
- поменять стратегию FastRoute, 
- поменять логику reverse
- изменить стратегию Invoker

### Добавление роутов, групп роутов

Простое добавление роутов.
Виды возможных обработчиков ограничены. [Поддерживаемые виды обработчиков](#поддерживаемые-виды-обработчиков).

```php
$router->map('GET', '/test/{name:[a-z]+}', function () {
    // Обработчик роута с именем
}, 'test_with_name');
```

Конечно же, можно использовать и Middleware. [Поддерживаемые виды Middleware](#поддерживаемые-виды-Middleware).

```php
$router->map('POST', '/admin', function () {
    // Обработчик
}, 'admin', [
    AuthMiddleware::class,
    CSRFValidationMiddleware::class
]);
```

Группы так же поддерживаются так же, с Middleware

```php
$router->group('GET', '/api', function (Router $router) {
    $router->map('GET', '/users', [UsersHandlerController::class, 'all'], 'users', [
        UsersGuardMiddleware::class
    ]);
}, 'api:', [
    ApiAuthMiddleware::class
]);
```

### Общие Middleware

Можно установить список Middleware, которые будут применены ко всем роутам.

```php
$router->setMiddlewares([
    MyCustomMiddleware::class
]);
```

### Обработка запроса

Так как роутер сам по себе является Middleware, то для обработки роута необходимо вызвать метод ```process```.

Для удобства вызова без какого-либо Pipeline предусмотрен обработчик по-умолчанию ```\Phact\Router\NotFoundHandler```, 
который выбрасывает ```\Phact\Router\Exception\NotFoundException```, если роут не найден.

Если роут есть, но запрашиваемый метод для него недоступен, то будет выброшено исключение
```\Phact\Router\Exception\MethodNotAllowedException```

```php
$response = $router->process($request, new NotFoundHandler());
```

## Url reverse - генерация url

Если мы добавляем роут с именем, то затем можно построить url по этому имени с переданными параметрами.
Например, добавим роут:

```php
// Добавление роута с именем
$router->addRoute('GET', '/test/{name:[a-z]+}', 'someHandler', 'test_with_name');
```

Затем, можно сгенерировать url следующим образом:

```php
$router->reverse('test_with_name', [
    'name' => 'harry'
])
```

В ответе мы получим ```/test/harry```

### Простой массив в качестве параметров url

Передаваемые параметры могут быть расположены простым (не ассоциативным массивом). 
В этом случае подстановка параметров будет произведена по порядку. Например, добавим вот такой роут:

```php
// Добавление роута с двумя параметрами
$router->addRoute('GET', '/test/{name:[a-z]+}/{id:[0-9]+}', 'someHandler', 'test_double');
```

И для генерации url передадим не ассоциативный массив:

```php
$router->reverse('test_with_name', [
    'harry',
    12
])
```

В ответе мы получим ```/test/harry/12```

### Генерация параметров запроса из неиспользованных параметров

Интересной особенностью reverse по-умолчанию является то, что не использованные переданные 
параметры будут переданы в параметры запроса.

Для примера определим роут:

```php
// Добавление роута с именем
$router->addRoute('GET', '/test/{name:[a-z]+}', 'someHandler', 'test_with_name');
```

И сгенерируем url вот так:

```php
$router->reverse('test_with_name', [
    'name' => 'harry',
    'faculty' => 'gryffindor'
])
```

В ответе мы получим ```/test/harry?faculty=gryffindor```


### Метод url

Вместо метода ```$router->reverse(...)``` можно применять метод ```$router->url(...)``` - они являются равнозначными

### Изменение поведения reverse

Если вам необходимо определить своё поведение для метода reverse, то вам необходимо:

1. Реализовать ```\Phact\Router\ReverserFactory```, которая будет создавать ```\Phact\Router\Reverser```
2. Реализовать ваш ```\Phact\Router\Reverser```
3. Передать вашу ```ReverserFactory``` в конструктор Router. Например так:

```php
$router = new Router(null, null, new MyAmazingReverserFactory());
```

## Поддерживаемые виды обработчиков

> Актуально только если вы используете PSR-7 совместимый метод работы.
> Если вы используете роутер простейшим способом, то обработчик может быть любым.

Любой из представленных обработчиков должен возвращать объект ```\Psr\Http\Message\ResponseInterface```

### Строка - класс и метод, разделенные "::"

Пример:

```php
$router->addRoute('GET', '/test', '\App\Handlers\MyHandler::myMethod', 'test');
```

Если установлен Container, то объект будет запрошен у [Container](#container).
Если Container не установлен, то объект будет создан.

### Строка - класс, у которого определен метод __invoke()

Пример:

```php
$router->addRoute('GET', '/test', MyInvokableHandler::class, 'test');
```

Если установлен Container, то объект будет запрошен у [Container](#container).
Если Container не установлен, то объект будет создан.

### Массив - класс и имя метода

Пример:

```php
$router->addRoute('GET', '/test', [MyHandler::class, 'myMethod'], 'test');
```

Если установлен Container, то объект будет запрошен у [Container](#container).
Если Container не установлен, то объект будет создан.


### Массив - объект и имя метода

Пример:

```php
$router->addRoute('GET', '/test', [new MyHandler(), 'myMethod'], 'test');
```


### Callable-объект

Пример:

```php
$router->addRoute('GET', '/test', new MyInvokableHandler(), 'test');
```

### callback

Пример:

```php
$router->addRoute('GET', '/test', function(ServerRequestInterface $request, array $variables) : ResponseInterface {
    // Обработка
}, 'test');
```

### Расширение поддерживаемых обработчиков

Чтобы изменить логику вызова обработчиков просто [примените свою реализацию Invoker](#изменение-invoker)

## Поддерживаемые виды Middleware

### Имя класса

Пример:

```php
$router->map('POST', '/admin', new MyInvokableHandler(), 'admin', [
    ExampleMiddleware::class
]);
```

Если установлен Container, то объект будет запрошен у [Container](#container).
Если Container не установлен, то объект будет создан.

### Объект

Пример:

```php
$router->map('POST', '/admin', new MyInvokableHandler(), 'admin', [
    new ExampleMiddleware()
]);
```

### Строка-идентификатор, по которой Container вернет объект Middleware

Актуально только при [использовании с Container](#container).

Пример:

```php
$router->map('POST', '/admin', new MyInvokableHandler(), 'admin', [
   'my_some_custom_middleware_from_container'
]);
```

## Invoker

Invoker, который реализует функциональность вызова обработчиков и Middleware является заменяемой частью роутера. 
Вы можете просто заменить Invoker, реализовав интерфейс ```\Phact\Router\Invoker``` и установив его следующим образом:

```php
$router->setInvoker(new MyCustomInvoker());
```

## Container

Если вы хотите работать с вашим контейнером и он соответствует ```Psr\Container\ContainerInterface```,
то примените его к Router следующим образом:

```php
$router->setContainer($myContainer);
```

> Обратите внимание! Router передает Container в стандартную реализацию Invoker.
> Если вы сами реализуете Invoker, учитывайте это.


## Loader

> По-умолчанию Router не использует никакой Loader

Вы можете реализовать свой объект для загрузки роутов из вашего хранилища (файла, базы данных ...)

Для этого:
- опишите свой Loader имплементируя интерфейс ```\Phact\Router\Loader```
- установите ваш Loader в Router следующим образом:

```php
$router->setLoader($myCustomLoader);
```

Метод ```load``` будет вызван в момент первого ```dispatch``` или ```reverse```.
Метод ```load``` может быть не вызван в случае [использования Cache](#cache).

## Cache

> По-умолчанию Router не использует никакой Cache

Вы можете использовать свою реализацию ```\Psr\SimpleCache\CacheInterface``` (PSR-16) следующим образом:

```php
$router->setCache($myCache);
```

В кеш роуты попадают в момент первого получения данных, а так же после добавления роута.
Получение роутов из кеша происходит при первом ```dispatch``` или ```reverse``` 
и позволяет избежать ресурсоёмкого [вызова Loader](#loader).

## Изменение стратегий обработки FastRoute

Есть возможность заменить любые части Router:
- DataGenerator и Dispatcher (через DispatcherFactory)
- ReverserDataGenerator и Reverser (через ReverserFactory)
- RouteParser

По-умолчанию Router использует GroupCountBased стратегию обработки.
Любую другую стратегию можно реализовать по аналогии со стратегий по-умолчанию.

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