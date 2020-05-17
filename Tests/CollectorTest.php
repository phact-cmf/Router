<?php declare(strict_types=1);

namespace Tests;

use Phact\Router\Collector;
use PHPUnit\Framework\TestCase;
use Tests\Mocks\DummyDataGenerator;
use Tests\Mocks\DummyParser;
use Tests\Mocks\DummyReverserDataGenerator;

class CollectorTest extends TestCase
{
    public function testAddedRouteWithoutNameExistsInData(): void
    {
        $collector = new Collector(
            new DummyParser(),
            new DummyDataGenerator(),
            new DummyReverserDataGenerator()
        );
        $collector->map('GET', '/example', 'someHandler');
        $this->assertEquals(
            [['GET', ['/example'], 'someHandler']],
            $collector->getData()
        );
    }

    public function testAddedRouteWithNameExistsInData(): void
    {
        $collector = new Collector(
            new DummyParser(),
            new DummyDataGenerator(),
            new DummyReverserDataGenerator()
        );
        $collector->map('GET', '/example', 'someHandler', 'example_name');
        $this->assertEquals(
            [['GET', ['/example'], 'someHandler']],
            $collector->getData()
        );
    }

    public function testAddedRouteWithNameExistsInReversedData(): void
    {
        $collector = new Collector(
            new DummyParser(),
            new DummyDataGenerator(),
            new DummyReverserDataGenerator()
        );
        $collector->map('GET', '/example', 'someHandler', 'example_name');
        $this->assertEquals(
            [['example_name', ['/example']]],
            $collector->getReverserData()
        );
    }

    public function testAddedGroupProvidePrefixToChildRoute(): void
    {
        $collector = new Collector(
            new DummyParser(),
            new DummyDataGenerator(),
            new DummyReverserDataGenerator()
        );
        $collector->group('/example', static function (Collector $collector) {
            $collector->map('GET', '/all', 'someHandler');
        });
        $this->assertEquals(
            [['GET', ['/example/all'], 'someHandler']],
            $collector->getData()
        );
    }

    public function testAddedGroupProvideNameToChildRoute(): void
    {
        $collector = new Collector(
            new DummyParser(),
            new DummyDataGenerator(),
            new DummyReverserDataGenerator()
        );
        $collector->group('/example', static function (Collector $collector) {
            $collector->map('GET', '/all', 'someHandler', 'all');
        }, 'example:');
        $this->assertEquals(
            [['example:all', ['/example/all']]],
            $collector->getReverserData()
        );
    }

    public function testAddedGroupProvidePrefixToSubgroupAndRoute(): void
    {
        $collector = new Collector(
            new DummyParser(),
            new DummyDataGenerator(),
            new DummyReverserDataGenerator()
        );
        $collector->group('/example', static function (Collector $collector) {
            $collector->group('/values', static function (Collector $collector) {
                $collector->map('GET', '/all', 'someHandler');
            });
        });
        $this->assertEquals(
            [['GET', ['/example/values/all'], 'someHandler']],
            $collector->getData()
        );
    }

    public function testAddedGroupProvideNameToSubgroupAndRoute(): void
    {
        $collector = new Collector(
            new DummyParser(),
            new DummyDataGenerator(),
            new DummyReverserDataGenerator()
        );
        $collector->group('/example', static function (Collector $collector) {
            $collector->group('/values', static function (Collector $collector) {
                $collector->map('GET', '/all', 'someHandler', 'all');
            }, 'values:');
        }, 'example:');
        $this->assertEquals(
            [['example:values:all', ['/example/values/all']]],
            $collector->getReverserData()
        );
    }

    public function testAddedGroupProvideCorrectPrefixToChildRouteAfterChildGroup(): void
    {
        $collector = new Collector(
            new DummyParser(),
            new DummyDataGenerator(),
            new DummyReverserDataGenerator()
        );
        $collector->group('/example', static function (Collector $collector) {
            $collector->group('/values', static function (Collector $collector) {
                $collector->map('GET', '/all', 'someHandler');
            });
            $collector->map('GET', '/users', 'someHandler');
        });
        $this->assertEquals(
            [
                ['GET', ['/example/values/all'], 'someHandler'],
                ['GET', ['/example/users'], 'someHandler'],
            ],
            $collector->getData()
        );
    }

    public function testAddedGroupProvideCorrectNameToChildRouteAfterChildGroup(): void
    {
        $collector = new Collector(
            new DummyParser(),
            new DummyDataGenerator(),
            new DummyReverserDataGenerator()
        );
        $collector->group('/example', static function (Collector $collector) {
            $collector->group('/values', static function (Collector $collector) {
                $collector->map('GET', '/all', 'someHandler', 'all');
            }, 'values:');
            $collector->map('GET', '/users', 'someHandler', 'users');
        }, 'example:');
        $this->assertEquals(
            [
                ['example:values:all', ['/example/values/all']],
                ['example:users', ['/example/users']],
            ],
            $collector->getReverserData()
        );
    }

    public function testGetCorrectGroupNameWithoutGroup(): void
    {
        $collector = new Collector(
            new DummyParser(),
            new DummyDataGenerator(),
            new DummyReverserDataGenerator()
        );
        $collector->map('GET', '/example', 'someHandler', 'example_name');
        $this->assertEquals('', $collector->getCurrentGroupName());
    }

    public function testGetCorrectGroupNameWithGroup(): void
    {
        $collector = new Collector(
            new DummyParser(),
            new DummyDataGenerator(),
            new DummyReverserDataGenerator()
        );
        $collector->group('/example', function (Collector $collector) {
            $this->assertEquals('group:', $collector->getCurrentGroupName());
        }, 'group:');
    }

    public function testGetCorrectGroupNameWithSubgroup(): void
    {
        $collector = new Collector(
            new DummyParser(),
            new DummyDataGenerator(),
            new DummyReverserDataGenerator()
        );
        $collector->group('/example', function (Collector $collector) {
            $collector->group('/sub', function (Collector $collector) {
                $this->assertEquals('group:sub:', $collector->getCurrentGroupName());
            }, 'sub:');
        }, 'group:');
    }

    public function testGetCorrectGroupNameAfterSubgroup(): void
    {
        $collector = new Collector(
            new DummyParser(),
            new DummyDataGenerator(),
            new DummyReverserDataGenerator()
        );
        $collector->group('/example', function (Collector $collector) {
            $collector->group('/sub', static function (Collector $collector) {
                $collector->map('GET', '/all', 'someHandler', 'all');
            }, 'sub:');
            $this->assertEquals('group:', $collector->getCurrentGroupName());
        }, 'group:');
    }

    public function testGetCorrectGroupNameAfterGroup(): void
    {
        $collector = new Collector(
            new DummyParser(),
            new DummyDataGenerator(),
            new DummyReverserDataGenerator()
        );
        $collector->group('/example', static function (Collector $collector) {
            $collector->map('GET', '/all', 'someHandler', 'all');
        }, 'group:');
        $this->assertEquals('', $collector->getCurrentGroupName());
    }
}
