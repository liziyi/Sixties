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
 * Require Hub repository class
 */
require_once dirname(dirname(__FILE__)) . '/hub/HubRepo.php';

/**
 * WsHub : Base class for the interfaces with Hub API
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
     */
    public function __construct($params) {
        $this->repo = new HubRepo($params['db_dsn'], $params['db_user'], $params['db_password']);
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
            // id or jid must be present
            if (empty($params['id']) && empty($params['jid'])) {
                throw new WsException("Missing parameter id or jid", 400);
            }

            return $this->repo->handlerRead($params['id'], $params['jid'], $params['node']);
        } catch (Exception $e) {
            return new WsResponse($e->getMessage(), WsResponse::WS_RESPONSE_KO);
        }
    }
    /**
     * Create handlers
     *
     * Parameters:
     * - jid    (required)
     * - node   (required)
     * - class  (required)
     * - params (required)
     *
     * @param array $params parameters
     *
     * @return WsResponse
     */
    public function handlerPost($params) {
        $this->checkparams(array('jid', 'node', 'class', 'params'), $params);
        try {
            $handler = HubHandler::handlerLoad($params['class'], array($params['jid'], $params['node'], $params['class'], $params['params']));
            $this->repo->handlerCreate($handler);
            return new WsResponse('', WsResponse::WS_RESPONSE_OK);
        } catch (Exception $e) {
            return new WsResponse($e->getMessage(), WsResponse::WS_RESPONSE_KO);
        }
    }
    /**
     * Update handler
     *
     * Parameters:
     * - id     (required)
     * - jid    (required)
     * - node   (required)
     * - class  (required)
     * - params (required)
     *
     * @param array $params parameters
     *
     * @return WsResponse
     */
    public function handlerPut($params) {
        $this->checkparams(array('id', 'jid', 'node', 'class', 'params'), $params);
        try {
            $handler = HubHandler::handlerLoad($params['class'], array($params['jid'], $params['node'], $params['class'], $params['params'], $params['id']));
            $this->repo->handlerUpdate($handler);
            return new WsResponse('', WsResponse::WS_RESPONSE_OK);
        } catch (Exception $e) {
            return new WsResponse($e->getMessage(), WsResponse::WS_RESPONSE_KO);
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
            $handler = new HubHandler($params['jid'], $params['node'], $params['class'], $params['params'], $params['id']);
            $this->repo->handlerDelete($handler);
            return new WsResponse('', WsResponse::WS_RESPONSE_OK);
        } catch (Exception $e) {
            return new WsResponse($e->getMessage(), WsResponse::WS_RESPONSE_KO);
        }
    }

}
