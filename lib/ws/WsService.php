<?php
/**
 * This file is part of Sixties, a set of PHP classes for playing with XMPP PubSub
 *
 * Copyright (C) 2009  Clochix.net
 *
 * Sixties is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Sixties is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Sixties; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   WS
 * @package    Sixties
 * @subpackage WebService
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @link       https://labo.clochix.net/projects/show/sixties
 */

/**
 * WsService : base for all web services classes called by BbRest
 *
 * @category   Library
 * @package    Sixties
 * @subpackage WebService
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */
abstract class WsService
{

    /**
     * Get the list of all available actions for a module
     *
     * @return WsResponse
     */
    public function Options() {
        $useAnnotations = class_exists('UrlMap');
        $methods = get_class_methods($this);
        $result  = array();
        // Get all methods whose name is of the form actionMethod
        foreach ($methods as $name) {
            if (preg_match('/([a-z]+)([A-Z]{1}[a-z]+)/', $name, $matches) == 1) {
                $args = null;
                if ($useAnnotations) {
                    $reflection = new ReflectionAnnotatedMethod($this, $name);
                    $urlmap = $reflection->getAnnotation('UrlMap');
                    if ($urlmap) {
                        $args = $urlmap->value;
                    }
                }
                $res = array($matches[2], $args);
                if (!isset($result[$matches[1]])) {
                    $result[$matches[1]] = array($res);
                } else {
                    $result[$matches[1]][] = $res;
                }
            }
        }
        return new WsResponse($result, WsResponse::WS_RESPONSE_OK);
    }

    /**
     * Check the presence of mandatory parameters
     *
     * @param array $expected array of expected parameters
     * @param array $actual   the actual parameters
     *
     * @throws 400 on error
     *
     * @return boolean
     */
    protected function checkparams($expected, $actual) {
        foreach ($expected as $param) {
            if (!isset($actual[$param])) {
                throw new WsException("Missing parameter $param ", 400);
            }
        }
        return true;
    }
}


/**
 * WsException : Exceptions throwns by the web service
 *
 * @category   Library
 * @package    Sixties
 * @subpackage WebService
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */
class WsException extends Exception
{
}

/**
 * WsResponse : Class for the objects returned by the web service
 *
 * @category   Library
 * @package    Sixties
 * @subpackage WebService
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */
class WsResponse
{
    public $code;
    public $message;

    const WS_RESPONSE_OK = 200;
    const WS_RESPONSE_KO = 500;
    /**
     * Constructor
     *
     * @param mixed   $message the content of the response
     * @param integer $code    ok or ko
     *
     * @return void
     */
    public function __construct($message = '', $code = 200){
        $this->message = $message;
        $this->code    = $code;
    }
}