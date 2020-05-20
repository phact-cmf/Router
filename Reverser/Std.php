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
        $this->hasRouteCheck($routeName);

        $route = $this->routes[$routeName];

        $url = '';
        $counter = -1;
        $usedParams = [];
        foreach ($route as $part) {
            if (is_string($part)) {
                $url .= $part;
                continue;
            }

            if ($counter === count($variables)) {
                throw new LogicException('Not enough parameters given');
            }

            $variableName = $part[0];
            $usedParams[] = $variableName;
            $counter++;

            if (isset($variables[$variableName])) {
                $url .= $variables[$variableName];
                continue;
            }

            if (isset($variables[$counter])) {
                $url .= $variables[$counter];
                continue;
            }

            throw new LogicException('Incorrect parameters given');
        }

        $query = $this->buildQuery($variables, $usedParams);
        $query = $query ? '?' . $query : '';

        return $url . $query;
    }

    /**
     * @param string $routeName
     */
    public function hasRouteCheck(string $routeName): void
    {
        if (!isset($this->routes[$routeName])) {
            throw new BadRouteException(sprintf(
                'Could not find route with name "%s"',
                $routeName
            ));
        }
    }

    /**
     * Build query string from unused params
     *
     * @param array $variables
     * @param array $usedParams
     * @return string
     */
    protected function buildQuery(array $variables, array $usedParams): string
    {
        $query = [];
        foreach ($variables as $param => $value) {
            if (is_string($param) && !in_array($param, $usedParams)) {
                $query[$param] = $value;
            }
        }
        if (!$query) {
            return '';
        }
        return http_build_query($query);
    }
}
