<?php
/**
 * Sambhuti
 * Copyright (C) 2012-2013 Piyush
 *
 * License:
 * This file is part of Sambhuti (http://sambhuti.org)
 *
 * Sambhuti is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Sambhuti is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Sambhuti.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Sambhuti
 * @author    Piyush <piyush@cio.bz>
 * @license   http://www.gnu.org/licenses/gpl.html
 * @copyright 2012 Piyush
 */

namespace sambhuti\controller;

use sambhuti\core;
use sambhuti\loader;

/**
 * Controller Container
 *
 * Stores all controllers
 *
 * @package    Sambhuti
 * @subpackage controller
 * @author     Piyush <piyush@cio.bz>
 * @license    http://www.gnu.org/licenses/gpl.html
 * @copyright  2012 Piyush
 */
class Container implements IContainer
{

    /**
     * Dependencies
     *
     * @static
     * @var array Array of dependency strings
     */
    public static $dependencies = ['config.routing', 'core', 'loader', 'request.request'];

    /**
     * Core
     *
     * Instance of Core
     *
     * @var null|\sambhuti\core\ICore $core
     */
    protected $core = null;

    /**
     * Routing Config
     *
     * @var null|\sambhuti\core\IData $routing
     */
    protected $routing = null;

    /**
     * Routes
     *
     * @var null|array
     */
    protected $routes = null;

    /**
     * System Routes
     *
     * @var null|array
     */
    protected $system = null;

    /**
     * Loader
     *
     * Instance of Loader
     *
     * @var null|\sambhuti\loader\IContainer $loader
     */
    protected $loader = null;

    /**
     * Not Found Controller
     *
     * @var null|\sambhuti\controller\IController
     */
    protected $error = null;

    /**
     * Controller instances
     *
     * @var array
     */
    protected $controllers = [];

    /**
     * Constructor
     *
     * Should set up not found, home etc from routing
     *
     * @param \sambhuti\core\IData $routing instance of routing
     * @param \sambhuti\core\ICore $core    instance of Core
     * @param \sambhuti\loader\IContainer $loader  instance of Loader
     * @param \sambhuti\core\IData $request instance of request
     */
    public function __construct(core\IData $routing, core\ICore $core, loader\IContainer $loader, core\IData $request)
    {
        $this->routing = $routing;
        $this->addRoutes($routing->get("routes"));
        $this->system = $routing->get("system");
        $this->core = $core;
        $this->loader = $loader;
        $this->error = $this->process($this->system['error']);
        $this->controllers['home'] = $this->process($this->system['home']);
        $this->request = $request;
    }

    public function setRequest(core\IData $request)
    {
        $this->request = $request;
    }

    /**
     * Get
     *
     * Takes in a command in format controller/method/arg1/arg2...
     * and calls controller::method(array(arg1,arg2...))
     * if exists or returns not found controller
     *
     * @param null $uri
     *
     * @return \sambhuti\controller\IController
     */
    public function get($uri = null)
    {
        try {
            $this->request->update('uri', $uri);
            if (empty($uri)) {
                return $this->get('home');
            }
            foreach ($this->routes as $route) {
                $mapping = $this->mapRoute($route);
                if ($mapping) {
                    break;
                }
            }
            if (empty($mapping)) {
                throw new \Exception("notFound");
            }
            extract($mapping, EXTR_OVERWRITE);
            $controller = core\Utils::camelCase($controller, ['caps' => true]);
            $action = core\Utils::camelCase($action);
            if (0 === strpos($controller, 'System')) {
                throw new \Exception("forbidden");
            } else {
                $object = $this->process($controller);
            }
            if (null === $object || !is_callable([$object, $action])) {
                throw new \Exception("notFound");
            }
            $object->$action($args);
        } catch (\Exception $e) {
            $object = $this->error;
            $action = $e->getMessage();
            $object->$action();
        }
        return $object;
    }

    protected function addRoutes($routes)
    {
        foreach ($routes as $name => $route) {
            $regex = isset($route['regex']) ? $route['regex'] : [];
            $this->routes[$name] = [
                'uri' => isset($route['uri']) ? $this->compileRegex($route['uri'], $regex) : NULL,
                'subdomain' => isset($route['subdomain']) ? $this->compileRegex($route['subdomain'], $regex) : NULL,
                'defaults' => isset($route['defaults']) ? $route['defaults'] : [],
                'callbacks' => isset($route['callbacks']) ? $route['callbacks'] : [],
            ];
        }
    }


    // This function was practically copied from Kohana's Route::compile()
    protected function compileRegex($path, $regex)
    {
        // Escape special characters in $path to prepare it as a regex.
        // This is similar to preg_quote(), but leaves the characters :() unescaped
        $path = preg_replace('#[.\\+*?[^\\]${}=!|<>]#', '\\\\$0', $path);

        // If there are optional parts of the $path (for example if the path is "foo(/bar)"
        // turn them into noncapturing groupings.
        $path = str_replace(['(', ')'], ['(?:', ')?'], $path);

        // Insert the default regex for all captures
        $path = preg_replace('/:(\w+)/', '(?P<$1>\w++)', $path);

        // Replace the default regex with custom regexes
        if ($regex) {
            $search = $replace = [];
            foreach ($regex as $key => $new) {
                $search[] = "<$key>\w++";
                $replace[] = "<$key>$new";
            }
            $path = str_replace($search, $replace, $path);
        }

        return '#^' . $path . '$#uD';
    }


    protected function mapRoute($route)
    {
        $subdomain = $this->request->get('subdomain');
        $uri = $this->request->get('uri');

        $args = $uri_args = $subdomain_args = [];

        // Check if URI matches
        if ($route['uri'])
            if (!preg_match($route['uri'], $uri, $uri_args))
                return false;

        // Check if the subdomain matches
        if ($route['subdomain'])
            if (!preg_match($route['subdomain'], $subdomain, $subdomain_args))
                return false;

        // Extract named arguments from subdomain first, then from URI, so that URI will overwrite in the case of name conflict.
        foreach ($subdomain_args as $k => $v)
            if (!is_int($k))
                $args[$k] = $v;
        foreach ($uri_args as $k => $v)
            if (!is_int($k))
                $args[$k] = $v;

        // Populate default values.
        foreach ($route['defaults'] as $k => $v)
            if (!isset($args[$k]) || $args[$k] === '')
                $args[$k] = $v;

        // Are there user callbacks?
        // If callbacks return something falseish, it's a failed match.
        // If callbacks return an array, then it should replace $args.
        foreach ($route['callbacks'] as $filter) {
            $result = call_user_func($filter, $route, $args, $this->request);
            if (!$result)
                return false;
            if (is_array($result))
                $args = $result;
        }

        // No controller or action?  Then the route did not match.
        if (empty($args['controller'])) {
            return false;
        }
        $controller = $args['controller'];
        $action = empty($args['action']) ? 'index' : $args['action'];

        return ['controller' => $controller, 'action' => $action, 'args' => $args];
    }

    /**
     * Process
     *
     * Processes single controller identifier to full name and returns instance or null
     *
     * @param string $controller name
     *
     * @return null|\sambhuti\controller\IController controller instance
     */
    public function process($controller)
    {
        if (empty($this->controllers[$controller])) {
            $class = $this->loader->fetch('controller' . '\\' . $controller . "Controller");
            if (null !== $class) {
                $this->controllers[$controller] = $this->core->process($class);
            } else {
                $this->controllers[$controller] = null;
            }
        }

        return $this->controllers[$controller];
    }
}