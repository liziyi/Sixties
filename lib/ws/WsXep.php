<?php
/**
 * This file is part of Sixties, a set of classes extending XMPPHP, the PHP XMPP library from Nathanael C Fritz
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
 * @category  Library
 * @package   Sixties
 * @author    Clochix <clochix@clochix.net>
 * @copyright 2009 Clochix.net
 * @license   http://www.gnu.org/licenses/gpl.txt GPL
 * @link      https://labo.clochix.net/projects/show/sixties
 */

require_once dirname(__FILE__) . '/../sixties/XMPP2.php';

/**
 * WsXep : Base class for the interfaces with Xep API
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
class WsXep extends BbRestService
{
    /**
     * @var XMPP2 the current connection
     */
    protected $conn;

    /**
     * @var integer max number of seconds we will wait an event from the server
     */
    protected $timeout;

    /**
     * Constructor : connect to the XMPP server
     *
     * @param array $params connexion parameters
     */
    public function __construct($params) {
        // set default timeout : 5s
        $this->timeout = 5;
        if (is_array($params)) {
            $this->conn = new XMPP2($params['host'], $params['port'], $params['user'], $params['password'], uniqid(get_class($this)), $params['server'], false, XMPPHP_Log::LEVEL_INFO);
            switch ($params['port']) {
            case '5280':
                session_start();
                // Use XMPP over BOSH
                $this->conn->connect("http://{$params['host']}:{$params['port']}/http-bind", 1, true);
                // Pinging the server is always a good idea ;)
                $this->conn->xep('ping')->ping();
                $res = $this->process(XepPing::EVENT_PONG);
                //@TODO : if it still don't work, do as in BbRest : if 503, go to sleep and try later
                break;
            default:
                $this->conn->connect();
                $this->conn->processUntil('session_start', $this->timeout);
                break;
            }
            $this->conn->logPath = '/tmp/xmpp.log';
            $this->conn->logXml = false;
        } else {
            //@TODO : set the connexion
        }
    }

    /**
     * Get current timeout
     *
     * @return integer
     */
    public function timeoutGet() {
        return $this->timeout;
    }

    /**
     * Set timeout
     *
     * @param integer $timeout the next timeout value
     *
     * @return WsXep $this
     */
    public function timeoutSet($timeout) {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Get the list of all available actions for a module
     *
     * @return XepResponse
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
        return new XepResponse($result, XepResponse::XEPRESPONSE_OK);
    }

    /**
     * Wait for an event and return the result
     *
     * @param string $event the event name
     *
     * @return mixed
     */
    protected function process($event) {
        if (!is_array($event)) $event = array($event);
        // Always trigger errors !
        $event[] = XEP::EVENT_ERROR;
        $payloads = $this->conn->processUntil($event, $this->timeout);
        foreach ($payloads as $payload) {
            if (in_array($payload[0], $event)) return $payload[1];
        }
        return false;
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

    /**
     * Object destructor : close connection
     */
    public function __destruct() {
        if ($this->conn->getPort == '5280') {
            $this->conn->saveSession();
            echo "SESSION SAVED";
        } else {
            $this->conn->disconnect();
        }
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