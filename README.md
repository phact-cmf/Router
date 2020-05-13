# Роутер

Использует FastRoute, много вещей подсмотрено у league/route.

Основные идеи:
- Reversed routing (генерация url по имени роута)
- практически нативный FastRoute с возможностью использовать различные стратегии обработки (CharCountBased, GroupCountBased...)
- возможность работать как в концепции PSR-7/PSR-15, так и вне их, используя концепцию FastRoute 
- быстрый множественный dispatch и reverse (при остающейся возможности динамического добавления роутов)

## Если нам нужен просто FastRoute с reversed routing

```
$router = new Router();
$router->addRoute('GET', '/test', function () {
    // Handler here!
}, 'test');
$router->addRoute('GET', '/test/{name:[a-z]+}', function () {
    // Handler for name here!
}, 'test_with_name');

$router->addGroup('GET', '/api', function (Router $router) {
    $router->addRoute('GET', '/users', function () {
        // Handler for name here!
    }, 'users')
}, 'api:');

$reversedRoute = $router->reverse('test_with_name', [
    'name' => 'somename'
]);
// '/test/somename'

$reversedRoute = $router->reverse('test_with_name', [
    'name' => 'somename',
    'additional' => 'variable'
]);
// '/test/somename?additional=variable'

// Just like with FastRoute
$data = $router->dispatch('GET', /test');
```
