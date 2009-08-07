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

require_once dirname(__FILE__) . "/Xep.php";

/**
 * XepDiscover : implement client-side XEP 0030 : service discovery
 *
 * @category  Library
 * @package   Sixties
 * @author    Clochix <clochix@clochix.net>
 * @copyright 2009 Clochix.net
 * @license   http://www.gnu.org/licenses/gpl.txt GPL
 * @version   $Id$
 * @link      https://labo.clochix.net/projects/show/sixties
 */
class XepDiscover extends Xep
{
    public $items;
    public $infos;

    /**
     * Create object and register handlers
     *
     * @param XMPP2 $conn the connexion
     *
     * @return void
     */
    public function __construct($conn) {
        parent::__construct($conn);
        $this->conn->addXPathHandler('iq/{http://jabber.org/protocol/disco#items}query', 'handlerItems', $this);
        $this->conn->addXPathHandler('iq/{http://jabber.org/protocol/disco#info}query', 'handlerInfo', $this);
    }

    /**
     * Send a discover items request
     *
     * @param string $server to get items on a specific server
     * @param string $node   to get items on a specific node
     *
     * @return XepDiscover $this
     */
    public function discoverItems($server=null, $node = null) {
        if (is_null($server)) $server = $this->conn->getHost();
        if (!is_null($node)) $node = "node='$node'";
        $this->conn->sendIq(array('to' => $server,'msg'=>"<query xmlns='http://jabber.org/protocol/disco#items' $node />"));
        return $this;
    }

    /**
     * Add an item to internal item list
     *
     * @param object $item   the current item
     * @param array  &$items the array of items to update
     *
     * @return void
     */
    private function _handleQuery($item, &$items) {
        $jid = $item->attrs['jid'];
        if (!isset($items[$jid])) {
            $items[$jid] = array('nodes' => array());
        }
        if ($item->attrs['node']) {
            $items[$jid]['nodes'][$item->attrs['node']] = $item->attrs;
        }
    }

    /**
     * Handle items response
     *
     * @param XMPPHP_XMLObj $xml the result
     *
     * @return void
     */
    public function handlerItems(XMPPHP_XMLObj $xml) {
        $this->conn->history($xml);
        try {
            $status = "result";
            $xmlitems = $xml->sub('query');

            // node level
            if ($xmlitems->attrs['node']) {
                $root = $xmlitems->attrs['from'];
                $node = $xmlitems->attrs['node'];
                foreach ($xmlitems->subs as $item) {
                    if ($item->name == 'item') {
                        $this->_handleQuery($item, $this->items[$root]['nodes'][$node]);
                    } else {
                        $status = "error";
                    }
                }
            } else {
                foreach ($xmlitems->subs as $item) {
                    if ($item->name == 'item') {
                        $this->_handleQuery($item, $this->items);
                    } else {
                        $status = "error";
                    }
                }
            }
            if ($status == "result") {
                $this->log("Ok getting items", XMPPHP_Log::LEVEL_DEBUG);
            } else {
                $this->log("Error getting items", XMPPHP_Log::LEVEL_WARNING);
            }
        } catch (Exception $e) {
            $this->log("Error handling items : " . $e->getMessage(), XMPPHP_Log::LEVEL_ERROR);
        }
        $this->conn->event('discover_items_handled');
    }

    /**
     * Send a discover info request
     *
     * @param string $server to get items on a specific server
     * @param string $node   to get items on a specific node
     *
     * @return XepDiscover $this
     */
    public function discoverInfo($server = null, $node = null) {
        if (is_null($server)) $server = $this->conn->getHost();
        if (!is_null($node)) $node = "node='$node'";
        $this->conn->sendIq(array('to'=>$server, 'msg'=>"<query xmlns='http://jabber.org/protocol/disco#info' $node />"));
        return $this;
    }
    /**
     * Handle infos response
     *
     * @param XMPPHP_XMLObj $xml the result
     *
     * @return void
     */
    public function handlerInfo(XMPPHP_XMLObj $xml) {
        $this->conn->history($xml);
        //@TODO implement this !
    }
}
