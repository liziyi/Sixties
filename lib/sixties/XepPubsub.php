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
 * Require base Xep class
 */
require_once dirname(__FILE__) . "/Xep.php";

/**
 * XepPubsub : implements client-side XEP 0060 : Publish-Subscribe
 *
 * @category   Library
 * @package    Sixties
 * @subpackage Xep
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
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

    const EVENT_AFFILIATIONS         = 'pubsub_event_affiliations';
    const EVENT_AFFILIATION_UPDATED  = 'pubsub_event_affiliation_updated';
    /**
     * Response to list of items request
     */
    const EVENT_ITEMS                = 'pubsub_event_items';
    const EVENT_ITEM_PUBLISHED       = 'pubsub_event_item_publisged';
    const EVENT_ITEM_DELETED         = 'pubsub_event_item_publisged';
    const EVENT_NODE_CREATED         = 'pubsub_event_created';
    const EVENT_NODECONFIG           = 'pubsub_event_nodeConfig';
    const EVENT_NODEDEFAULTCONFIG    = 'pubsub_event_nodeDefaultConfig';
    const EVENT_NODE_DELETED         = 'pubsub_event_node_deleted';
    /**
     * Nodes of a collection have been updated
     */
    const EVENT_NOTIF_COLLECTION     = 'pubsub_event_collection';
    /**
     * Configuration of node has been updated
     */
    const EVENT_NOTIF_CONFIG         = 'pubsub_event_notif_config';
    /**
     * Node has been deleted
     */
    const EVENT_NOTIF_DELETE         = 'pubsub_event_notif_delete';
    /**
     * New items
     */
    const EVENT_NOTIF_ITEMS          = 'pubsub_event_notif_items';
    /**
     * All items have been purged
     */
    const EVENT_NOTIF_PURGE          = 'pubsub_event_notif_purge';
    /**
     * Subscription state change
     */
    const EVENT_NOTIF_SUBSCRIPTION   = 'pubsub_event_notif_subscriptions';
    /**
     * List of subscriptions
     */
    const EVENT_SUBSCRIPTIONS        = 'pubsub_event_subscription';
    const EVENT_SUBSCRIPTION_OPTIONS = 'pubsub_event_subscription_options';
    const EVENT_SUBSCRIPTION_CREATED = 'pubsub_event_subscription_created';
    const EVENT_SUBSCRIPTION_DELETED = 'pubsub_event_subscription_deleted';
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
        $this->conn->addXPathHandler('message/{http://jabber.org/protocol/pubsub#event}event', 'handlerPubsubEvent', $this);

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
     * Create a "collection" node
     *
     * @param string $server the pubsub server name
     * @param string $type   node type. May be 'collection' or 'leaf'
     * @param string $node   the node name. If empty, an "instant node" will be created
     * @param string $parent parent's node. If null, use node's path
     * @param array  $config configuration options
     *
     * @return XepPubsub $this
     */
    public function nodeCreate($server, $type = 'leaf', $node = null, $parent = null, $config = null) {
        // Remove trailing slash
        if (substr($node, -1) == '/') $node = substr($node, 0, -1);
        if (!is_array($config)) $config = array();
        if (!isset($config['collection'])) {
            // Get the parent node
            if ($parent == null) {
                $parent = substr($node, 0, strrpos($node, '/'));
            }
            $config['pubsub#collection'] = $parent;
        }
        if ($type != null) {
            $config['pubsub#node_type'] = $type;
        }

        if ($node) $node = "node=\"$node\"";
        else $node = '';
        $configure = '';
        if (count($config) > 0) {
            $configure = $this->_buildNodeOptions($config);
        }

        $this->addCommonHandler(self::EVENT_NODE_CREATED);
        $this->sendIq(array('type'=>'set', 'to' => $server, 'msg'=>"<create $node/><configure>$configure</configure>"));

        return $this;
    }

    /**
     * Get or set the configuration of a node
     *
     * @param string  $server     the pubsub server name
     * @param string  $node       the node name
     * @param array   $config     hashmap of configuration-key => configuration-value or null to get the current configuration
     * @param boolean $collection to get the configuration of a collection, set config to null and collection to true
     *
     * @return XepPubsub $this
     */
    public function nodeConfiguration($server, $node, $config = null, $collection = false) {
        if ($node) $node = "node=\"$node\"";
        $req       = array();
        $req['to'] = $server;
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
        $this->sendIq(array('to' => $server, 'msg' => '<default />'));
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
            $form->addField(new XepFormField($key, $val));
        }
        return $form;
    }

    /**
     * Delete a node
     *
     * @param string $server the pubsub server name
     * @param string $node   the node name
     *
     * @return XepPubsub $this
     */
    public function nodeDelete($server, $node) {
        $node = "node=\"$node\"";
        // There's no other way to handle the response than by id
        $this->addCommonHandler(self::EVENT_NODE_DELETED);
        return $this->sendIq(array('type'=>'set', 'to' => $server, 'msg'=>"<delete $node />"));
    }

    /******************************************************************************************************************
     *
     * Publish
     *
     *****************************************************************************************************************/

    /**
     * Ask for the items of a node
     *
     * @param string  $server the pubsub server name
     * @param string  $node   the node name
     * @param String  $subid  subscription id
     * @param mixed   $items  item id or array of items
     * @param integer $max    max number of items
     *
     * @return this
     */
    public function itemGet($server, $node, $subid = null, $items = null, $max = null) {
        if ($subid) $subid = "subid='$subid'";
        if ($max) $max = "max_items='$max'";
        $req = '';
        if ($items !== null) {
            if (!is_array($items)) $items = array($items);
            foreach ($items as $item) $req .= "<item id='$item' />";
        }
        $this->sendIq(array('to' => $server, 'msg'=>"<items node=\"$node\" $subid $max>$req</items>"));
        return $this;
    }

    /**
     * Publish content into a node
     *
     * @param string $server the pubsub server name
     * @param string $node   the node name
     * @param string $item   XML content
     * @param string $id     id of the content (optionnal)
     *
     * @return XepPubsub $this
     */
    public function itemPublish($server, $node, $item, $id=null) {
        if (simplexml_load_string($item) === false) {
            $this->log("published content is not valid XML", XMPPHP_Log::LEVEL_ERROR);
            throw new XepException("published content is not valid XML", 400);
        } else {
            if ($id) $id = "id='$id'";
            // There's no other way to handle the response than by id
            $this->conn->addIdHandler($this->conn->getNextId(), 'handlerPubsubPublished', $this);
            $this->sendIq(array('type' => 'set', 'to' => $server, 'msg'=>"<publish node=\"$node\" ><item $id>$item</item></publish>"));
        }
        return $this;
    }

    /**
     * Delete published content
     *
     * @param string  $server the pubsub server name
     * @param string  $node   the node name
     * @param mixed   $items  item id or array of items. If null, all items will be purged !
     * @param boolean $notify should subscribers been notified
     *
     * @return $this
     */
    public function itemUnpublish($server, $node, $items = null, $notify = true) {
        if ($items === null) {
            // Purge all items
            return $this->itemPurge($server, $node);
        }
        if (!is_array($items)) $items = array($items);
        $unpublish = '';
        foreach ($items as $item) $unpublish .= "<item id='$item' />";
        $notif = ($notify ? "notify='true'" : '');
        $this->addCommonHandler(self::EVENT_ITEM_DELETED);
        $this->sendIq(array('type' => 'set', 'to' => $server, 'msg'=>"<retract node=\"$node\" $notif>$unpublish</retract>"));
        return $this;
    }

    /**
     * Purge all items of a node
     *
     * @param string $server the pubsub server name
     * @param string $node   the node name
     *
     * @return XepPubSub $this
     */
    public function itemPurge($server, $node) {
        $this->addCommonHandler(self::EVENT_ITEM_DELETED);
        $this->sendIq(array('type' => 'set', 'to' => $server, 'msg'=>"<purge node=\"$node\" />"));
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
     * @param string $server the pubsub server name
     * @param string $node   node name
     *
     * @return XepPubsub $this
     */
    public function affiliationGet($server, $node = null) {
        if ($node == null) {
            $this->conn->addIdHandler($this->conn->getNextId(), 'handlerPubsub', $this);
            $this->sendIq(array('to' => $server, 'msg'=>"<affiliations />"), self::NS);
        } else {
            $this->sendIq(array('to' => $server, 'msg'=>"<affiliations node=\"$node\"/>"), self::NS . '#owner');
        }
        return $this;
    }

    /**
     * Set affiliations
     *
     * @param string $server      the pubsub server name
     * @param string $node        the node name
     * @param string $jid         jid of the user to affiliate
     * @param string $affiliation one of self::AFFILIATION_*
     *
     * @return XepPubsub $this
     */
    public function affiliationSet($server, $node, $jid, $affiliation) {
        $this->addCommonHandler(self::EVENT_AFFILIATION_UPDATED);
        $this->sendIq(array('type' => 'set', 'to' => $server, 'msg'=>"<affiliations node=\"$node\"><affiliation jid=\"$jid\" affiliation='$affiliation' /></affiliations>"), self::NS . '#owner');
        return $this;
    }

    /**
     * Get subscriptions
     *
     * @param string $server the pubsub server name
     * @param string $node   the node name
     *
     * @return XepPubsub $this
     */
    public function subscriptionGet($server, $node = null) {
        if ($node == null) {
            $this->sendIq(array('to' => $server, 'msg'=>"<subscriptions />"), self::NS);
        } else {
            $this->sendIq(array('to' => $server, 'msg'=>"<subscriptions node=\"$node\"/>"), self::NS . '#owner');
        }
        return $this;
    }

    /**
     * Subscribe to a node
     *
     * @param string $server  the pubsub server name
     * @param string $node    the node name
     * @param array  $options options of the subscription
     *
     * @return XepPubsub $this
     */
    public function subscribe($server, $node = null, $options = null) {
        if ($node !== null) $node = "node=\"$node\"";
        //@ Should we use full or bare JID ? it's not clear in the spec, and implementations differs...
        $jid = $this->conn->getBareJid();
        $opt = '';
        if (is_array($options)) $opt = "<options>" . $this->_buildSubscriptionOptions($options) . "</options>";
        $this->sendIq(array('type' => 'set', 'to' => $server, 'msg'=>"<subscribe $node jid=\"$jid\" />$opt"));
        return $this;
    }

    /**
     * Unsubscrive from a node
     *
     * @param string $server the pubsub server name
     * @param string $node   the node name
     * @param string $subid  id of the subscription (optionnal)
     * @param string $jid    user's jid (optionnal) if null, use current user's one
     *
     * @return XepPubsub $this
     */
    public function unsubscribe($server, $node, $subid = null, $jid = null) {
        if ($subid) $subid = "subid=\"$subid\"";
        if ($jid == null) $jid = $this->conn->getBareJid();
        $this->conn->addIdHandler($this->conn->getNextId(), 'handlerPubsubSubsDelete', $this);
        $this->sendIq(array('type' => 'set', 'to' => $server, 'msg'=>"<unsubscribe node=\"$node\" jid=\"$jid\" $subid />"));
        return $this;
    }

    /**
     * Get Subscription options
     *
     * @param string $server the pubsub server name
     * @param string $node   the node name
     * @param string $subid  subscription id (optionnal)
     * @param string $jid    user's jid (optionnal) if null, use current user's one
     *
     * @return XepPubsub $this
     */
    public function subscriptionOptionsGet($server, $node, $subid = null, $jid = null) {
        if ($subid) $subid = "subid=\"$subid\"";
        if ($jid == null) $jid = $this->conn->getBareJid();
        $this->conn->addIdHandler($this->conn->getNextId(), 'handlerPubsubSubsOptionsGet', $this);
        $this->sendIq(array('type' => 'get', 'to' => $server, 'msg'=>"<options node=\"$node\" jid=\"$jid\" $subid />"));
        return $this;
    }

    /**
     * Set Subscription options
     *
     * @param string $server  the pubsub server name
     * @param string $node    the node name
     * @param array  $options hashmap of options
     * @param string $subid   subscription id (optionnal)
     * @param string $jid     user's jid (optionnal) if null, use current user's one
     *
     * @return XepPubsub $this
     */
    public function subscriptionOptionsSet($server, $node, $options, $subid = null, $jid = null) {
        if ($subid) $subid = "subid=\"$subid\"";
        if ($jid == null) $jid = $this->conn->getBareJid();
        $opt = $this->_buildSubscriptionOptions($options);
        $this->conn->addIdHandler($this->conn->getNextId(), 'handlerPubsubSubsOptions', $this);
        $this->sendIq(array('type' => 'set', 'to' => $server, 'msg'=>"<options node=\"$node\" jid=\"$jid\" $subid >$opt</options>"));
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
            $form->addField(new XepFormField("$key", $val));
        }
        return $form;
    }

    /**
     * Ask for pending subscription request
     *
     * Without arguments, get all pending requests and ask for details of each
     *
     * @param string $server    the server name
     * @param string $node      the node name
     * @param string $sessionid the sessionid provided by the server
     *
     * @return XepPubsub $this
     */
    public function subscriptionGetPending($server, $node = null, $sessionid = null) {
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
            $server);
        return $this;
    }

    /**
     * Send a subscription management request
     *
     * @param string $server the pubsub server name
     * @param string $node   the node name
     * @param string $jid    the subscriber's jid
     * @param string $allow  'true' or 'false'
     * @param string $subid  id of the request
     *
     * @return void
     */
    private function _subscriptionManage($server, $node, $jid, $allow, $subid = null) {
        $form = new XepForm();
        $req = $form->addFormtype('http://jabber.org/protocol/pubsub#subscribe_authorization')
                    ->addField(new XepFormField('pubsub#node', $node))
                    ->addField(new XepFormField('pubsub#subscriber_jid', $jid))
                    ->addField(new XepFormField('pubsub#allow', $allow));
        if ($subid !== null) $form->addField(new XepField('pubsub#sibid', $subid));
        $this->conn->sendMessage($server, (string)$req);
    }

    /**
     * Approve a subscription request
     *
     * @param string $server the pubsub server name
     * @param string $node   the node name
     * @param string $jid    the subscriber's jid
     * @param string $subid  id of the request
     *
     * @return XepPubsub $this
     */
    public function subscriptionApprove($server, $node, $jid, $subid = null) {
        $this->_subscriptionManage($server, $node, $jid, 'true', $subid);
        return $this;
    }
    /**
     * Deny a subscription request
     *
     * @param string $server the pubsub server name
     * @param string $node   the node name
     * @param string $jid    the subscriber's jid
     * @param string $subid  id of the request
     *
     * @return XepPubsub $this
     */
    public function subscriptionDeny($server, $node, $jid, $subid = null) {
        $this->_subscriptionManage($server, $node, $jid, 'false', $subid);
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
                    $jid = $this->conn->getBareJid();
                    foreach ($aff->subs as $sub) {
                        if ($sub->name == 'affiliation') {
                            if (!isset($sub->attrs['jid'])) $sub->attrs['jid'] = $jid;
                            $res->message[] = $sub->attrs;
                        }
                    }
                    $this->conn->event(self::EVENT_AFFILIATIONS, $res);
                }
                // List of items
                if ($pubsub->hasSub('items')) {
                    $res->message = $pubsub->sub('items')->toString();
                    $this->conn->event(self::EVENT_ITEMS, $res);
                }
                // Options of a subscription
                if ($pubsub->hasSub('options')) {
                    $res->message = $pubsub->sub('options')->toString();
                    $this->conn->event(self::EVENT_SUBSCRIPTION_OPTIONS, $res);
                }
                // Result of node creation
                if ($pubsub->hasSub('create')) {
                    $res->message = $pubsub->sub('create')->attrs;
                    $this->conn->event(self::EVENT_NODE_CREATED, $res);
                }
                // Result of a subscription creation
                if ($pubsub->hasSub('subscription')) {
                    $res->message = $pubsub->sub('subscription')->attrs;
                    $this->conn->event(self::EVENT_SUBSCRIPTION_CREATED, $res);
                }
                // List of user's subscriptions
                if ($pubsub->hasSub('subscriptions')) {
                    $res->message = array();
                    $subs = $pubsub->sub('subscriptions');
                    $jid  = $this->conn->getBareJid();
                    foreach ($subs->subs as $sub) {
                        if ($sub->name == 'subscription') {
                            if (!isset($sub->attrs['jid'])) $sub->attrs['jid'] = $node;
                            $res->message[] = $sub->attrs;
                        }
                    }
                    $this->conn->event(self::EVENT_SUBSCRIPTIONS, $res);
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
                    $node = $aff->attrs['node'];
                    foreach ($aff->subs as $sub) {
                        if ($sub->name == 'affiliation') {
                            if (!isset($sub->attrs['node'])) $sub->attrs['node'] = $node;
                            $res->message[] = $sub->attrs;
                        }
                    }
                    $this->conn->event(self::EVENT_AFFILIATIONS, $res);
                }
                // List of node's subscriptions
                if ($pubsub->hasSub('subscriptions')) {
                    $res->message = array();
                    $subs = $pubsub->sub('subscriptions');
                    $node = $subs->attrs['node'];
                    foreach ($subs->subs as $sub) {
                        if ($sub->name == 'subscription') {
                            if (!isset($sub->attrs['node'])) $sub->attrs['node'] = $node;
                            $res->message[] = $sub->attrs;
                        }
                    }
                    $this->conn->event(self::EVENT_SUBSCRIPTIONS, $res);
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
     * Handle request of options of subscription response
     *
     * Should go to handlerPubsub but ejabberd don't send the correct XML
     *
     * @param XMPPHP_XMLObj $xml the response
     *
     * @return void
     */
    public function handlerPubsubSubsOptionsGet(XMPPHP_XMLObj $xml){
        try {
            $res = $this->commonHandler($xml);
            if ($res->code != XepResponse::XEPRESPONSE_KO) {
                $res->message = $xml->toString();
                $this->conn->event(self::EVENT_SUBSCRIPTION_OPTIONS, $res);
            }
        } catch (Exception $e) {
            $res = new XepResponse($e->getMessage(), XepResponse::XEPRESPONSE_KO);
            $this->conn->event(self::EVENT_ERROR, $res);
        }
    }

    /**
     * Handle update of options of subscription response
     *
     * @param XMPPHP_XMLObj $xml the response
     *
     * @return void
     */
    public function handlerPubsubSubsOptions(XMPPHP_XMLObj $xml){
        try {
            $res = $this->commonHandler($xml);
            if ($res->code != XepResponse::XEPRESPONSE_KO) {
                $res->message = "Subscription updated";
                $this->conn->event(self::EVENT_OK, $res);
            }
        } catch (Exception $e) {
            $res = new XepResponse($e->getMessage(), XepResponse::XEPRESPONSE_KO);
            $this->conn->event(self::EVENT_ERROR, $res);
        }
    }

    /**
     * Handle Unsubscribe response
     *
     * @param XMPPHP_XMLObj $xml the response
     *
     * @return void
     */
    public function handlerPubsubSubsDelete(XMPPHP_XMLObj $xml){
        try {
            $res = $this->commonHandler($xml);
            if ($res->code != XepResponse::XEPRESPONSE_KO) {
                $res->message = "Subscription updated";
                $this->conn->event(self::EVENT_SUBSCRIPTION_DELETED, $res);
            }
        } catch (Exception $e) {
            $res = new XepResponse($e->getMessage(), XepResponse::XEPRESPONSE_KO);
            $this->conn->event(self::EVENT_ERROR, $res);
        }
    }

    /**
     * Handle item published
     *
     * @param XMPPHP_XMLObj $xml the response
     *
     * @return void
     */
    public function handlerPubsubPublished(XMPPHP_XMLObj $xml) {
        try {
            $res = $this->commonHandler($xml);
            if ($res->code != XepResponse::XEPRESPONSE_KO) {
                //@TODO get the node ID of the new item
                $this->conn->event(self::EVENT_ITEM_PUBLISHED, $res);
            }
        } catch (Exception $e) {
            $res = new XepResponse($e->getMessage(), XepResponse::XEPRESPONSE_KO);
            $this->conn->event(self::EVENT_ERROR, $res);
        }
    }

    /**
     * Handle events
     *
     * @param XMPPHP_XMLObj $xml the response
     *
     * @return void
     */
    public function handlerPubsubEvent(XMPPHP_XMLObj $xml) {
        try {
            $res = $this->commonHandler($xml);
            if ($res->code != XepResponse::XEPRESPONSE_KO) {
                if ($xml->hasSub('event', 'http://jabber.org/protocol/pubsub#event')) {
                    $event = $xml->sub('event');
                    foreach ($event->subs as $sub) {
                        // Prepare message
                        $message = $sub->toString();
                        //@TODO : eJabberd seems to send header without headers
                        //if ($xml->hasSub('headers')) $message .= $xml->sub('headers')->toString();
                        $res->message = "<event>$message</event>";
                        switch ($sub->name) {
                        case 'collection':
                            // Nodes of a collection have been updated
                            $this->conn->event(self::EVENT_NOTIF_COLLECTION, $res);
                            break;
                        case 'configuration':
                            // Node configuration upddated
                            $this->conn->event(self::EVENT_NOTIF_CONFIG, $res);
                            break;
                        case 'delete':
                            // Node has been deleted
                            $this->conn->event(self::EVENT_NOTIF_DELETE, $res);
                            break;
                        case 'items':
                            // New items published
                            $this->conn->event(self::EVENT_NOTIF_ITEMS, $res);
                            break;
                        case 'purge':
                            // All items have been purged
                            $this->conn->event(self::EVENT_NOTIF_PURGE, $res);
                            break;
                        case 'subscription':
                            // Subscription update
                            $this->conn->event(self::EVENT_NOTIF_SUBSCRIPTION, $res);
                            break;
                        default:
                            //@TODO
                            break;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $res = new XepResponse($e->getMessage(), XepResponse::XEPRESPONSE_KO);
            $this->conn->event(self::EVENT_ERROR, $res);
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
