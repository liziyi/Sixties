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
require_once dirname(dirname(__FILE__)) . "/sixties/Xep.php";
require_once dirname(dirname(__FILE__)) . "/sixties/XepPubsub.php";

/**
 * Data repository
 */
require_once dirname(__FILE__) . "/HubRepo.php";

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
class Hub
{
    /**
     * List of current connections
     *
     * @access protected
     * @var    array
     */
    protected $conns;

    /**
     * Data repository
     *
     * @access protected
     * @var    repo
     */
    protected $repo;

    /**
     * Constructor
     *
     * @param HubRepo $repo the data repository to use
     *
     * @return void
     */
    public function __construct(HubRepo $repo) {
        $this->repo = $repo;
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
            $conn->presence('BubBot', 'available', null, 'invisible', 99);

            // load handlers
            $handlers = array();
            $res = $this->repo->handlerRead(null, $user . '@' . $host);
            foreach ($res as $obj) {
                if (!isset($handlers[$obj->node])) $handlers[$obj->node] = array();
                $handlers[$obj->node][] = HubHandler::handlerLoad(
                    $obj->class,
                    array($obj->jid, $obj->node, $obj->class, $obj->params, $obj->id)
                );
            }
            // save connection
            $this->conns[$conn->getBareJid()] = array('conn' => $conn, 'handlers' => $handlers);
        } catch (Exception $e) {
            $this->log($e->getMessage());
        }

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
            $ping = time() +60; // date of next ping
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
                            var_dump($jid, $events);
                            foreach ($events as $event) {
                                try {
                                    $eventType = $event[0];
                                    $payload   = simplexml_load_string($event[1]->message);
                                    $node      = (string)$payload->items[0]['node'];
                                    if (isset($conn['handlers'][$node])) {
                                        foreach ($payload->items[0] as $item) {
                                            foreach ($conn['handlers'][$node] as $handler) {
                                                $handler->handle($item->asXML());
                                            }
                                        }
                                    } else {
                                        echo "No handlers for node $node for $jid\n";
                                    }
                                } catch (Exception $e) {
                                    // @TODO
                                    var_dump($e->getMessage());
                                }
                            }
                        }
                    } catch (Exception $e) {
                        // @TODO
                        var_dump($e->getMessage());
                    }
                }
                // sleep for 5000ms
                usleep(5000000);
                if (time() > $ping) {
                    foreach ($this->conns as $jid => $conn) {
                        $conn['conn']->xep('ping')->ping();
                    }
                    $ping = time() +60;
                }
                echo '.';
            }
            // End : disconnect
            foreach ($this->conns as $jid => $conn) {
                $conn['conn']->disconnect();
            }
        } catch (Exception $e) {
            //@TODO
            var_dump($e->getMessage());
        }
        return $this;
    }

    /**
     * Reload all handlers
     *
     * @return Hub this
     */
    public function reloadHandlers() {
        echo "Reloading handlers ";
        foreach ($this->conns as $jid => $conn) {
            // load handlers
            $handlers = array();
            $res = $this->repo->handlerRead(null, $jid);
            foreach ($res as $obj) {
                if (!isset($handlers[$obj->node])) $handlers[$obj->node] = array();
                $handlers[$obj->node][] = HubHandler::handlerLoad(
                    $obj->class,
                    array($obj->jid, $obj->node, $obj->class, $obj->params, $obj->id)
                );
                echo '.';
            }
            // save connection
            $this->conns[$jid]['handlers'] = $handlers;
        }
        echo "done\n";
        return $this;
    }

    /**
     * Log a message
     *
     * @param string $message the message to log
     *
     * @return void;
     */
    protected function log($message) {
        //@TODO : implement logger !
        echo "$message\n";
    }
}