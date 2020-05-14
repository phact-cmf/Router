<?php declare(strict_types=1);

namespace Phact\Router\Reverser;

use FastRoute\BadRouteException;
use Phact\Router\Reverser;
use LogicException;

use function is_string;
use function count;
use function in_array;
use function http_build_query;

class Std implements Reverser
{
    /**
     * @var array
     */
    protected $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * @inheritDoc
     */
    public function reverse(string $routeName, array $variables = []): string
    {
        if (!isset($this->routes[$routeName])) {
            throw new BadRouteException(sprintf(
                'Could not find route with name "%s"',
                $routeName
            ));
        }

        $route = $this->routes[$routeName];

        $url = '';
        $paramIdx = 0;
        $usedParams = [];
        foreach ($route as $part) {
            if (is_string($part)) {
                $url .= $part;
                continue;
            }

            if ($paramIdx === count($variables)) {
                throw new LogicException('Not enough parameters given');
            }

            $variableName = $part[0];
            $usedParams[] = $variableName;

            if (isset($variables[$variableName])) {
                $url .= $variables[$variableName];
                $paramIdx++;
                continue;
            }

            if (isset($variables[$paramIdx])) {
                $url .= $variables[$paramIdx];
                $paramIdx++;
                continue;
            }

            throw new LogicException('Incorrect parameters given');
        }

        $query = [];
        foreach ($variables as $param => $value) {
            if (is_string($param) && !in_array($param, $usedParams)) {
                $query[$param] = $value;
            }
        }
        return $url . ($query ? '?' . http_build_query($query) : '');
    }
}