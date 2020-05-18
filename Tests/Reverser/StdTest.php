<?php declare(strict_types=1);

namespace Tests\Reverser;

use FastRoute\BadRouteException;
use LogicException;
use Phact\Router\Reverser\Std;
use PHPUnit\Framework\TestCase;

class StdTest extends TestCase
{
    public function testExceptionOnNotExistingRoute(): void
    {
        $this->expectException(BadRouteException::class);
        $reverser = new Std([
            'example_with_two_parameters' =>[
                "/example/",
                 ["firstParameter", "[^/]+"],
                "/moreFixed/",
                 ["secondParameter", "[0-9]+"],
            ],
        ]);
        $reverser->reverse('example');
    }

    public function testRouteWithoutParameters(): void
    {
        $reverser = new Std([
            'example_no_parameters' =>[
                "/example",
            ],
        ]);
        $url = $reverser->reverse('example_no_parameters');
        $this->assertEquals('/example', $url);
    }

    public function testRouteWithoutParametersWithVariables(): void
    {
        $reverser = new Std([
            'example_no_parameters' =>[
                "/example",
            ],
        ]);
        $url = $reverser->reverse('example_no_parameters', [
            'additional' => 'value'
        ]);
        $this->assertEquals('/example?additional=value', $url);
    }

    public function testRouteWithParametersByNameEnough(): void
    {
        $reverser = new Std([
            'example_with_two_parameters' =>[
                "/example/",
                ["firstParameter", "[^/]+"],
                "/users/",
                ["secondParameter", "[0-9]+"],
            ],
        ]);
        $url = $reverser->reverse('example_with_two_parameters', [
            'firstParameter' => 'value',
            'secondParameter' => 'secondValue'
        ]);
        $this->assertEquals('/example/value/users/secondValue', $url);
    }

    public function testRouteWithParametersByIndexEnough(): void
    {
        $reverser = new Std([
            'example_with_two_parameters' =>[
                "/example/",
                ["firstParameter", "[^/]+"],
                "/users/",
                ["secondParameter", "[0-9]+"],
            ],
        ]);
        $url = $reverser->reverse('example_with_two_parameters', [
            'value',
            'secondValue'
        ]);
        $this->assertEquals('/example/value/users/secondValue', $url);
    }

    public function testRouteWithParametersByIndexAndByNameEnough(): void
    {
        $reverser = new Std([
            'example_with_two_parameters' =>[
                "/example/",
                ["firstParameter", "[^/]+"],
                "/users/",
                ["secondParameter", "[0-9]+"],
            ],
        ]);
        $url = $reverser->reverse('example_with_two_parameters', [
            'value',
            'secondParameter' => 'secondValue'
        ]);
        $this->assertEquals('/example/value/users/secondValue', $url);
    }

    public function testExceptionOnRouteWithParametersNotEnough(): void
    {
        $this->expectException(LogicException::class);
        $reverser = new Std([
            'example_with_two_parameters' =>[
                "/example/",
                ["firstParameter", "[^/]+"],
                "/users/",
                ["secondParameter", "[0-9]+"],
            ],
        ]);
        $reverser->reverse('example_with_two_parameters', [
            'firstParameter' => 'value',
        ]);
    }

    public function testExceptionOnRouteWithIncorrectParameters(): void
    {
        $this->expectException(LogicException::class);
        $reverser = new Std([
            'example_with_two_parameters' =>[
                "/example/",
                ["firstParameter", "[^/]+"],
                "/users/",
                ["secondParameter", "[0-9]+"],
            ],
        ]);
        $reverser->reverse('example_with_two_parameters', [
            'someOtherParameter' => 'value',
            'badNameParameter' => 'value',
        ]);
    }

    public function testRouteWithParametersByNameEnoughAndAdditionalVariable(): void
    {
        $reverser = new Std([
            'example_with_two_parameters' =>[
                "/example/",
                ["firstParameter", "[^/]+"],
                "/users/",
                ["secondParameter", "[0-9]+"],
            ],
        ]);
        $url = $reverser->reverse('example_with_two_parameters', [
            'firstParameter' => 'value',
            'secondParameter' => 'secondValue',
            'additional' => 'additionalValue'
        ]);
        $this->assertEquals('/example/value/users/secondValue?additional=additionalValue', $url);
    }

    public function testRouteWithParametersByIndexEnoughAndAdditionalVariable(): void
    {
        $reverser = new Std([
            'example_with_two_parameters' =>[
                "/example/",
                ["firstParameter", "[^/]+"],
                "/users/",
                ["secondParameter", "[0-9]+"],
            ],
        ]);
        $url = $reverser->reverse('example_with_two_parameters', [
            'value',
            'secondValue',
            'additional' => 'additionalValue'
        ]);
        $this->assertEquals('/example/value/users/secondValue?additional=additionalValue', $url);
    }

    public function testRouteWithParametersByIndexByNameEnoughAndAdditionalVariable(): void
    {
        $reverser = new Std([
            'example_with_two_parameters' =>[
                "/example/",
                ["firstParameter", "[^/]+"],
                "/users/",
                ["secondParameter", "[0-9]+"],
            ],
        ]);
        $url = $reverser->reverse('example_with_two_parameters', [
            'value',
            'secondParameter' => 'secondValue',
            'additional' => 'additionalValue'
        ]);
        $this->assertEquals('/example/value/users/secondValue?additional=additionalValue', $url);
    }

    public function testRouteWithParametersByNameEnoughAndAdditionalVariables(): void
    {
        $reverser = new Std([
            'example_with_two_parameters' =>[
                "/example/",
                ["firstParameter", "[^/]+"],
                "/users/",
                ["secondParameter", "[0-9]+"],
            ],
        ]);
        $url = $reverser->reverse('example_with_two_parameters', [
            'firstParameter' => 'value',
            'secondParameter' => 'secondValue',
            'additional' => 'additionalValue',
            'addSecond' => 'addSecondValue'
        ]);
        $expected = '/example/value/users/secondValue?additional=additionalValue&addSecond=addSecondValue';
        $this->assertEquals($expected, $url);
    }
}
