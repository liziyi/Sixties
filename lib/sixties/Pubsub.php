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
 * @package   Sixties
 * @category  Library
 * @license   http://www.gnu.org/licenses/gpl.txt GPL
 * @author    Clochix <clochix@clochix.net>
 * @copyright 2009 Clochix.net
 */

require_once dirname(__FILE__) . "/Xep.php";

/**
 * Discover : implements client-side XEP 0060 : Publish-Subscribe
 *
 * @package   Sixties
 * @category  Library
 * @license   http://www.gnu.org/licenses/gpl.txt GPL
 * @author    Clochix <clochix@clochix.net>
 * @copyright 2009 Clochix.net
 * @version   $Id$
 */

class Pubsub extends Xep{

    protected $pubsubHost = null;

    /**
     * Create object and register handlers
     *
     * @param XMPP2  $conn the connexion
     * @param string $host the pubsub server
     *
     * @return void
     */
    public function __construct($conn, $host = null) {
        parent::__construct($conn);
        $this->pubsubHost = $host;
        $this->conn->addXPathHandler('iq/{http://jabber.org/protocol/pubsub}pubsub', 'handlerPubsub', $this);
        $this->conn->addXPathHandler('iq/{http://jabber.org/protocol/pubsub#owner}pubsub', 'handlerPubsubOwner', $this);
    }

    /**
     * Send a request
     *
     * @param array $params request parameters
     *
     * @return : mixed : FALSE or integer
     */
    protected function sendIq($params) {
        // set namespace according to action
        $ns = "http://jabber.org/protocol/pubsub";
        $tmp = preg_split("/\W/", $params['msg']);
        switch (strtolower($tmp[1])) {
            // @FIXME affiliations and subscriptions may be called with differents namespaces
            // case "affiliations":
            // case "subscriptions":
            case "configure":
            case "default":
            case "delete":
            case "purge":
                $ns .= '#owner';
                break;
        }
        if ($this->pubsubHost) $params['to'] = $this->pubsubHost;
        $params["msg"] = "<pubsub xmlns='$ns'>{$params['msg']}</pubsub>";
        return $this->conn->sendIq($params);
    }

    /**
     * Create a node
     *
     * @param string $type   node type (dag or flat)
     * @param string $node   the node name. If empty, an "instant node" will be created
     * @param array  $config configuration options
     *
     * @return : mixed : FALSE or integer
     */
    private function _createNode($type, $node=null, $config=null) {
        if ($node) $node = "node='$node' type='$type'";
        $configure = '';
        if (is_array($config)) {
            foreach ($config as $key => $val) {
                $configure .= "<field var='pubsub#$key'><value>$val</value></field>";
            }
            $configure = "<x xmlns='jabber:x:data' type='submit'><field var='FORM_TYPE' type='hidden'><value>http://jabber.org/protocol/pubsub#node_config</value></field>$configure</x>";
        }
        return $this->sendIq(array('type'=>'set', 'msg'=>"<create $node/><configure>$configure</configure>"));
    }
    /**
     * Create a "collection" node
     *
     * @param string $node   the node name. If empty, an "instant node" will be created
     * @param array  $config configuration options
     *
     * @return : mixed : FALSE or integer
     *
     * @FIXME : fix me
     */
    public function createCollection($node = null, $config=null) {
        if (!is_array($config)) $config = array();
        $config['collections'] = 1;
        return $this->_createNode('dag', $node, $config);
    }
    /**
     * Create a "leaf" node
     *
     * @param string $node   the node name. If empty, an "instant node" will be created
     * @param array  $config configuration options
     *
     * @return : mixed : FALSE or integer
     */
    public function createNode($node = null, $config=null) {
        return $this->_createNode('flat', $node, $config);
    }

    /**
     * Get or set the configuration of a node
     *
     * @param string $node   the node name
     * @param array  $config hashmap of configuration-key => configuration-value or null to get the current configuration
     *
     * @return : mixed : FALSE or integer
     */
    public function configureNode($node, $config = null) {
        if ($node) $node = "node='$node'";
        $configure = '';
        if (is_array($config)) {
            $type = 'set';
            foreach ($config as $key => $val) {
                $configure .= "<field var='pubsub#$key'><value>$val</value></field>";
            }
            $configure = "<x xmlns='jabber:x:data' type='submit'><field var='FORM_TYPE' type='hidden'><value>http://jabber.org/protocol/pubsub#node_config</value></field>$configure</x>";
        } else {
            $type = 'get';
        }
        return $this->sendIq(array('type'=>$type, 'msg'=>"<configure $node>$configure</configure>"));
    }

    /**
     * Delete a node
     *
     * @param string $node   the node name
     *
     * @return : mixed : FALSE or integer
     */
    public function deleteNode($node = null) {
        if ($node) $node = "node='$node'";
        // There's no other way to handle the response than by id
        $id = $this->conn->getLastId() + 1;
        $this->conn->addIdHandler($id, 'handlerPubsubDelete', $this);
        return $this->sendIq(array('type'=>'set', 'msg'=>"<delete $node />"));
    }

    /**
     * Get affiliations
     *
     * @return : mixed : FALSE or integer
     */
    public function getAffiliations() {
        return $this->sendIq(array('msg'=>"<affiliations />"));
    }

    /**
     * Get subscriptions
     *
     * @return : mixed : FALSE or integer
     */
    public function getSubscriptions($node = null) {
        if ($node) $node = "node='$node'";
        return $this->sendIq(array('msg'=>"<subscriptions $node />"));
    }

    /**
     * Publish content into a node
     *
     * @param string $node the node name
     * @param string item  XML content
     *
     * @return : mixed : FALSE or integer
     */
    public function publish($node, $item, $id=null) {
        if (simplexml_load_string($item) === FALSE) {
            $this->log("published content is not valid XML", XMPPHP_Log::LEVEL_ERROR);
        } else {
            if ($id) $id = "id='$id'";
            return $this->sendIq(array('type' => 'set', 'msg'=>"<publish node='$node' ><item $id>$item</item></publish>"));
        }
    }

    /**
     * Subscribe to a node
     *
     * @param string $node the node name
     *
     * @return : mixed : FALSE or integer
     */
    public function subscribe($node) {
        $jid = $this->conn->getBaseJid();
        return $this->sendIq(array('type' => 'set', 'msg'=>"<subscribe node='$node' jid='$jid' />"));
    }

    /**
     * Handle responses
     *
     * @param XMLObj $xml
     *
     * @return void
     */
    public function handlerPubsub($xml) {
        if ($xml->hasSub('error')) {
            $err = $xml->sub('error');
            $msg = '';
            foreach ($err->subs as $sub) $msg.= $sub->name . " ";
            $this->log("ERROR : ({$err->attrs['code']}) {$err->attrs['type']} : $msg", XMPPHP_Log::LEVEL_ERROR);
        }
        $this->conn->event('pubsub_handled');
    }
    /**
     * Handle responses
     *
     * @param XMLObj $xml
     *
     * @return void
     */
    public function handlerPubsubOwner($xml) {
        if ($xml->hasSub('error')) {
            $err = $xml->sub('error');
            $msg = '';
            foreach ($err->subs as $sub) $msg.= $sub->name . " ";
            $this->log("ERROR : ({$err->attrs['code']}) {$err->attrs['type']} : $msg", XMPPHP_Log::LEVEL_ERROR);
        } elseif ($xml->hasSub('default')) {
            //@TODO
        }
        $this->conn->event('pubsub_handled');
    }

    /**
     * Handle delete responses
     *
     * @param XMLObj $xml
     *
     * @return void
     */
    public function handlerPubsubDelete($xml) {
        if ($xml->hasSub('error')) {
            $err = $xml->sub('error');
            $msg = '';
            foreach ($err->subs as $sub) $msg.= $sub->name . " ";
            $this->log("ERROR : ({$err->attrs['code']}) {$err->attrs['type']} : $msg", XMPPHP_Log::LEVEL_ERROR);
        } else {
            $this->log("Node deleted", XMPPHP_Log::LEVEL_INFO);
        }
        $this->conn->event('pubsub_handled');
    }
}
