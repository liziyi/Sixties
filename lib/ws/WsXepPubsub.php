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
     * Create a new node
     *
     * If node is null, will try to create an instant node
     *
     * Parameters:
     * - server (required)
     * - type (optional) : 'collection' or 'leaf'
     * - node (optional)
     *
     * @param array $params parameters
     *
     * @return XepResponse
     */
    public function nodePost($params) {
        $this->checkparams(array('node'), $params);
        $this->conn->xep('pubsub')->nodeCreate($params['server'], $params['type'], $params['node']);
        return $this->process(XepPubsub::EVENT_NODE_CREATED);
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
            $this->conn->xep('pubsub')->nodeConfiguration($params['server'], $params['node'], null, false, $params['server']);
            return $this->process(XepPubsub::EVENT_NODECONFIG);
        }
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
        $this->conn->xep('pubsub')->nodeConfiguration($params['server'], $params['node'], $params['form'], false, $params['server']);
        return $this->process(Xep::EVENT_OK);
    }
    /**
     * Delete a node
     *
     * Parameters:
     * - server (optionnal)
     * - node (mandatory)
     *
     * @param array $params parameters
     *
     * @return XepResponse
     */
    public function nodeDelete($params) {
        $this->checkparams(array('node'), $params);
        $this->conn->xep('pubsub')->nodeDelete($params['server'], $params['node']);
        return $this->process(XepPubsub::EVENT_NODE_DELETED);
    }

    /**
     * Get the affiliations
     *
     * Without node, return all affiliations of the owner
     * With a node, return all affiliations on this node
     *
     * Parameters:
     * - server (required)
     * - node (optional)
     *
     * @param array $params parameters
     *
     * @return XepResponse
     *
     * @UrlMap('server', 'node')
     */
    public function affiliationGet($params) {
        $this->conn->xep('pubsub')->affiliationGet($params['server'], $params['node']);
        return $this->process(XepPubsub::EVENT_AFFILIATIONS);
    }
    /**
     * Set the affiliations of the user
     *
     * Parameters:
     * - server
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
        $this->conn->xep('pubsub')->affiliationSet($params['server'], $params['node'], $params['jid'], $params['affiliation']);
        return $this->process(XepPubsub::EVENT_AFFILIATIONS);
    }

    /**
     * Get the subscriptions
     *
     * Without node, return all subscriptions of the owner
     * With a node, return all subscriptions on this node
     *
     * Parameters:
     * - server (required)
     * - node (optional)
     *
     * @param array $params parameters
     *
     * @return XepResponse
     *
     * @UrlMap('server', 'node')
     */
    public function subscriptionGet($params) {
        $this->conn->xep('pubsub')->subscriptionGet($params['server'], $params['node']);
        return $this->process(XepPubsub::EVENT_SUBSCRIPTIONS);
    }

    /**
     * Subscribe to a node
     *
     * Parameters:
     * - server (required)
     * - node (required)
     * - options (optionnal)
     *
     * @param array $params parameters
     *
     * @return XepResponse
     *
     * @UrlMap('server', 'node')
     */
    public function subscriptionPost($params) {
        $this->checkparams(array('server', 'node'), $params);
        $this->conn->xep('pubsub')->subscribe($params['server'], $params['node'], $params['options']);
        return $this->process(XepPubsub::EVENT_SUBSCRIPTION_CREATED);
    }
    /**
     * Get subscription options
     *
     * Parameters:
     * - server (required)
     * - node (required)
     * - subid (required) subscription id
     *
     * @param array $params parameters
     *
     * @return XepResponse
     */
    public function subscriptionOptions($params) {
        $this->checkparams(array('node', 'subid'), $params);
        $this->conn->xep('pubsub')->subscriptionOptionsGet($params['server'], $params['node'], $params['subid']);
        return $this->process(XepPubsub::EVENT_SUBSCRIPTION_OPTIONS);
    }
    /**
     * Update subscription options
     *
     * Parameters:
     * - server (required)
     * - node (required)
     * - form (required) the options
     * - subid (required) subscription id
     *
     * @param array $params parameters
     *
     * @return XepResponse
     */
    public function subscriptionPut($params) {
        $this->checkparams(array('node', 'form', 'subid'), $params);
        $this->conn->xep('pubsub')->subscriptionOptionsSet($params['server'], $params['node'], $params['form'], $params['subid']);
        return $this->process(XepPubsub::EVENT_OK);
    }
    /**
     * Delete a subscription
     *
     * Parameters:
     * - server (required)
     * - node (required)
     * - subid (required) subscription id
     *
     * @param array $params parameters
     *
     * @return XepResponse
     */
    public function subscriptionDelete($params) {
        $this->checkparams(array('node', 'subid'), $params);
        $this->conn->xep('pubsub')->unsubscribe($params['server'], $params['node'], $params['subid']);
        return $this->process(XepPubsub::EVENT_SUBSCRIPTION_DELETED);
    }

    /**
     * Create a new content item
     *
     * Parameters:
     * - server (required)
     * - node (required)
     * - item (required) valid XML content
     * - id (optionnal)
     *
     * @param array $params parameters
     *
     * @return XepResponse
     */
    public function itemPost($params) {
        $this->checkparams(array('server', 'node', 'item'), $params);
        $this->conn->xep('pubsub')->itemPublish($params['server'], $params['node'], $params['item'], $params['id']);
        return $this->process(XepPubsub::EVENT_ITEM_PUBLISHED);
    }

    /**
     * Get a x-data form to publish a ATOM like content
     *
     * @param array $params parameters
     *
     * @return XepResponse
     */
    public function atomOptions($params) {
        $uuid = $this->_getUuid();
        $form = new XepForm();
        $form->addField(new XepFormField('Title', '', XepFormField::FIELD_TYPE_TEXTSINGLE, true, 'Title'))
             ->addField(new XepFormField('Summary', '', XepFormField::FIELD_TYPE_TEXTSINGLE, false, 'Summary'))
             ->addField(new XepFormField('Content', '', XepFormField::FIELD_TYPE_TEXTMULTI, false, 'Content'))
             ->addField(new XepFormField('Author', $this->params['user'], XepFormField::FIELD_TYPE_TEXTSINGLE, false, 'Author'))
             ->addField(new XepFormField('Contributor', $this->params['user'], XepFormField::FIELD_TYPE_TEXTSINGLE, false, 'Contributor'))
             ->addField(new XepFormField('Category', '', XepFormField::FIELD_TYPE_TEXTSINGLE, false, 'Category'))
             ->addField(new XepFormField('Link', '', XepFormField::FIELD_TYPE_TEXTSINGLE, false, 'Link'))
             ->addField(new XepFormField('Published', date('c'), XepFormField::FIELD_TYPE_TEXTSINGLE, false, 'Published'))
             ->addField(new XepFormField('Updated', date('c'), XepFormField::FIELD_TYPE_TEXTSINGLE, true, 'Updated'))
             ->addField(new XepFormField('Rights', '', XepFormField::FIELD_TYPE_TEXTSINGLE, false, 'Rights'))
             ->addField(new XepFormField('Source', '', XepFormField::FIELD_TYPE_TEXTSINGLE, false, 'Source'))
             ->addField(new XepFormField('Id', $uuid, XepFormField::FIELD_TYPE_TEXTSINGLE, true, 'Id'));
        return new XepResponse(array('form' => (string)$form), XepResponse::XEPRESPONSE_OK);
    }

    /**
     * Get a pretty but sad and not RFC 4122 compliant UUID
     *
     * @return string
     */
    private function _getUuid() {
        $uuid = '';
        while (strlen($uuid) < 32) $uuid .= substr(dechex(mt_rand()), 1);
        $uuid = substr($uuid, 0, 8) . '-' .
                substr($uuid, 8, 4) . '-' .
                substr($uuid, 12, 4) . '-' .
                substr($uuid, 16, 4) . '-' .
                substr($uuid, 20, 12);
        return $uuid;
    }
}