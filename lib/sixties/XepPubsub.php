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
 * XepPubsub : implements client-side XEP 0060 : Publish-Subscribe
 *
 * @category  Library
 * @package   Sixties
 * @author    Clochix <clochix@clochix.net>
 * @copyright 2009 Clochix.net
 * @license   http://www.gnu.org/licenses/gpl.txt GPL
 * @version   $Id$
 * @link      https://labo.clochix.net/projects/show/sixties
 */

class XepPubsub extends Xep
{

    const NS = 'http://jabber.org/protocol/pubsub';

    const AFFILIATION_OWNER     = 'owner';
    const AFFILIATION_PUBLISHER = 'publisher';
    const AFFILIATION_MEMBER    = 'member';
    const AFFILIATION_NONE      = 'none';
    const AFFILIATION_OUTCAST   = 'outcast';

    const SUBSCRIPTION_NONE         = 'none';
    const SUBSCRIPTION_PENDING      = 'pending';
    const SUBSCRIPTION_UNCONFIGURED = 'unconfigured';
    const SUBSCRIPTION_SUBSCRIBED   = 'subscribed';

    const EVENT_AFFILIATIONS        = 'pubsub_event_affiliations';
    const EVENT_NODECONFIG          = 'pubsub_event_nodeConfig';
    const EVENT_NODEDEFAULTCONFIG   = 'pubsub_event_nodeDefaultConfig';
    const EVENT_COLLECTION          = 'pubsub_event_collection';
    const EVENT_CONFIGURATION       = 'pubsub_event_configuration';
    const EVENT_DELETE              = 'pubsub_event_delete';
    const EVENT_ITEMS               = 'pubsub_event_items';
    const EVENT_PURGE               = 'pubsub_event_purge';
    const EVENT_SUBSCRIPTION        = 'pubsub_event_subscription';

    /**
     * Any entity may subscribe to the node (i.e., without the necessity for
     * subscription approval) and any entity may retrieve items from the node
     * (i.e., without being subscribed)
     */
    const NODE_ACCESS_OPEN      = 'open';
    /**
     * Any entity with a subscription of type "from" or "both" may subscribe
     * to the node and retrieve items from the node; this access model applies
     * mainly to instant messaging systems (see RFC 3921).
     */
    const NODE_ACCESS_PRESENCE  = 'presence';
    /**
     * Any entity in the specified roster group(s) may subscribe to the node
     * and retrieve items from the node; this access model applies mainly to
     * instant messaging systems (see RFC 3921).
     */
    const NODE_ACCESS_ROSTER    = 'roster';
    /**
     * The node owner must approve all subscription requests, and only
     * subscribers may retrieve items from the node.
     */
    const NODE_ACCESS_AUTHORIZE = 'authorize';
    /**
     * An entity may subscribe or retrieve items only if on a whitelist managed
     * by the node owner. The node owner MUST automatically be on the whitelist.
     */
    const NODE_ACCESS_WHITELIST = 'whitelist';

    /**
     * @var string the pubsub host
     */
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
        //@TODO : use feature discovery to set the host
        $this->pubsubHost = ($host ? $host : 'pubsub.' . $conn->getHost());
        $this->conn->addXPathHandler('iq/{http://jabber.org/protocol/pubsub}pubsub', 'handlerPubsub', $this);
        $this->conn->addXPathHandler('iq/{http://jabber.org/protocol/pubsub#owner}pubsub', 'handlerPubsubOwner', $this);
        $this->conn->addXPathHandler('message/{http://jabber.org/protocol/pubsub#event}event', 'handlerEvent', $this);

        $this->conn->addEventHandler('form_message_handled', 'handlerFormMessage', $this);
    }

    /**
     * Send a request
     *
     * @param array  $params request parameters
     * @param string $ns     namespace of the action
     *
     * @return void
     */
    protected function sendIq($params, $ns = null) {
        if ($ns == null) {
            // try to guess namespace according to action
            $ns = self::NS;;
            $tmp = preg_split("/\W/", $params['msg']);
            switch (strtolower($tmp[1])) {
            // affiliations and subscriptions may be called with differents namespaces
            // case "affiliations":
            // case "subscriptions":
            case "configure":
            case "default":
            case "delete":
            case "purge":
                $ns .= '#owner';
                break;
            }
        }
        if ($this->pubsubHost) $params['to'] = $this->pubsubHost;
        $params["msg"] = "<pubsub xmlns=\"$ns\">{$params['msg']}</pubsub>";
        $this->conn->sendIq($params);
    }

    /******************************************************************************************************************
     *
     * Node management
     *
     *****************************************************************************************************************/

    /**
     * Create a node
     *
     * Each node type has some specific options...
     * - hometree : node name must be of the form /home/host/user/xxx
     *
     * @param string $type   node type (dag or flat)
     * @param string $node   the node name. If empty, an "instant node" will be created
     * @param array  $config configuration options
     *
     * @return XepPubsub $this
     */
    private function _createNode($type, $node=null, $config=null) {
        if ($node) $node = "node=\"$node\" type='$type'";
        $configure = '';
        if (is_array($config) && count($config) > 0) {
            $configure = $this->_buildNodeOptions($config);
        }
        $this->sendIq(array('type'=>'set', 'msg'=>"<create $node/><configure>$configure</configure>"));
        return $this;
    }
    /**
     * Create a "collection" node
     *
     * @param string $node   the node name. If empty, an "instant node" will be created
     * @param string $parent parent's node. If null, use node's path
     * @param array  $config configuration options
     *
     * @return XepPubsub $this
     *
     * @FIXME : fix me
     */
    public function nodeCreateCollection($node = null, $parent = null, $config = null) {
        if (substr($node, -1) == '/') $node = substr($node, 0, -1);
        if ($parent == null) {
            $parent = substr($node, 0, strrpos($node, '/'));
        }
        if (!is_array($config)) $config = array();
        if (!isset($config['collection'])) {
            $config['collection'] == $parent;
        }
        $config['node_type'] = 'collection';
        $this->_createNode('dag', $node, $config);
        return $this;
    }
    /**
     * Create a "leaf" node
     *
     * @param string $node   the node name. If empty, an "instant node" will be created
     * @param array  $config configuration options
     *
     * @return XepPubsub $this
     */
    public function nodeCreateLeaf($node = null, $config=null) {
        if (substr($node, -1) == '/') $node = substr($node, 0, -1);
        if (!is_array($config)) $config = array();
        $config['node_type'] = 'leaf';
        $this->_createNode('dag', $node, $config);
        return $this;
    }

    /**
     * Get or set the configuration of a node
     *
     * @param string  $node       the node name
     * @param array   $config     hashmap of configuration-key => configuration-value or null to get the current configuration
     * @param boolean $collection to get the configuration of a collection, set config to null and collection to true
     * @param string  $server     server name
     *
     * @return XepPubsub $this
     */
    public function nodeConfiguration($node, $config = null, $collection = false, $server = null) {
        if ($node) $node = "node=\"$node\"";
        $req       = array();
        $configure = '';
        if (is_array($config)) {
            $req['type'] = 'set';
            $configure = $this->_buildNodeOptions($config);
            $this->conn->addIdHandler($this->conn->getNextId(), 'handlerPubsubNodeConfigured', $this);
        } else {
            $req['type'] = 'get';
            if ($collection === true) {
                $configure = $this->_buildNodeOptions(array('node_type' => 'collection'));
            }
        }
        $req['msg'] = "<configure $node>$configure</configure>";
        $this->sendIq($req);
        return $this;
    }

    /**
     * Ask for the default configuration of nodes
     *
     * @param string $server server name
     *
     * @return XepPubsub $this
     */
    public function nodeConfigurationDefault($server = null) {
        $req = array('msg' => '<default />');
        if ($server != null) $req['to'] = $server;
        $this->sendIq($req);
        return $this;
    }

    /**
     * Build the request for setting node option
     *
     * @param array $config hashmap of options
     *
     * @return XepForm
     */
    private function _buildNodeOptions($config) {
        $form = new XepForm();
        $form->addFormtype('http://jabber.org/protocol/pubsub#node_config');
        foreach ($config as $key => $val) {
            $form->addField(new XepFormField("$key", $val));
        }
        return $form;
    }

    /**
     * Delete a node
     *
     * @param string $node the node name
     *
     * @return XepPubsub $this
     */
    public function deleteNode($node = null) {
        if ($node) $node = "node=\"$node\"";
        // There's no other way to handle the response than by id
        $this->conn->addIdHandler($this->conn->getNextId(), 'handlerPubsubDelete', $this);
        return $this->sendIq(array('type'=>'set', 'msg'=>"<delete $node />"));
    }

    /******************************************************************************************************************
     *
     * Publish
     *
     *****************************************************************************************************************/

    /**
     * Publish content into a node
     *
     * @param string $node the node name
     * @param string $item XML content
     * @param string $id   id of the content (optionnal)
     *
     * @return XepPubsub $this
     */
    public function itemPublish($node, $item, $id=null) {
        if (simplexml_load_string($item) === false) {
            //@TODO : throws exception
            $this->log("published content is not valid XML", XMPPHP_Log::LEVEL_ERROR);
        } else {
            if ($id) $id = "id='$id'";
            $this->sendIq(array('type' => 'set', 'msg'=>"<publish node=\"$node\" ><item $id>$item</item></publish>"));
        }
        return $this;
    }

    /**
     * Delete published content
     *
     * @param string  $node   the node name
     * @param mixed   $items  item id or array of items. If null, all items will be purged !
     * @param boolean $notify should subscribers been notified
     *
     * @return $this
     */
    public function itemUnpublish($node, $items = null, $notify = null) {
        if ($items === null) {
            // Purge all items
            return $this->itemsPurge($node);
        }
        if (!is_array($items)) $items = array($items);
        $req = '';
        foreach ($items as $item) $req .= "<item id='$item' />";
        $notif = ($notify ? "notify='true'" : '');
        $this->sendIq(array('type' => 'set', 'msg'=>"<retract node=\"$node\" $notif>$req</retract>"));
        return $this;
    }

    /**
     * Purge all items of a node
     *
     * @param string $node the node name
     *
     * @return XepPubSub $this
     */
    public function itemsPurge($node) {
        $req = '';
        $this->sendIq(array('type' => 'set', 'msg'=>"<purge node=\"$node\" />"));
        return $this;
    }
    /******************************************************************************************************************
     *
     * Items
     *
     *****************************************************************************************************************/

    /**
     * Ask for the items of a node
     *
     * @param string  $node  the node name
     * @param mixed   $items item id or array of items
     * @param integer $max   max number of items
     *
     * @return this
     */
    public function getItems($node, $items = null, $max = null) {
        if ($max) $max = "max_items='$max'";
        $req = '';
        if ($items !== null) {
            if (!is_array($items)) $items = array($items);
            foreach ($items as $item) $req .= "<item id='$item' />";
        }
        $this->sendIq(array('msg'=>"<items node=\"$node\" $max>$req</items>"));
        return $this;
    }

    /******************************************************************************************************************
     *
     * Subscription and affiliations
     *
     *****************************************************************************************************************/

    /**
     * Get affiliations
     *
     * Without node, return all affiliations of the owner
     * With a node, return all affiliations on this node
     *
     * @param string $node node name
     *
     * @return XepPubsub $this
     */
    public function affiliationGet($node = null) {
        if ($node == null) {
            $this->conn->addIdHandler($this->conn->getNextId(), 'handlerPubsub', $this);
            $this->sendIq(array('msg'=>"<affiliations />"), self::NS);
        } else {
            $this->sendIq(array('msg'=>"<affiliations node=\"$node\"/>"), self::NS . '#owner');
        }
        return $this;
    }

    /**
     * Set affiliations
     *
     * @param string $node        the node name
     * @param string $jid         jid of the user to affiliate
     * @param string $affiliation one of self::AFFILIATION_*
     *
     * @return XepPubsub $this
     */
    public function affiliationSet($node, $jid, $affiliation) {
        $this->sendIq(array('type' => 'set', 'msg'=>"<affiliations node=\"$node\"><affiliation jid=\"$jid\" affiliation='$affiliation' /></affiliations>"), self::NS . '#owner');
        return $this;
    }

    /**
     * Get subscriptions
     *
     * @param string $node the node name
     *
     * @return XepPubsub $this
     */
    public function getSubscriptions($node = null) {
        if ($node) $node = "node=\"$node\"";
        $this->sendIq(array('msg'=>"<subscriptions $node />"));
        return $this;
    }

    /**
     * Subscribe to a node
     *
     * @param string $node    the node name
     * @param array  $options options of the subscription
     *
     * @return XepPubsub $this
     */
    public function subscribe($node = null, $options = null) {
        if ($node !== null) $node = "node=\"$node\"";
        $jid = $this->conn->getBaseJid();
        $opt = '';
        if (is_array($options)) $opt = "<options>" . $this->_buildSubscriptionOptions($options) . "</options>";
        $this->sendIq(array('type' => 'set', 'msg'=>"<subscribe $node jid=\"$jid\" />$opt"));
        return $this;
    }

    /**
     * Unsubscrive from a node
     *
     * @param string $node  the node name
     * @param string $subid id of the subscription (optionnal)
     *
     * @return XepPubsub $this
     */
    public function unsubscribe($node, $subid = null) {
        if ($subid) $subid = "subid=\"$subid\"";
        $jid = $this->conn->getBaseJid();
        $this->sendIq(array('type' => 'set', 'msg'=>"<unsubscribe node=\"$node\" jid=\"$jid\" $subid />"));
        return $this;
    }

    /**
     * Get Subscription options
     *
     * @param string $node  the node name
     * @param string $subid subscription id (optionnal)
     *
     * @return XepPubsub $this
     */
    public function subscriptionOptionsGet($node, $subid = null) {
        if ($subid) $subid = "subid=\"$subid\"";
        $jid = $this->conn->getBaseJid();
        $this->sendIq(array('type' => 'get', 'msg'=>"<options node=\"$node\" jid=\"$jid\" $subid />"));
        return $this;
    }

    /**
     * Set Subscription options
     *
     * @param string $node    the node name
     * @param array  $options hashmap of options
     * @param string $subid   subscription id (optionnal)
     *
     * @return XepPubsub $this
     */
    public function subscriptionOptionsSet($node, $options, $subid = null) {
        if ($subid) $subid = "subid=\"$subid\"";
        $jid = $this->conn->getBaseJid();
        $opt = $this->_buildSubscriptionOptions($options);
        $this->sendIq(array('type' => 'set', 'msg'=>"<options node=\"$node\" jid=\"$jid\" $subid >$opt</options>"));
        return $this;
    }

    /**
     * Build the request for subscription option
     *
     * @param array $options hashmap of options
     *
     * @return string containing XML
     */
    private function _buildSubscriptionOptions($options) {
        $form =new XepForm();
        $form->addFormtype('http://jabber.org/protocol/pubsub#subscribe_options');
        foreach ($options as $key => $val) {
            $form->addField(new XepFormField("pubsub#$key", $val));
        }
        return $form;
    }

    /**
     * Ask for pending subscription request
     *
     * Without arguments, get all pending requests and ask for details of each
     *
     * @param string $node      the node name
     * @param string $sessionid the sessionid provided by the server
     *
     * @return XepPubsub $this
     */
    public function subscriptionGetPending($node = null, $sessionid = null) {
        if ($node !== null) {
            $form = new XepForm(XepForm::FORM_TYPE_SUBMIT);
            $form->addField(new XepFormField('pubsub#node', $node));
        } else {
            $this->conn->addIdHandler($this->conn->getNextId(), 'handlerGetPending', $this);
            $form = null;
        }
        $this->conn->xep('command')->execute('http://jabber.org/protocol/pubsub#get-pending',
            XepCommand::COMMAND_ACTION_EXECUTE,
            $sessionid,
            $form,
            $this->pubsubHost);
        return $this;
    }

    /**
     * Send a subscription management request
     *
     * @param string $node  the node name
     * @param string $jid   the subscriber's jid
     * @param string $allow 'true' or 'false'
     * @param string $subid id of the request
     *
     * @return void
     */
    private function _subscriptionManage($node, $jid, $allow, $subid = null) {
        $form = new XepForm();
        $req = $form->addFormtype('http://jabber.org/protocol/pubsub#subscribe_authorization')
                    ->addField(new XepFormField('pubsub#node', $node))
                    ->addField(new XepFormField('pubsub#subscriber_jid', $jid))
                    ->addField(new XepFormField('pubsub#allow', $allow));
        if ($subid !== null) $form->addField(new XepField('pubsub#sibid', $subid));
        $this->conn->sendForm($this->pubsubHost, $req);
    }

    /**
     * Approve a subscription request
     *
     * @param string $node  the node name
     * @param string $jid   the subscriber's jid
     * @param string $subid id of the request
     *
     * @return XepPubsub $this
     */
    public function subscriptionApprove($node, $jid, $subid = null) {
        $this->_subscriptionManage($node, $jid, 'true', $subid);
        return $this;
    }
    /**
     * Deny a subscription request
     *
     * @param string $node  the node name
     * @param string $jid   the subscriber's jid
     * @param string $subid id of the request
     *
     * @return XepPubsub $this
     */
    public function subscriptionDeny($node, $jid, $subid = null) {
        $this->_subscriptionManage($node, $jid, 'false', $subid);
        return $this;
    }

    /******************************************************************************************************************
     *
     * Handlers
     *
     *****************************************************************************************************************/

    /**
     * Handle responses
     *
     * @param XMPPHP_XMLObj $xml the response
     *
     * @return void
     */
    public function handlerPubsub(XMPPHP_XMLObj $xml) {
        try {
            $res = $this->commonHandler($xml);
            if ($res->code != XepResponse::XEPRESPONSE_KO) {
                $node    = $xml->attrs['from'] . '!' . $query->attrs['node'];
                $pubsub = $xml->sub('pubsub');
                // List of user's affiliations
                if ($pubsub->hasSub('affiliations')) {
                    $res->message = array();
                    $aff = $pubsub->sub('affiliations');
                    $jid = $this->conn->getBaseJid();
                    foreach ($aff->subs as $sub) {
                        if ($sub->name == 'affiliation') {
                            if (!isset($sub->attrs['jid'])) $sub->attrs['jid'] = $jid;
                            $res->message[] = $sub->attrs;
                        }
                    }
                    $this->conn->event(self::EVENT_AFFILIATIONS, $res);
                }
            }
        } catch (Exception $e) {
            $res = new XepResponse($e->getMessage(), XepResponse::XEPRESPONSE_KO);
            $this->conn->event(self::EVENT_ERROR, $res);
        }
    }
    /**
     * Handle responses
     *
     * @param XMPPHP_XMLObj $xml the response
     *
     * @return void
     */
    public function handlerPubsubOwner(XMPPHP_XMLObj $xml) {
        try {
            $res = $this->commonHandler($xml);
            if ($res->code != XepResponse::XEPRESPONSE_KO) {
                $node    = $xml->attrs['from'] . '!' . $query->attrs['node'];
                $pubsub = $xml->sub('pubsub');
                // List of node's affiliations
                if ($pubsub->hasSub('affiliations')) {
                    $res->message = array();
                    $aff = $pubsub->sub('affiliations');
                    $node = $add->attrs['node'];
                    foreach ($aff->subs as $sub) {
                        if ($sub->name == 'affiliation') {
                            if (!isset($sub->attrs['node'])) $sub->attrs['node'] = $node;
                            $res->message[] = $sub->attrs;
                        }
                    }
                    $this->conn->event(self::EVENT_AFFILIATIONS, $res);
                }
                if ($pubsub->hasSub('configure')) {
                    // node configuration
                    $res->message = $pubsub->sub('configure')->toString();
                    $this->conn->event(self::EVENT_NODECONFIG, $res);
                }
                if ($pubsub->hasSub('default')) {
                    // this is the default node configuration
                    $res->message = $pubsub->sub('default')->toString();
                    $this->conn->event(self::EVENT_NODEDEFAULTCONFIG, $res);
                }
            }
        } catch (Exception $e) {
            $res = new XepResponse($e->getMessage(), XepResponse::XEPRESPONSE_KO);
            $this->conn->event(self::EVENT_ERROR, $res);
        }
    }

    /**
     * Handle configuration request response
     *
     * @param XMPPHP_XMLObj $xml the response
     *
     * @return void
     */
    public function handlerPubsubNodeConfigured(XMPPHP_XMLObj $xml){
        try {
            $res = $this->commonHandler($xml);
            if ($res->code != XepResponse::XEPRESPONSE_KO) {
                $res->message = "Configuration updated";
                $this->conn->event(self::EVENT_OK, $res);
            }
        } catch (Exception $e) {
            $res = new XepResponse($e->getMessage(), XepResponse::XEPRESPONSE_KO);
            $this->conn->event(self::EVENT_ERROR, $res);
        }
    }

    /**
     * Handle delete responses
     *
     * @param XMPPHP_XMLObj $xml the response
     *
     * @return void
     */
    public function handlerPubsubDelete(XMPPHP_XMLObj $xml) {
        $this->conn->history($xml);
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

    /**
     * Handle events
     *
     * @param XMPPHP_XMLObj $xml the response
     *
     * @return void
     */
    public function handlerEvent(XMPPHP_XMLObj $xml) {
        // @FIXME : on errors there's no event
        if ($xml->hasSub('error')) {
            $err = $xml->sub('error');
            $msg = '';
            foreach ($err->subs as $sub) $msg.= $sub->name . " ";
            $this->log("ERROR : ({$err->attrs['code']}) {$err->attrs['type']} : $msg", XMPPHP_Log::LEVEL_ERROR);
        }
        if ($xml->hasSub('event', 'http://jabber.org/protocol/pubsub#event')) {
            $event = $xml->sub('event');
            if ($event->hasSub('collection')) {
                //@TODO
                $this->conn->event(self::EVENT_COLLECTION);
            }
            if ($event->hasSub('configuration')) {
                //@TODO
                $this->conn->event(self::EVENT_CONFIGURATION);
            }
            if ($event->hasSub('delete')) {
                //@TODO
                $this->conn->event(self::EVENT_DELETE);
            }
            if ($event->hasSub('items')) {
                //@TODO
                $this->conn->event(self::EVENT_ITEMS);
            }
            if ($event->hasSub('purge')) {
                $this->conn->event(self::EVENT_PURGE, $event->sub('purge')->attrs['node']);
            }
            if ($event->hasSub('subscription')) {
                $sub = $event->sub('subscription');
                $this->conn->event(self::EVENT_SUBSCRIPTION, array('node' => $sub->attrs['node'], 'subscription' => $sub->attrs['subscription']));
            }
        }
        $this->log("EVENT received", XMPPHP_Log::LEVEL_WARNING);
        $this->conn->event('pubsub_event_handled', $xml->toString());
    }

    /**
     * Handle forms
     *
     * @param XepForm $form the form
     *
     * @return void
     */
    public function handlerFormMessage(XepForm $form) {
    }

    /**
     * Handle get pending subscription request
     *
     * @param XMPPHP_XMLObj $xml the form
     *
     * @return void
     */
    public function handlerGetPending(XMPPHP_XMLObj $xml) {
        if ($xml->attrs['type'] == 'result') {
            $command = $xml->sub('command');
            $form    = XepForm::load($command);
            $options = $form->getField('pubsub#node')->getOptions();
            if (count($options) > 0) {
                $sessionid = $command->attrs['sessionid'];
                foreach ($options as $option) {
                    $this->subscriptionGetPending($option['value'], $sessionid);
                }
            }
        } else {
            //@TODO : manage errors...
        }
    }
}
