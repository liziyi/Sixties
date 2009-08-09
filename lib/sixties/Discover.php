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

    /**
     * @const event on info result
     */
    const EVENT_INFO  = 'discover_info_handled';
    /**
     * @const event on items result
     */
    const EVENT_ITEMS = 'discover_items_handled';

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
     * Send a discover items request
     *
     * @param string $server to get items on a specific server
     * @param string $node   to get items on a specific node
     *
     * @return XepDiscover $this
     */
    public function discoverItems($server = null, $node = null) {
        if (is_null($server)) $server = $this->conn->getHost();
        if (!is_null($node)) $node = "node='$node'";
        $this->conn->sendIq(array('to' => $server,'msg'=>"<query xmlns='http://jabber.org/protocol/disco#items' $node />"));
        return $this;
    }

    /**
     * Handle items response
     *
     * @param XMPPHP_XMLObj $xml the result
     *
     * @return void
     */
    public function handlerItems(XMPPHP_XMLObj $xml) {
        try {
            $res = $this->commonHandler($xml);
            if ($res->code != XepResponse::XEPRESPONSE_KO) {
                $query   = $xml->sub('query');
                $node    = $xml->attrs['from'] . '!' . $query->attrs['node'];
                $items   = array($node => array('items'=>array()));
                foreach ($query->subs as $sub) {
                    if ($sub->name == 'item') {
                        $items[$node]['items'][$sub->attrs['jid'] . '!' . $sub->attrs['node']] = array(
                            'jid'  => $sub->attrs['jid'],
                            'name' => $sub->attrs['name'],
                            'node' => $sub->attrs['node']);
                    }
                }
                $res->message = $items;
            }
        } catch (Exception $e) {
            $res = new XepResponse($e->getMessage(), XepResponse::XEPRESPONSE_KO);
        }
        $this->conn->event(self::EVENT_ITEMS, $res);
    }

    /**
     * Handle infos response
     *
     * @param XMPPHP_XMLObj $xml the result
     *
     * @return void
     */
    public function handlerInfo(XMPPHP_XMLObj $xml) {
        try {
            $res = $this->commonHandler($xml);
            if ($res->code != XepResponse::XEPRESPONSE_KO) {
                $query   = $xml->sub('query');
                $node    = $xml->attrs['from'] . '!' . $query->attrs['node'];
                $message = array($node => array('identities'=>array(), 'features'=>array()));
                foreach ($query->subs as $sub) {
                    if ($sub->name == 'identity') {
                        $message[$node]['identities'][] = array(
                            'category' => $sub->attrs['category'],
                            'name'     => $sub->attrs['name'],
                            'type'     => $sub->attrs['type']
                            );
                    }
                    if ($sub->name == 'feature') {
                        $message[$node]['features'][$sub->attrs['var']] = $sub->attrs['var'];
                    }
                }
                $res->message = $message;
            }
        } catch (Exception $e) {
            $res = new XepResponse($e->getMessage(), XepResponse::XEPRESPONSE_KO);
        }
        $this->conn->event(self::EVENT_INFO, $res);
    }
}
