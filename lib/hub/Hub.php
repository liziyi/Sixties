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
 * Base Xep class
 */
require_once dirname(dirname(__FILE__)) . '/sixties/Xep.php';
require_once dirname(dirname(__FILE__)) . '/sixties/XepPubsub.php';

/**
 * Data repository
 */
require_once dirname(__FILE__) . '/HubRepo.php';

/**
 * Hub : bot for managing notifications from a pubsub server
 *
 * The bot connects to Jabber servers with many accounts and wait for Event.
 * When an account receive an event, the bot send it to the handlers the user has registered
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
class Hub extends BbBase
{
    /**
     * @var array list of current connections
     */
    protected $conns;

    /**
     * @var HubRepo data repository
     */
    protected $repo;

    /**
     * @var integer Number of seconds between each ping
     */
    protected $pingTimeout = 60;

    /**
     * Constructor
     *
     * @param HubRepo $repo the data repository to use
     *
     * @return void
     */
    public function __construct(HubRepo $repo) {
        parent::__construct();
        $this->repo  = $repo;
        $this->conns = array();
    }

    /**
     * Add a connection to the hub
     *
     * @param string  $user     user
     * @param string  $host     host
     * @param string  $password password
     * @param string  $resource ressource
     * @param string  $server   server
     * @param integer $port     port
     *
     * @return Hub this
     */
    public function addConnection($user, $host, $password = '', $resource = 'HubBot', $server = null, $port = 5222) {
        try {
            // connect
            $conn = new XMPP2($host, $port, $user, $password, $resource, $server, false, XMPPHP_Log::LEVEL_INFO);
            $conn->connect();
            $conn->processUntil('session_start');
            $conn->xep('pubsub'); // Dummy call to load pubsub handlers
            // Set a high priority to receive all messages
            $conn->presence('HubBot', 'available', null, 'invisible', 99);

            // load handlers
            $handlers = $this->_loadHandlers($user . '@' . $host);
            // save connection
            $this->conns[$conn->getBareJid()] = array('conn' => $conn, 'handlers' => $handlers);
        } catch (Exception $e) {
            $this->log("Error connecting " . $e->getMessage(), BbLogger::ERROR, 'Hub');
        }

        return $this;
    }

    /**
     * Connect all users that have registered handlers
     *
     * @return Hub this
     */
    public function loadConnections() {
        try {
            $this->log("Loading connections", BbLogger::INFO, 'hub');
            $res = $this->repo->usersGet();
            foreach ($res as $user) {
                if (!isset($this->conns[$user->jid])) {
                    // connect
                    $this->log("Connecting {$user->jid}", BbLogger::INFO, 'Hub');
                    $conn = XMPP2::quickConnect($user->jid . '/HubBot', $user->password);
                    $conn->xep('pubsub'); // Dummy call to load pubsub handlers
                    // Set a high priority to receive all messages
                    $conn->presence('HubBot', 'available', null, 'invisible', 99);
                    // save connection
                    $this->conns[$user->jid] = array('conn' => $conn, 'handlers' => array());
                }
                // load handlers
                $handlers = $this->_loadHandlers($user->jid);
                $this->conns[$user->jid]['handlers'] = $handlers;
            }
        } catch (Exception $e) {
            $this->log("Error loading connection" . $e->getMessage(), BbLogger::ERROR, 'hub');
        }
        $this->log("Loading connections successful", BbLogger::INFO, 'hub');
        return $this;
    }

    /**
     * Main hub loop : listen to connections and handle incoming events
     *
     * @param integer $timeout max hub execution time, in seconds
     *
     * @return Hub this
     */
    public function process($timeout = null) {
        try {
            $end  = ($timeout !== null ? time() + $timeout : time() + 3600);
            $ping = time() + $this->pingTimeout; // date of next ping
            // Main loop
            while (time() < $end) {
                foreach ($this->conns as $jid => $conn) {
                    try {
                        // use a 0 timeout, so it doesn't depend on the number of connections. We will sleep later
                        $events = $conn['conn']->processUntil(
                            array(
                                XepPubSub::EVENT_NOTIF_COLLECTION,
                                XepPubSub::EVENT_NOTIF_CONFIG,
                                XepPubSub::EVENT_NOTIF_DELETE,
                                XepPubSub::EVENT_NOTIF_ITEMS,
                                XepPubSub::EVENT_NOTIF_PURGE,
                                XepPubSub::EVENT_NOTIF_SUBSCRIPTION),
                            0.01 // the timeout : don't wait if there's nothing new
                        );
                        if (count($events) > 0) {
                            // new events are available
                            $this->log("New messages for $jid", BbLogger::INFO, 'hub');
                            foreach ($events as $event) {
                                try {
                                    $this->log("Message for $jid : {$event[1]->message}", BbLogger::DEBUG, 'hub');
                                    $eventType = $event[0];
                                    $payload   = simplexml_load_string($event[1]->message);
                                    if (is_object($payload)) {
                                        $node  = (string)$payload->items[0]['node'];
                                        // Search a handler for the node or an ancestor
                                        $found = isset($conn['handlers'][$node]);
                                        $pos   = strrpos($node, '/');
                                        while (!$found && $pos !== false) {
                                            $node  = substr($node, 0, $pos);
                                            $found = isset($conn['handlers'][$node]);
                                            $pos   = strrpos($node, '/');
                                        }
                                        if ($found) {
                                            foreach ($payload->items[0] as $item) {
                                                foreach ($conn['handlers'][$node] as $handler) {
                                                    // Fork and handle items in child processes
                                                    $pid = pcntl_fork();
                                                    if ($pid == -1) {
                                                        // fork unsuccess, handle the event ourself
                                                        $this->log("Hub was enable to fork", BbLogger::ERROR, 'hub');
                                                        $handler->handle($item->asXML());
                                                    } elseif ($pid == 0) {
                                                        $this->log("Child process started", BbLogger::DEBUG, 'hub');
                                                        try {
                                                            // child process : handle the event
                                                            $this->log(sprintf("Handling event with handler %d (%s)", $handler->getId(), $handler->getHandler()), BbLogger::DEBUG, 'Hub');
                                                            $handler->handle($item->asXML());
                                                            $this->log(sprintf("Handling ok with handler %d (%s)", $handler->getId(), $handler->getHandler()), BbLogger::DEBUG, 'Hub');
                                                        } catch (Exception $e) {
                                                            $this->log("Error handling event for $jid " . $e->getMessage(), BbLogger::ERROR, 'Hub');
                                                        }
                                                        $this->log("Child process exciting", BbLogger::DEBUG, 'hub');
                                                        exit;
                                                    } else {
                                                        // parent process, nothing to do
                                                    }
                                                }
                                            }
                                        } else {
                                            $this->log("No handlers for node $node for $jid", BbLogger::WARNING, 'hub');
                                        }
                                    } else {
                                        $this->log("Bad payload : " . $event[1]->message, BbLogger::ERROR, 'hub');
                                    }
                                } catch (Exception $e) {
                                    $this->log("Error handling event for $jid " . $e->getMessage(), BbLogger::ERROR, 'hub');
                                }
                            }
                        }
                    } catch (Exception $e) {
                        $this->log("Error getting notifications for $jid " . $e->getMessage(), BbLogger::ERROR, 'hub');
                    }
                }
                $conn['conn']->presence('HubBot', 'available', null, 'available', 99);
                echo '+';
                // sleep for 5000ms
                usleep(5000000);

                // if ping timeout reached, send a ping request to avoid disconnection
                if (time() > $ping) {
                    foreach ($this->conns as $jid => $conn) {
                        $this->log("Pinging $jid", BbLogger::DEBUG, 'hub');
                        $conn['conn']->xep('ping')->ping();
                        $this->log("Pinging $jid ok", BbLogger::DEBUG, 'hub');
                    }
                    $ping = time() + $this->pingTimeout;
                }
                echo '.';
            }
            // End : disconnect
            foreach ($this->conns as $jid => $conn) {
                $conn['conn']->disconnect();
            }
        } catch (Exception $e) {
            $this->log("Error in hub main loop " . $e->getMessage(), BbLogger::ERROR, 'hub');
        }
        return $this;
    }

    /**
     * Reload all handlers
     *
     * @return Hub this
     */
    /*
    public function reloadHandlers() {
        try {
            $this->log("Reloading handlers", BbLogger::INFO, 'hub');
            foreach ($this->conns as $jid => $conn) {
                // load handlers
                $handlers = $this->_loadHandlers($jid);
                // save connection
                $this->conns[$jid]['handlers'] = $handlers;
            }
        } catch (Exception $e) {
            $this->log("Error reloading handlers" . $e->getMessage(), BbLogger::ERROR, 'hub');
        }
        $this->log("Reloading handlers successful", BbLogger::INFO, 'hub');
        return $this;
    }
    */
    /**
     * Load the handlers registered by a user
     *
     * @param string $jid JID of the user
     *
     * @return array
     */
    private function _loadHandlers($jid) {
        $handlers = array();
        $res = $this->repo->handlerRead(null, $jid, null, true);
        foreach ($res as $obj) {
            try {
                if (!isset($handlers[$obj->node])) $handlers[$obj->node] = array();
                $handler = HubHandler::handlerLoad(
                    $obj->class,
                    array($obj->jid, $obj->password, $obj->node, $obj->class, $obj->params, $obj->id)
                );
                $handlers[$obj->node][] = $handler;
            } catch (Exception $e) {
                $this->log("Error loading for {$obj->jid} on {$jis->node} : " . $e->getMessage(), BbLogger::ERROR, 'hub');
            }
        }

        return $handlers;
    }
}