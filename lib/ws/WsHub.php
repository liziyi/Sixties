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
 * XMPP class for auth
 */
require_once dirname(__FILE__) . '/../sixties/XMPP2.php';

/**
 * Require Hub repository class
 */
require_once dirname(dirname(__FILE__)) . '/hub/HubRepo.php';

/**
 * WsHub : Base class for the interfaces with Hub API
 *
 * This service use a dummy connection to Jabber server to check user auth
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
class WsHub extends WsService
{
    /**
     * @var XMPP2 the current connection
     */
    protected $conn;


    protected $params;

    /**
     * Constructor : connect to the Repository
     *
     * @param array $params connexion parameters
     *
     * @return void
     */
    public function __construct($params) {
        parent::__construct($params);
        try {
            // Dummy connection to check user auth
            $jid  = $this->params['user'] . '@' . $this->params['host'];
            $conn = XMPP2::quickConnect($jid . '/HubBot', $this->params['password']);
            $conn->disconnect();
            $this->repo = new HubRepo($params['db_dsn'], $params['db_user'], $params['db_password']);
        } catch (XMPPHP_Exception $e) {
            $this->log($e->getMessage(), BbLogger::FATAL, 'WsXep');
            throw new WsException($e->getMessage(), WsResponse::INTERNAL);
        }
    }

    /**
     * Object destructor : close connection
     */
    public function __destruct() {
        $this->repo = null;
    }


    /**
     * Get handlers
     *
     * Parameters:
     * - id to get a specific handler
     * - jid to get all handlers of a user
     * - jid and node to get all handlers of a user on a node
     *
     * @param array $params parameters
     *
     * @return WsResponse
     */
    public function handlerGet($params) {
        try {
            $jid = $this->params['user'] . '@' . $this->params['host'];
            $res = $this->repo->handlerRead($params['id'], $jid, $params['node']);
            return new WsResponse($res, WsResponse::OK);
        } catch (WsException $e) {
            return new WsResponse($e->getMessage(), $e->getCode());
        } catch (Exception $e) {
            return new WsResponse($e->getMessage(), WsResponse::KO);
        }
    }
    /**
     * Create handlers for the current connected user
     *
     * Parameters:
     * - node   (required)
     * - class  (required)
     * - form   (required) the parameters
     *
     * @param array $params parameters
     *
     * @return WsResponse
     */
    public function handlerPost($params) {
        $this->checkparams(array('node', 'class', 'form'), $params);
        try {
            $jid     = $this->params['user'] . '@' . $this->params['host'];
            $handler = HubHandler::handlerLoad($params['class'], array($jid, $this->params['password'], $params['node'], $params['class'], $params['form']));
            $this->repo->handlerCreate($handler);
            return new WsResponse('', WsResponse::OK);
        } catch (WsException $e) {
            return new WsResponse($e->getMessage(), $e->getCode());
        } catch (Exception $e) {
            return new WsResponse($e->getMessage(), WsResponse::KO);
        }
    }
    /**
     * Update handler
     *
     * Parameters:
     * - id     (required)
     * - node   (required)
     * - class  (required)
     * - params (required)
     *
     * @param array $params parameters
     *
     * @return WsResponse
     */
    public function handlerPut($params) {
        $this->checkparams(array('id', 'node', 'class', 'form'), $params);
        try {
            $jid     = $this->params['user'] . '@' . $this->params['host'];
            $handler = HubHandler::handlerLoad($params['class'], array($jid, $this->params['password'], $params['node'], $params['class'], $params['form'], $params['id']));
            $this->repo->handlerUpdate($handler);
            return new WsResponse('', WsResponse::OK);
        } catch (WsException $e) {
            return new WsResponse($e->getMessage(), $e->getCode());
        } catch (Exception $e) {
            return new WsResponse($e->getMessage(), WsResponse::KO);
        }
    }
    /**
     * Delete handler
     *
     * Parameters:
     * - id (required)
     *
     * @param array $params parameters
     *
     * @return WsResponse
     */
    public function handlerDelete($params) {
        $this->checkparams(array('id'), $params);
        try {
            // It's just for deletion, we don't need to load a real handler
            $jid     = $this->params['user'] . '@' . $this->params['host'];
            $handler = new HubHandler($jid, null, null, null, null, $params['id']);
            $this->repo->handlerDelete($handler);
            return new WsResponse('', WsResponse::OK);
        } catch (WsException $e) {
            return new WsResponse($e->getMessage(), $e->getCode());
        } catch (Exception $e) {
            return new WsResponse($e->getMessage(), WsResponse::KO);
        }
    }

    /**
     * Get the form to edit a handler
     *
     * id or class are required
     * Parameters:
     * - id
     * - class
     *
     * @param array $params parameters
     *
     * @return WsResponse
     */
    public function formGet($params) {
        try {
            // id or class
            if (empty($params['id']) && empty($params['class'])) {
                $this->log("Invalid parameters", BbLogger::FATAL, 'WsXep');
                throw new WsException("Missing parameter id or class", WsResponse::BAD_REQUEST);
            }
            if (empty($params['id'])) {
                // Request new form
                $handler = HubHandler::handlerLoad($params['class'], array('', '', '', $params['class'], null));
            } else {
                // Load handler
                $res = $this->repo->handlerRead($params['id']);
                if (count($res) != 1) {
                    $this->log("wrong number of handlers for {$params['id']} : " . count($res), BbLogger::FATAL, 'WsXep');
                    throw new WsException("No such handler", WsResponse::NOT_FOUND);
                }
                $handler = HubHandler::handlerLoad($res[0]->class, array($res[0]->jid, $res[0]->password, $res[0]->node, $res[0]->class, $res[0]->params, $res[0]->id));
            }
            if ($handler) return new WsResponse($handler->formLoad(), WsResponse::OK);
            else return new WsResponse('Unable to load handler', WsResponse::KO);
        } catch (WsException $e) {
            return new WsResponse($e->getMessage(), $e->getCode());
        } catch (Exception $e) {
            return new WsResponse($e->getMessage(), WsResponse::KO);
        }
    }

    /**
     * Get the list of available handlers classes
     *
     * Parameters: none
     *
     * @param array $params parameters
     *
     * @return WsResponse
     */
    public function classGet($params) {
        return new WsResponse(HubHandler::handlersGet(), WsResponse::OK);
    }
}
