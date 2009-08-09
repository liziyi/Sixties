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
 * wsXep : Base class for the interfaces with Xep API
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
class WsXep
{
    /**
     * @var XMPP2 the current connection
     */
    protected $conn;

    /**
     * Constructor : connect to the XMPP server
     *
     * @param array $params connexion parameters
     */
    public function __construct($params) {
        $this->conn = new XMPP2($params['host'], $params['port'], $params['user'], $params['password'], uniqid(get_class($this)), $params['server'], false, XMPPHP_Log::LEVEL_INFO);
        $this->conn->connect();
        $this->conn->processUntil('session_start');
    }

    /**
     * Get the list of all available actions for a module
     *
     * @return XepResponse
     */
    public function Options() {
        $methods = get_class_methods($this);
        $result  = array();
        // Get all methods whose name is of the form actionMethod
        foreach ($methods as $name) {
            if (preg_match('/([a-z]+)([A-Z]{1}[a-z]+)/', $name, $matches) == 1) {
                if (!isset($result[$matches[1]])) {
                    $result[$matches[1]] = array($matches[2]);
                } else {
                    $result[$matches[1]][] = $matches[2];
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
        $payloads = $this->conn->processUntil($event);
        foreach ($payloads as $payload) {
            if ($payload[0] == $event) return $payload[1];
        }
        return false;
    }

}