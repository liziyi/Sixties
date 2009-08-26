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
 * @subpackage Hub
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @link       https://labo.clochix.net/projects/show/sixties
 */

/**
 * Include base handler class
 */
require_once 'HubHandler.php';

/**
 * HubRepo : Base class for all repositories
 *
 * @category   Library
 * @package    Sixties
 * @subpackage Hub
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */
class HubRepo extends BbBase
{
    /**
     * @var PDO $dbh the PDO database handler
     */
    protected $dbh;

    /**
     * Constructor
     *
     * @param string $dsn      the database DSN
     * @param string $user     database user
     * @param string $password database password
     */
    public function __construct($dsn, $user = null, $password = null) {
        parent::__construct();
        // Connect
        try {
            $this->dbh = new PDO($dsn, $user, $password);
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception $e) {
            $this->log("Error connecting to repository : " . $e->getMessage(), BbLogger::ERROR, 'HubRepo');
        }
    }

    /**
     * Destructor
     *
     * @return void
     */
    public function __destruct() {
        $this->dbh = null;
    }

    /**
     * Insert a new handler into the repository
     *
     * @param HubHandler $handler the handler
     *
     * @return HubRepo this
     */
    public function handlerCreate(HubHandler $handler) {
        try {
            $stmt = $this->dbh->prepare("INSERT INTO HANDLERS (jid, password, node, class, params) VALUES (:jid, :password, :node, :class, :params)");
            $stmt->bindValue(':jid', $handler->getJid(), PDO::PARAM_STR);
            $stmt->bindValue(':password', $handler->getPassword(), PDO::PARAM_STR);
            $stmt->bindValue(':node', $handler->getNode(), PDO::PARAM_STR);
            $stmt->bindValue(':class', $handler->getHandler(), PDO::PARAM_STR);
            $stmt->bindValue(':params', serialize($handler->getParams()), PDO::PARAM_STR);
            $stmt->execute();
        } catch (Exception $e) {
            $this->log("Error creating handler : " . $e->getMessage(), BbLogger::ERROR, 'HubRepo');
            throw new Exception("Internal error when creating handler");
        }
        return $this;
    }
    /**
     * Get the handlers for a user and a node
     *
     * If id is null, return all handlers of a user (for a node if node is not null)
     * If id and jid are null, return all handlers
     *
     * @param integer $id       internal handler id
     * @param string  $jid      JID (mandatory)
     * @param string  $node     node name
     * @param boolean $password true to include password
     *
     * @return array of {@link HubHandler}
     */
    public function handlerRead($id = null, $jid = null, $node = null, $password = false) {
        try {
            $res = array();
            $req = 'SELECT id, jid, password, node, class, params FROM HANDLERS ';
            if ($id !== null) {
                $req .= ' WHERE id = :id';
                // security : be sure to only get handlers of this user
                if ($jid != null) $req .= ' AND jid = :jid';
                $stmt = $this->dbh->prepare($req);
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                if ($jid != null) $stmt->bindValue(':jid', $id, PDO::PARAM_STR);
            } elseif ($jid != null) {
                $req .= ' WHERE jid = :jid';
                if ($node !== null) $req .= ' AND node = :node';
                $stmt = $this->dbh->prepare($req);
                $stmt->bindValue(':jid', $jid, PDO::PARAM_STR);
                if ($node !== null) $stmt->bindValue(':node', $node, PDO::PARAM_STR);
            } else {
                $stmt = $this->dbh->prepare($req);
            }
            if ($stmt->execute()) {
                while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                    $row->params = unserialize($row->params);
                    if (!$password) $row->password = null;
                    $res[] = $row;
                }
            }
        } catch (Exception $e) {
            $this->log("Error reading handler : " . $e->getMessage(), BbLogger::ERROR, 'HubRepo');
            throw new Exception("Internal error when reading handler");
        }
        return $res;
    }
    /**
     * Update a handler into the repository
     *
     * @param HubHandler $handler the handler
     *
     * @return HubRepo this
     */
    public function handlerUpdate(HubHandler $handler) {
        try {
            $req = 'UPDATE HANDLERS SET class = :class, params = :params WHERE id = :id';
            if ($handler->getJid() != null) $req .= ' AND jid = :jid';
            $stmt = $this->dbh->prepare($req);
            $stmt->bindValue(':id', $handler->getId(), PDO::PARAM_INT);
            $stmt->bindValue(':class', $handler->getHandler(), PDO::PARAM_STR);
            $stmt->bindValue(':params', serialize($handler->getParams()), PDO::PARAM_STR);
            if ($handler->getJid() != null) $stmt->bindValue(':jid', $handler->getJid(), PDO::PARAM_STR);
            $stmt->execute();
        } catch (Exception $e) {
            $this->log("Error updating handler : " . $e->getMessage(), BbLogger::ERROR, 'HubRepo');
            throw new Exception("Internal error when updating handler");
        }
        return $this;
    }
    /**
     * Delete handler from the repository
     *
     * @param HubHandler $handler the handler
     *
     * @return HubRepo this
     */
    public function handlerDelete(HubHandler $handler) {
        try {
            $req = 'DELETE FROM HANDLERS WHERE id = :id';
            if ($handler->getJid() != null) $req .= ' AND jid = :jid';
            $stmt = $this->dbh->prepare($req);
            $stmt->bindValue(':id', $handler->getId(), PDO::PARAM_INT);
            if ($handler->getJid() != null) $stmt->bindValue(':jid', $handler->getJid(), PDO::PARAM_STR);
            $stmt->execute();
        } catch (Exception $e) {
            $this->log("Error deleting handler : " . $e->getMessage(), BbLogger::ERROR, 'HubRepo');
            throw new Exception("Internal error when deleting handler");
        }
        return $this;
    }

    /**
     * Get the list of user with registered handlers
     *
     * @return array of objects
     */
    public function usersGet() {
        try {
            $res  = array();
            $req  = 'SELECT distinct jid, password FROM HANDLERS';
            $stmt = $this->dbh->prepare($req);
            if ($stmt->execute()) {
                while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                    $res[] = $row;
                }
            }
        } catch (Exception $e) {
            $this->log("Error reading users : " . $e->getMessage(), BbLogger::ERROR, 'HubRepo');
            throw new Exception("Internal error when reading handler");
        }
        return $res;
    }
}