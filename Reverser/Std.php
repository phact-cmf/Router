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
        $counter = 0;
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

            $url .= $this->retrieveVariable($variables, $counter, $variableName);
            $counter++;
        }

        $query = $this->buildQuery($variables, $usedParams);
        $query = $query ? '?' . $query : '';

        return $url . $query;
    }

    /**
     * Get variables from variables by name or by index
     *
     * @param array $variables
     * @param int $index
     * @param string $variableName
     * @return string
     */
    public function retrieveVariable(array $variables, int $index, string $variableName): string
    {
        if (isset($variables[$variableName])) {
            return $variables[$variableName];
        }

        if (isset($variables[$index])) {
            return $variables[$index];
        }
        throw new LogicException('Incorrect parameters given');
    }

    /**
     * Check that route with given name exists
     *
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
