<?php
namespace sambhuti\controller;
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
 * @author    Piyush<piyush[at]cio[dot]bz>
 * @license   http://www.gnu.org/licenses/gpl.html
 * @copyright 2012 Piyush
 */
use sambhuti\core;

abstract class base extends core\container {
    static $dependencies = array('request.request', 'request.response');
    /** @var null|\sambhuti\core\dataFace $request */
    protected $request = null;
    /** @var null|\sambhuti\core\dataFace $response */
    protected $response = null;
    protected $raw = array();

    final function __construct ( array $dependencies = array() ) {
        $this->request = $dependencies['request.request'];
        $this->response = $dependencies['request.response'];
        $this->raw = $dependencies;
    }

    function get ( $id = null ) {
        return $this->response;
    }

    abstract function index ( array $args = array() );
}
