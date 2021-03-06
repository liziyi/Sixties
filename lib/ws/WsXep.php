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
 * @category   Library
 * @package    Sixties
 * @subpackage WebService
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */

/**
 * Require base web services class
 */
require_once "WsService.php";

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
class WsXep extends WsService
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
     *
     * @return void
     */
    public function __construct($params) {
        parent::__construct($params);
        try {
            // set default timeout : 5s
            $this->timeout = 5;
            if (is_array($params)) {
                switch ($params['port']) {
                case '5280':
                    include_once dirname(__FILE__) . '/../sixties/XMPP_BOSH.php';
                    session_start();
                    $this->conn = new XMPPHP_BOSH($params['host'], $params['port'], $params['user'], $params['password'], uniqid(get_class($this)), $params['server'], false, XMPPHP_Log::LEVEL_INFO);
                    // Use XMPP over BOSH
                    $this->conn->connect("http://{$params['host']}:{$params['port']}/http-bind", 1, true);
                    // Pinging the server is always a good idea ;)
                    $this->conn->xep('ping')->ping();
                    $res = $this->process(XepPing::EVENT_PONG);
                    //@TODO : if it still don't work, do as in BbRest : if 503, go to sleep and try later
                    break;
                default:
                    include_once dirname(__FILE__) . '/../sixties/XMPP2.php';
                    $this->conn = new XMPP2($params['host'], $params['port'], $params['user'], $params['password'], uniqid(get_class($this)), $params['server'], false, XMPPHP_Log::LEVEL_INFO);
                    $this->conn->connect();
                    $this->conn->processUntil('session_start', 10);
                    break;
                }
                $this->conn->logXml = true;
            } else {
                $this->log("No parameters given, unable to connect", BbLogger::FATAL, 'WsXep');
                throw new WsException("No parameters given, unable to connect", WsResponse::INTERNAL);
            }
        } catch (XMPPHP_Exception $e) {
            $this->log($e->getMessage(), BbLogger::FATAL, 'WsXep');
            throw new WsException($e->getMessage(), WsResponse::INTERNAL);
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
     * Wait for an event and return the result
     *
     * @param string  $event   the event name
     * @param integer $timeout time to wait
     *
     * @return mixed
     */
    protected function process($event, $timeout = null) {
        if (!$timeout) $timeout = $this->timeout;
        if (!is_array($event)) $event = array($event);
        // Always trigger errors !
        $event[] = XEP::EVENT_ERROR;
        $payloads = $this->conn->processUntil($event, $timeout);
        foreach ($payloads as $payload) {
            if (in_array($payload[0], $event)) return $payload[1];
        }
        $message = '';
        if (is_array($payloads)) {
            if (count($payloads) == 0) {
                $message = 'Timeout before answer';
            } else {
                $message = 'Event never happens';
            }
        } else {
            $message = 'Unknown error';
        }
        return new WsResponse($message, WsResponse::KO);
    }

    /**
     * Create a XepForm from incoming datas
     *
     * @param array $data the datas
     *
     * @return XepForm
     */
    protected function formLoad($data) {
        if ($data) {
            $form = new XepForm();
            foreach ($data as $k => $v) $form->addField(new XepFormField($k, $v));
            return $form;
        } else return null;
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
