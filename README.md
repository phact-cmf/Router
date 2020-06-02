# Router

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phact-cmf/Router/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phact-cmf/Router/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/phact-cmf/Router/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/phact-cmf/Router/?branch=master)
[![Build Status](https://travis-ci.org/phact-cmf/Router.svg?branch=master)](https://travis-ci.org/phact-cmf/Router)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/phact-cmf/Router/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

Follows PSR-7, PSR-15, and PSR-1, PSR-2, PSR-4, PSR-11, PSR-16.

Based on [FastRoute](https://github.com/nikic/FastRoute), inspired by [league/route](https://route.thephpleague.com/).

## Main ideas

- follow PSR-7/PSR-15 or using FastRoute approach
- reversed routing (URL generation by route name)
- almost native [FastRoute](https://github.com/nikic/FastRoute) with possibility of using various processing strategies (CharCountBased, GroupCountBased...)
- fast multiple dispatch and reverse (with ability to add routes dynamically)
- allows using custom Loader (allows to load routes from different formats, files, etc.)
- allows caching (PSR-16)
- flexible (you can replace any component: Invoker, Dispatcher, Reverser, etc.)

## Installation

```bash
composer require phact-cmf/router
```

## Requirements

- PHP >= 7.2

## Documentation

[Full documentation](docs/en.md)

[Доступна полная документация на русском языке](docs/ru.md)

## License

The MIT License (MIT). [License File](LICENSE.md).

