<?php

namespace Phact\Router;

use Exception;
use InvalidArgumentException;
use Phact\Helpers\Text;
use Traversable;

/**
 * Class Router
 * Inspired by AltoRouter
 * @link https://github.com/dannyvankooten/AltoRouter
 *
 * @package Phact\Router
 */
class Router
{
    /**
     * @var array Array of all routes (incl. named routes).
     */
    protected $routes = [];

    /**
     * @var array Array of all named routes.
     */
    protected $namedRoutes = [];

    /**
     * @var string Can be used to ignore leading part of the Request URL (if main file lives in subdirectory of host)
     */
    protected $basePath = '';

    protected $cacheTimeout = 10;
    
    protected $matched = [];

    /**
     * @var array Array of default match types (regex helpers)
     */
    protected $matchTypes = array(
        'i' => '[0-9]++',
        'a' => '[0-9A-Za-z]++',
        's' => '[0-9A-Za-z\-]++',
        'slug' => '[0-9A-Za-z_\-]++',
        'h' => '[0-9A-Fa-f]++',
        '*' => '.+?',
        '**' => '.++',
        '' => '[^/\.]++'
    );

    /**
     * @var CacheInterface
     */
    protected $_cacheDriver;

    /**
     * @var string|null
     */
    protected $_configPath;

    public function __construct(string $configPath = null, $cacheDriver = null)
    {
        $this->_cacheDriver = $cacheDriver;
        $this->_configPath = $configPath;
    }

    /**
     * Retrieves all routes.
     * Useful if you want to process or display routes.
     * @return array All routes.
     */
    public function getRoutes()
    {
        $this->fetchRoutes();
        return $this->routes;
    }

    /**
     * Retrieves all routes.
     * Useful if you want to process or display routes.
     * @return array All routes.
     */
    public function getNamedRoutes()
    {
        $this->fetchRoutes();
        return $this->namedRoutes;
    }

    protected function fetchRoutes()
    {
        if (empty($this->routes)) {
            $routes = null;
            $cacheKey = 'PHACT__ROUTER';
            if (!is_null($this->cacheTimeout) && $this->_cacheDriver) {
                $routes = $this->_cacheDriver->get($cacheKey);
                if ($routes) {
                    $this->namedRoutes = $routes['named'];
                    $this->routes = $routes['all'];
                }
                $this->matched = $this->getMatchedRoutes();
            }

            if (!$routes) {
                if ($this->_configPath) {
                    $this->collectFromFile($this->_configPath);
                }
                if (!is_null($this->cacheTimeout) && $this->_cacheDriver) {
                    $routes = [
                        'named' => $this->namedRoutes,
                        'all' => $this->routes
                    ];
                    $this->_cacheDriver->set($cacheKey, $routes, $this->cacheTimeout);
                }
            }
        }
        return $this->routes;
    }

    /**
     * Add multiple routes at once from array in the following format:
     *
     *   $routes = array(
     *      array($method, $route, $target, $name)
     *   );
     *
     * @param array $routes
     * @return void
     * @author Koen Punt
     * @throws Exception
     */
    public function addRoutes($routes)
    {
        if (!is_array($routes) && !$routes instanceof Traversable) {
            throw new Exception('Routes should be an array or an instance of Traversable');
        }
        foreach ($routes as $route) {
            call_user_func_array(array($this, 'map'), $route);
        }
    }

    /**
     * Set the base path.
     * Useful if you are running your application from a subdirectory.
     * @param $basePath
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * Add named match types. It uses array_merge so keys can be overwritten.
     *
     * @param array $matchTypes The key is the name and the value is the regex.
     */
    public function addMatchTypes($matchTypes)
    {
        $this->matchTypes = array_merge($this->matchTypes, $matchTypes);
    }

    /**
     * Map a route to a target
     *
     * @param string $method One of 5 HTTP Methods, or a pipe-separated list of multiple HTTP Methods (GET|POST|PATCH|PUT|DELETE)
     * @param string $route The route regex, custom regex must start with an @. You can use multiple pre-set regex filters, like [i:id]
     * @param mixed $target The target where this route should point to. Can be anything.
     * @param string $name Optional name of this route. Supply if you want to reverse route this url in your application.
     * @throws Exception
     */
    public function map($method, $route, $target, $name = null)
    {
        if ($route == '') {
            $route = '/';
        }

        $this->routes[] = array($method, $route, $target, $name);

        if ($name) {
            if (isset($this->namedRoutes[$name])) {
                throw new \Exception("Can not redeclare route '{$name}'");
            } else {
                $this->namedRoutes[$name] = $route;
            }
        }

        return;
    }

    /**
     * Reversed routing
     *
     * Generate the URL for a named route. Replace regexes with supplied parameters
     *
     * @param string $routeName The name of the route.
     * @param array @params Associative array of parameters to replace placeholders with.
     * @return string The URL of the route with named parameters in place.
     * @throws Exception
     */
    public function url($routeName, $params = array())
    {
        $namedRoutes = $this->getNamedRoutes();

        // Check if named route exists
        if (!isset($namedRoutes[$routeName])) {
            throw new \Exception("Route '{$routeName}' does not exist.");
        }

        if (!is_array($params)) {
            $params = [$params];
        }
        // Replace named parameters
        $route = $namedRoutes[$routeName];

        // prepend base path to route url again
        $url = $this->basePath . $route;

        $matches = isset($this->matched[$routeName]) ? $this->matched[$routeName] : null;
        if (is_null($matches)) {
            preg_match_all('`(\/|)\{.*?:(.+?)\}(\?|)`', $route, $matches, PREG_SET_ORDER);
            $this->matched[$routeName] = $matches;
            $this->setMatchedRoutes($this->matched);
        }
        $usedParams = [];
        if ($matches) {
            $counter = 0;
            foreach ($matches as $match) {
                $param = $match[2];
                $block = $match[0];
                if ($match[1]) {
                    $block = substr($block, 1);
                }
                if (isset($params[$param])) {
                    $url = str_replace($block, $params[$param], $url);
                } elseif (isset($params[$counter])) {
                    $url = str_replace($block, $params[$counter], $url);
                } elseif ($match[3]) {
                    $url = str_replace($match[1] . $block, '', $url);
                } else {
                    throw new InvalidArgumentException('Incorrect params of route');
                }
                $usedParams[] = $param;
                $counter++;
            }
        }
        $query = [];
        foreach ($params as $param => $value) {
            if (is_string($param) && !in_array($param, $usedParams)) {
                $query[$param] = $value;
            }
        }

        return $url . ($query ? '?' . http_build_query($query) : '');
    }

    /**
     * Match a given Request Url against stored routes
     * @param string $requestUrl
     * @param string $requestMethod
     * @return array|boolean Array with route information on success, false on failure (no match).
     * @throws Exception
     */
    public function match($requestUrl = null, $requestMethod = null)
    {
        $params = array();
        $match = false;

        // set Request Url if it isn't passed as parameter
        if ($requestUrl === null) {
            $requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        } elseif ($requestUrl === '') {
            $requestUrl = '/';
        }

        // strip base path from request url
        $requestUrl = substr($requestUrl, strlen($this->basePath));

        // Strip query string (?a=b) from Request Url
        if (($strpos = strpos($requestUrl, '?')) !== false) {
            $requestUrl = substr($requestUrl, 0, $strpos);
        }

        // set Request Method if it isn't passed as a parameter
        if ($requestMethod === null) {
            $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        }

        $matches = [];
        $compiled = $this->getCompiledRoutes();
        $setCompiled = false;
        foreach ($this->getRoutes() as $handler) {
            list($method, $_route, $target, $name) = $handler;

            $methods = explode('|', $method);
            $method_match = false;

            // Check if request method matches. If not, abandon early. (CHEAP)
            foreach ($methods as $method) {
                if (strcasecmp($requestMethod, $method) === 0) {
                    $method_match = true;
                    break;
                }
            }

            // Method did not match, continue to next route.
            if (!$method_match) continue;

            // Check for a wildcard (matches all)
            if ($_route === '*') {
                $match = true;
            } elseif (isset($_route[0]) && $_route[0] === '@') {
                $pattern = '`' . substr($_route, 1) . '`u';
                $match = preg_match($pattern, $requestUrl, $params);
            } else {
                $route = null;
                $regex = false;
                $j = 0;
                $n = isset($_route[0]) ? $_route[0] : null;
                $i = 0;

                // Find the longest non-regex substring and match it against the URI
                while (true) {
                    if (!isset($_route[$i])) {
                        break;
                    } elseif (false === $regex) {
                        $c = $n;
                        $regex = $c === '[' || $c === '(' || $c === '.';
                        if (false === $regex && false !== isset($_route[$i + 1])) {
                            $n = $_route[$i + 1];
                            $regex = $n === '?' || $n === '+' || $n === '*' || $n === '{';
                        }
                        if (false === $regex && $c !== '/' && (!isset($requestUrl[$j]) || $c !== $requestUrl[$j])) {
                            continue 2;
                        }
                        $j++;
                    }
                    $route .= $_route[$i++];
                }

                if (!isset($compiled[$route])) {
                    $setCompiled = true;
                    $compiled[$route] = $this->compileRoute($route);
                }
                $regex = $compiled[$route];
                $match = preg_match($regex, $requestUrl, $params);
            }

            if (($match == true || $match > 0)) {

                if ($params) {
                    foreach ($params as $key => $value) {
                        if (is_numeric($key)) unset($params[$key]);
                    }
                }

                $matches[] = array(
                    'target' => $target,
                    'params' => $params,
                    'name' => $name
                );
            }
        }
        if ($setCompiled) {
            $this->setCompiledRoutes($compiled);
        }

        return $matches;
    }

    protected function getCompiledRoutes()
    {
        if (!$this->cacheTimeout || !$this->_cacheDriver) {
            return [];
        }
        return $this->_cacheDriver->get('PHACT__ROUTER_COMPILED');
    }

    protected function setCompiledRoutes($routes)
    {
        if (!$this->cacheTimeout || !$this->_cacheDriver) {
            return true;
        }
        $this->_cacheDriver->set('PHACT__ROUTER_COMPILED', $routes, $this->cacheTimeout);
        return true;
    }

    protected function getMatchedRoutes()
    {
        if (!$this->cacheTimeout || !$this->_cacheDriver) {
            return [];
        }
        return $this->_cacheDriver->get('PHACT__ROUTER_MATCHED', []);
    }

    /**
     * @param $routes
     * @return bool
     */
    protected function setMatchedRoutes($routes)
    {
        if (!$this->cacheTimeout || !$this->_cacheDriver) {
            return true;
        }
        $this->_cacheDriver->set('PHACT__ROUTER_MATCHED', $routes, $this->cacheTimeout);
        return true;
    }

    /**
     * Compile the regex for a given route (EXPENSIVE)
     * @param $route
     * @return string
     */
    protected function compileRoute($route)
    {
        if (preg_match_all('`(/|\.|)\{([^:\}]*+)(?::([^:\}]*+))?\}(\?|)`', $route, $matches, PREG_SET_ORDER)) {

            $matchTypes = $this->matchTypes;
            foreach ($matches as $match) {
                list($block, $pre, $type, $param, $optional) = $match;

                if (isset($matchTypes[$type])) {
                    $type = $matchTypes[$type];
                }
                if ($pre === '.') {
                    $pre = '\.';
                }

                //Older versions of PCRE require the 'P' in (?P<named>)
                $pattern = '(?:'
                    . ($pre !== '' ? $pre : null)
                    . '('
                    . ($param !== '' ? "?P<$param>" : null)
                    . $type
                    . '))'
                    . ($optional !== '' ? '?' : null);

                $route = str_replace($block, $pattern, $route);
            }

        }
        return "`^$route$`u";
    }

    /**
     * Append routes from array
     *
     * @param array $configuration
     * @param string $namespace
     * @param string $route
     * @throws Exception
     */
    public function collect($configuration = [], $namespace = '', $route = '')
    {
        foreach ($configuration as $item) {
            $this->appendRoute($item, $namespace, $route);
        }
    }

    /**
     * Append single route
     * @param $item
     * @param string $namespace
     * @param string $route
     * @throws Exception
     */
    public function appendRoute($item, $namespace = '', $route = '/')
    {
        $methods = isset($item['methods']) ? $item['methods'] : ["GET", "POST"];
        $method = implode('|', $methods);
        $name = isset($item['name']) ? $item['name'] : '';
        if ($name && $namespace) {
            $name = $namespace . ':' . $name;
        }
        $path = isset($item['route']) ? $item['route'] : '';
        if ($route || $path) {
            $path = $route . $path;
        }
        $target = isset($item['target']) ? $item['target'] : null;
        $this->map($method, $path, $target, $name);
    }
}