# Роутер

Следует стандартам PSR-7, PSR-15, а также PSR-1, PSR-2, PSR-4, PSR-11, PSR-16.

Использует [FastRoute](https://github.com/nikic/FastRoute), вдохновлено [league/route](https://route.thephpleague.com/).

Основные идеи:
- возможность работать, следуя стандартам PSR-7/PSR-15, либо используя подход FastRoute 
- reversed routing (генерация URL по имени роута)
- практически нативный FastRoute с возможностью использования различных стратегий обработки (CharCountBased, GroupCountBased...)
- быстрый множественный dispatch и reverse (при сохранении возможности динамического добавления роутов)
- возможность подключить свой загрузчик роутов из любых форматов
- возможность подключить кэш (PSR-16)
- возможность гибко настраивать любой из компонентов роутера (заменять обработку handler, middleware)

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
  - [Метод url()](#метод-url)
  - [Изменение поведения reverse](#изменение-поведения-reverse)
- [Поддерживаемые виды обработчиков](#поддерживаемые-виды-обработчиков)
  - [Строка, содержащая имя класса и имя метода, разделенные "::"](#строка-содержащая-имя-класса-и-имя-метода-разделенные-)
  - [Строка, содержащая имя класса, который реализует метод __invoke()](#строка-содержащая-имя-класса-который-реализует-метод-__invoke)
  - [Массив, содержащий имя класса и имя метода](#массив-содержащий-имя-класса-и-имя-метода)
  - [Массив, содержащий объект и имя метода](#массив-содержащий-объект-и-имя-метода)
  - [Callable-объект](#callable-объект)
  - [callback](#callback)
  - [Расширение поддерживаемых обработчиков](#расширение-поддерживаемых-обработчиков)
- [Поддерживаемые виды Middleware](#поддерживаемые-виды-middleware)
  - [Имя класса](#имя-класса)
  - [Объект](#объект)
  - [Строка-идентификатор, по которой Container вернёт объект Middleware](#строка-идентификатор-по-которой-container-вернёт-объект-middleware)
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
- применять кэш
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
```

### Получение данных роута

Аналогично FastRoute, смотрите здесь: [Basic usage FastRoute](https://github.com/nikic/FastRoute#usage)

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
- применять кэш
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

Конечно же, можно использовать и Middleware. [Поддерживаемые виды Middleware](#поддерживаемые-виды-middleware).

```php
$router->map('POST', '/admin', function () {
    // Обработчик
}, 'admin', [
    AuthMiddleware::class,
    CSRFValidationMiddleware::class
]);
```

Также поддерживаются группы, для которых можно указать Middleware.

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

Можно указать список Middleware, которые будут применены ко всем роутам.

```php
$router->setMiddlewares([
    MyCustomMiddleware::class
]);
```

### Обработка запроса

Так как роутер сам по себе является Middleware, то для обработки роута необходимо вызвать метод ```process```.

Для удобства вызова без какого-либо Pipeline предусмотрен обработчик по умолчанию ```\Phact\Router\NotFoundHandler```, 
который выбрасывает ```\Phact\Router\Exception\NotFoundException```, если роут не найден.

Если роут есть, но запрашиваемый метод для него недоступен, то будет выброшено исключение
```\Phact\Router\Exception\MethodNotAllowedException```. 

```php
$response = $router->process($request, new NotFoundHandler());
```

## URL reverse - генерация URL

Если мы добавляем роут с именем, то после можно построить URL по этому имени с переданными параметрами.
Например, добавим роут:

```php
// Добавление роута с именем
$router->addRoute('GET', '/test/{name:[a-z]+}', 'someHandler', 'test_with_name');
```

Затем можно сгенерировать URL следующим образом:

```php
$url = $router->reverse('test_with_name', [
    'name' => 'harry'
]);
```

В ответе мы получим ```/test/harry```

### Простой массив в качестве параметров URL

Передаваемые параметры могут быть простым (не ассоциативным) массивом. 
В этом случае подстановка параметров будет произведена по порядку. Например, добавим вот такой роут:

```php
$router->addRoute('GET', '/test/{name:[a-z]+}/{id:[0-9]+}', 'someHandler', 'test_double');
```

И для генерации URL передадим не ассоциативный массив:

```php
$url = $router->reverse('test_with_name', [
    'harry',
    12
]);
```

В ответе мы получим ```/test/harry/12```.

### Генерация параметров запроса из неиспользованных параметров

По умолчанию неиспользованные параметры будут переданы в параметры запроса.

Для примера определим роут:

```php
$router->addRoute('GET', '/test/{name:[a-z]+}', 'someHandler', 'test_with_name');
```

И сгенерируем URL вот так:

```php
$router->reverse('test_with_name', [
    'name' => 'harry',
    'faculty' => 'gryffindor'
]);
```

В ответе мы получим ```/test/harry?faculty=gryffindor```


### Метод url()

Вместо метода ```$router->reverse(...)``` можно применять метод ```$router->url(...)```, так как они являются равнозначными. 

### Изменение поведения reverse

Если вам необходимо определить своё поведение для метода reverse, то вам необходимо:

1. Реализовать ```\Phact\Router\ReverserFactory```, которая будет создавать ```\Phact\Router\Reverser```.
2. Реализовать ```\Phact\Router\Reverser```.
3. Передать вашу ```ReverserFactory``` в конструктор Router. Например, так:

```php
$router = new Router(null, null, new MyAmazingReverserFactory());
```

## Поддерживаемые виды обработчиков

> Актуально, только если вы используете PSR-7–совместимый метод работы.
> Если вы используете роутер простейшим способом, то обработчик может быть любым.

Любой из представленных ниже обработчиков должен возвращать объект ```\Psr\Http\Message\ResponseInterface```

### Строка, содержащая имя класса и имя метода, разделенные "::"

Пример:

```php
$router->addRoute('GET', '/test', '\App\Handlers\MyHandler::myMethod', 'test');
```

Если установлен Container, то объект будет запрошен у [Container](#container).
Если Container не установлен, то объект будет создан.

### Строка, содержащая имя класса, который реализует метод __invoke()

Пример:

```php
$router->addRoute('GET', '/test', MyInvokableHandler::class, 'test');
```

Если установлен Container, то объект будет запрошен у [Container](#container).
Если Container не установлен, то объект будет создан.

### Массив, содержащий имя класса и имя метода

Пример:

```php
$router->addRoute('GET', '/test', [MyHandler::class, 'myMethod'], 'test');
```

Если установлен Container, то объект будет запрошен у [Container](#container).
Если Container не установлен, то объект будет создан.

### Массив, содержащий объект и имя метода

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

Чтобы изменить логику вызова обработчиков, просто [примените свою реализацию Invoker](#изменение-invoker)

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

### Строка-идентификатор, по которой Container вернёт объект Middleware

Актуально только при [использовании с Container](#container).

Пример:

```php
$router->map('POST', '/admin', new MyInvokableHandler(), 'admin', [
   'my_some_custom_middleware_from_container'
]);
```

## Invoker

Invoker, который реализует функциональность вызова обработчиков и Middleware, является заменяемой частью роутера. 
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

> По умолчанию Router не использует никакой Loader.

Вы можете реализовать свой класс для загрузки роутов из вашего хранилища (файла, базы данных ...). 

Для этого:
- опишите свой Loader, имплементируя интерфейс ```\Phact\Router\Loader```
- установите ваш Loader в Router следующим образом:

```php
$router->setLoader($myCustomLoader);
```

Метод ```load``` будет вызван в момент первого ```dispatch``` или ```reverse```.

Метод ```load``` может быть не вызван в случае [использования Cache](#cache).

## Cache

> По умолчанию Router не использует никакой Cache

Вы можете использовать свою реализацию ```\Psr\SimpleCache\CacheInterface``` (PSR-16) следующим образом:

```php
$router->setCache($myCache);
```

В кэш роуты попадают в момент первого получения данных, а также после добавления роута.
Получение роутов из кэша происходит при первом ```dispatch``` или ```reverse``` 
и позволяет избежать ресурсоёмкого [вызова Loader](#loader).

## Изменение стратегий обработки FastRoute

Есть возможность заменить любые части Router:
- DataGenerator и Dispatcher (через DispatcherFactory)
- ReverserDataGenerator и Reverser (через ReverserFactory)
- RouteParser

По умолчанию Router использует стратегию обработки GroupCountBased.
Любую другую стратегию можно реализовать по аналогии со стратегий по умолчанию.

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