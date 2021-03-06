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

namespace sambhuti\core;

use sambhuti\loader;

/**
 * Core Container Class
 *
 * This class is the core dependency injection container for everything in sambhuti
 *
 * @package    Sambhuti
 * @subpackage core
 * @author     Piyush <piyush@cio.bz>
 * @license    http://www.gnu.org/licenses/gpl.html
 * @copyright  2012 Piyush
 */
class Core implements ICore
{

    /**
     * Dependencies
     *
     * @static
     * @var array Array of dependency strings
     */
    public static $dependencies = ['loader'];

    /**
     * Processed
     *
     * All Containers are stored in this array
     *
     * @var array
     */
    protected $processed = [];

    /**
     * Loader
     *
     * Another copy of loader is kept separate for clarity's sake
     *
     * @var null|\sambhuti\loader\IContainer
     */
    protected $loader = null;

    /**
     * Constructor
     *
     * Sets up Loader and itself into the processed array
     *
     * @param \sambhuti\loader\IContainer $loader
     */
    public function __construct(loader\IContainer $loader)
    {
        $this->loader = $loader;
        $this->set('loader', $loader);
        $this->set('core', $this);
    }

    /**
     * Get
     *
     * @fixme Write better doc here
     *
     *
     * @param null|string $identifier
     *
     * @return mixed|\sambhuti\core\IContainer container or response of "get" method based on string
     */
    public function get($identifier = null)
    {
        if (null === $identifier) {
            return $this;
        }
        if (empty($this->processed[$identifier])) {
            $class_ident = $identifier . "\\Container";
            $processed_class = null;
            if (false === strpos($identifier, '.')) {
                $processed_class = $this->fetchProcess($class_ident);
                if (method_exists($processed_class, 'instance')) {
                    $processed_class = $processed_class->instance();
                }
            } else {
                $parts = explode('.', $identifier);
                $processed_class = $this->loopGet($parts, $identifier);
            }
            $this->processed[$identifier] = $processed_class;
        }
        return $this->processed[$identifier];
    }

    /**
     * Set
     *
     * @param $identifier string
     * @param $value object
     */
    public function set($identifier, $value)
    {
        $this->processed[$identifier] = $value;
    }

    /**
     * Fetch and Process
     * @param $class_ident string
     * @return object
     * @throws \Exception
     */
    public function fetchProcess($class_ident)
    {
        $class = $this->loader->fetch($class_ident);
        if (empty($class)) {
            throw new \Exception("Cannot find " . $class_ident);
        }
        return $this->process($class);
    }

    /**
     * Process
     *
     * @fixme Write better doc here
     *
     * @param string $class
     *
     * @return object
     * @throws \Exception
     */
    public function process($class)
    {
        if (empty($class) || !class_exists($class)) {
            throw new \Exception($class . ' not found');
        }
        $dependencies = [];
        if (!empty($class::$dependencies)) {
            $dependencies = @array_map(array($this, 'get'), $class::$dependencies);
        }
        $count = count($dependencies);
        //implement an ugly hack for speed
        switch ($count) {
            case 0:
                return new $class();
            case 1:
                return new $class($dependencies[0]);
            case 2:
                return new $class($dependencies[0], $dependencies[1]);
            case 3:
                return new $class($dependencies[0], $dependencies[1], $dependencies[2]);
            case 4:
                return new $class($dependencies[0], $dependencies[1], $dependencies[2], $dependencies[3]);
            case 5:
                return new $class($dependencies[0], $dependencies[1], $dependencies[2], $dependencies[3], $dependencies[4]);
            case 6:
                return new $class($dependencies[0], $dependencies[1], $dependencies[2], $dependencies[3], $dependencies[4], $dependencies[5]);
            case 7:
                return new $class($dependencies[0], $dependencies[1], $dependencies[2], $dependencies[3], $dependencies[4], $dependencies[5], $dependencies[6]);
            case 8:
                return new $class($dependencies[0], $dependencies[1], $dependencies[2], $dependencies[3], $dependencies[4], $dependencies[5], $dependencies[6], $dependencies[7]);
            case 9:
                return new $class($dependencies[0], $dependencies[1], $dependencies[2], $dependencies[3], $dependencies[4], $dependencies[5], $dependencies[6], $dependencies[7], $dependencies[8]);
            case 10:
                return new $class($dependencies[0], $dependencies[1], $dependencies[2], $dependencies[3], $dependencies[4], $dependencies[5], $dependencies[6], $dependencies[7], $dependencies[8], $dependencies[9]);
            case 11:
                return new $class($dependencies[0], $dependencies[1], $dependencies[2], $dependencies[3], $dependencies[4], $dependencies[5], $dependencies[6], $dependencies[7], $dependencies[8], $dependencies[9], $dependencies[10]);
            case 12:
                return new $class($dependencies[0], $dependencies[1], $dependencies[2], $dependencies[3], $dependencies[4], $dependencies[5], $dependencies[6], $dependencies[7], $dependencies[8], $dependencies[9], $dependencies[10], $dependencies[11]);
            default:
                //more than 12 dependencies, go for good old reflection
                $reflection = new \ReflectionClass($class);

                return $reflection->newInstanceArgs($dependencies);
        }
    }

    /**
     * Loop get
     *
     * @param array $parts
     * @param string $identifier
     * @return mixed|Core|IContainer
     * @throws \Exception
     */
    protected function loopGet(array $parts, $identifier = '')
    {
        $current = $this;
        foreach ($parts as $part) {
            if (!is_object($current)) {
                throw new \Exception ('Cannot load ' . $identifier . ' dependency ' . $part . ' can not be loaded from a non-object');
            }
            $current = $current->get($part);
        }
        return $current;
    }
}
