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
class HubRepo
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
        // Connect
        try {
            $this->dbh = new PDO($dsn, $user, $password);
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            //@TODO
            var_dump($e->getMessage());
        } catch (Exception $e) {
            //@TODO
            var_dump($e->getMessage());
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
            $stmt = $this->dbh->prepare("INSERT INTO HANDLERS (jid, node, class, params) VALUES (:jid, :node, :class, :params)");
            $stmt->bindValue(':jid', $handler->getJid(), PDO::PARAM_STR);
            $stmt->bindValue(':node', $handler->getNode(), PDO::PARAM_STR);
            $stmt->bindValue(':class', $handler->getHandler(), PDO::PARAM_STR);
            $stmt->bindValue(':params', serialize($handler->getParams()), PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            //@TODO
            var_dump($e->getMessage());
        } catch (Exception $e) {
            //@TODO
            var_dump($e->getMessage());
        }
        return $this;
    }
    /**
     * Get the handlers for a user and a node
     *
     * If id is null, return all handlers of a user (for a node if node is not null)
     * If id and jid are null, return all handlers
     *
     * @param integer $id   internal handler id
     * @param string  $jid  JID (mandatory)
     * @param string  $node node name
     *
     * @return array of {@link HubHandler}
     */
    public function handlerRead($id = null, $jid = null, $node = null) {
        try {
            $res = array();
            $req = 'SELECT id, jid, node, class, params FROM HANDLERS ';
            if ($id !== null) {
                $req .= ' WHERE id = :id';
                $stmt = $this->dbh->prepare($req);
                $stmt->bindValue(':id', $jid, PDO::PARAM_INT);
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
                    $res[] = $row;
                }
            }
        } catch (PDOException $e) {
            //@TODO
            var_dump($e->getMessage());
        } catch (Exception $e) {
            //@TODO
            var_dump($e->getMessage());
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
            $stmt = $this->dbh->prepare("UPDATE HANDLERS SET class = :class, params = :params WHERE id = :id");
            $stmt->bindValue(':id', $handler->getId(), PDO::PARAM_INT);
            $stmt->bindValue(':class', $handler->getHandler(), PDO::PARAM_STR);
            $stmt->bindValue(':params', serialize($handler->getParams()), PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            //@TODO
            var_dump($e->getMessage());
        } catch (Exception $e) {
            //@TODO
            var_dump($e->getMessage());
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
            $stmt = $this->dbh->prepare("DELETE FROM HANDLERS WHERE id = :id");
            $stmt->bindValue(':id', $handler->getId(), PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            //@TODO
            var_dump($e->getMessage());
        } catch (Exception $e) {
            //@TODO
            var_dump($e->getMessage());
        }
        return $this;
    }
}