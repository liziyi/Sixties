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

/**
 * Require bas WsXep class
 */
require_once 'WsXep.php';

/**
 * wsXepDisco : Interface with the Discovery Module
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
class WsXepDisco extends WsXep
{

    /**
     * Get items
     *
     * Parameters:
     * - server (optionnal)
     * - node (optional)
     *
     * @param array $params parameters
     *
     * @return XepResponse
     *
     * @UrlMap({'server', 'node'})
     */
    public function itemsGet($params) {
        $this->conn->xep('discover')->discoverItems($params['server'], $params['node']);
        return $this->process('discover_items_handled');
    }

    /**
     * Get information on a server / node
     *
     * Parameters:
     * - server (optionnal)
     * - node (optional)
     *
     * @param array $params parameters
     *
     * @return XepResponse
     *
     * @UrlMap({'server', 'node'})
     */
    public function infoGet($params) {
        $this->conn->xep('discover')->discoverInfo($params['server'], $params['node']);
        return $this->process('discover_info_handled');
    }

    /**
     * Get full service tree
     *
     * Parameters:
     * - server (optionnal)
     * - node (optional)
     *
     * @param array $params parameters
     *
     * @return XepResponse
     *
     * @UrlMap({'server', 'node'})
     */
    public function servicesGet($params) {
        return($this->_servicesGet($params['server'], $params['node']));
    }

    /**
     * Private method : recursively fetch info and sub-items
     *
     * @param string $server the server
     * @param string $node   the node name
     *
     * @return XepResponse
     */
    private function _servicesGet($server = null, $node = null) {
        // Get items
        $this->conn->xep('discover')->discoverItems($server, $node);
        $items = $this->process('discover_items_handled');
        // Get info
        $this->conn->xep('discover')->discoverInfo($server, $node);
        $infos    = $this->process('discover_info_handled');
        // Merge
        $msgItems = ($items->code == 200 ? $items->message : array());
        $msgInfo  = ($infos->code == 200 ? $infos->message : array());
        $res = new XepResponse(array_merge_recursive($msgItems, $msgInfo));
        // recurse on all children
        foreach ($res->message as $host => $hostval) {
            if (is_array($hostval['items']) && is_array($hostval['items'])) {
                foreach ($hostval['items'] as $itemkey => $itemval) {
                    $tmp = explode('!', $itemkey);
                    $childServer = $tmp[0];
                    if (count($tmp) < 3) {
                        $childNode = $tmp[1];
                    } else {
                        unset($tmp[0]);
                        $childNode = implode('!', $tmp);
                    }
                    // Recurse only if current node is null (root of the server) or node name of the child is not null.
                    if (!empty($itemval['node']) || empty($node)) {
                        $child = $this->_servicesGet($childServer, $childNode);
                        if ($child->code == 200) {
                            ksort($res->message[$host]['items']);
                            $res->message[$host]['items'] = array_merge_recursive($res->message[$host]['items'], $child->message);
                        }
                    }
                }
            }
        }
        return $res;
    }
}