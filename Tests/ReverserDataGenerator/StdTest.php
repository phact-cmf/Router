<?php declare(strict_types=1);

namespace Tests\ReverserDataGenerator;

use FastRoute\BadRouteException;
use Phact\Router\ReverserDataGenerator\Std;
use PHPUnit\Framework\TestCase;

class StdTest extends TestCase
{
    public function testExceptionOnDuplicateName(): void
    {
        $this->expectException(BadRouteException::class);
        $dataGenerator = new Std();
        $dataGenerator->addRoute('example', [
            "/example/",
        ]);
        $dataGenerator->addRoute('example', [
            "/example/",
            ["firstParameter", "[^/]+"],
            "/moreFixed/",
            ["secondParameter", "[0-9]+"],
        ]);
    }

    public function testCorrectlyAddRoute(): void
    {
        $dataGenerator = new Std();
        $dataGenerator->addRoute('example', [
            "/example/",
            ["firstParameter", "[^/]+"],
            "/moreFixed/",
            ["secondParameter", "[0-9]+"],
        ]);
        $this->assertEquals([
            'example' => [
                "/example/",
                ["firstParameter", "[^/]+"],
                "/moreFixed/",
                ["secondParameter", "[0-9]+"],
            ]
        ], $dataGenerator->getData());
    }
}
