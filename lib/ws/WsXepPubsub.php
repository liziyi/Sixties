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
require_once 'WsXep.php';

/**
 * wsXepPubsub : Interface with the PubSub Module
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
class WsXepPubsub extends WsXep
{
    /**
     * Get the affiliations of the user
     *
     * Without node, return all affiliations of the owner
     * With a node, return all affiliations on this node
     *
     * Parameters:
     * - node (optional)
     *
     * @param array $params parameters
     *
     * @return XepResponse
     *
     * @UrlMap('node')
     */
    public function affiliationGet($params) {
        $this->conn->xep('pubsub')->affiliationGet($params['node']);
        return $this->process(XepPubsub::EVENT_AFFILIATIONS);
    }
    /**
     * Set the affiliations of the user
     *
     * Parameters:
     * - node
     * - jid
     * - affiliation
     *
     * @param array $params parameters
     *
     * @return XepResponse
     */
    public function affiliationPut($params) {
        $this->checkparams(array('node', 'jid', 'affiliation'), $params);
        $this->conn->xep('pubsub')->affiliationSet($params['node'], $params['jid'], $params['affiliation']);
        return $this->process(XepPubsub::EVENT_AFFILIATIONS);
    }

    /**
     * Create a new collection
     *
     * @param array $params parameters
     *
     * @return XepResponse
     */
    public function collectionPost($params) {
    }

    /**
     * Create a new leaf
     *
     * @param array $params parameters
     *
     * @return XepResponse
     */
    public function leafPost($params) {
    }

    /**
     * Update node configuration
     *
     * Parameters:
     * - node (mandatory)
     * - form (mandatory)
     * - server (optionnal)
     *
     * @param array $params parameters
     *
     * @return XepResponse
     */
    public function nodePut($params) {
        $this->checkparams(array('node', 'form'), $params);
        $this->conn->xep('pubsub')->nodeConfiguration($params['node'], $params['form'], false, $params['server']);
        return $this->process(Xep::EVENT_OK);
    }

    /**
     * Get node configuration
     *
     * Without node, return the default node config
     *
     * Parameters:
     * - node (optionnal)
     * - server (optionnal)
     *
     * @param array $params parameters
     *
     * @return XepResponse
     *
     * @UrlMap({'node', 'server'})
     */
    public function nodeOptions($params) {
        if ($params['node'] == null) {
            $this->conn->xep('pubsub')->nodeConfigurationDefault($params['server']);
            return $this->process(XepPubsub::EVENT_NODEDEFAULTCONFIG);
        } else {
            $this->conn->xep('pubsub')->nodeConfiguration($params['node'], null, false, $params['server']);
            return $this->process(XepPubsub::EVENT_NODECONFIG);
        }
    }
}