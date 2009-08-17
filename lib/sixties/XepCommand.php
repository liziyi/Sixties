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
 * XepCommand : implement client-side XEP 0050 : Ad-Hoc Commands
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
class XepCommand extends Xep
{
    /**
     * @var array command list of commands
     */
    public $commands = array();

    /**
     * @const base namespace
     */
    const NS = 'http://jabber.org/protocol/commands';

    const EVENT_ITEMS    = 'command_event_items';
    const EVENT_INFO     = 'command_event_info';
    const EVENT_COMMAND  = 'command_event_command';

    const COMMAND_ACTION_CANCEL   = 'cancel';
    const COMMAND_ACTION_COMPLETE = 'complete';
    const COMMAND_ACTION_EXECUTE  = 'execute';
    const COMMAND_ACTION_NEXT     = 'next';
    const COMMAND_ACTION_PREV     = 'prev';

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
        $this->conn->addXPathHandler('iq/{' . self::NS . '}command', 'handlerCommand', $this);
    }

    /**
     * Ask for the list of available commands
     *
     * @return XepCommand $this
     */
    public function getCommandList() {
        $this->conn->xep('discover')->discoverItems(null, self::NS);
        return $this;
    }

    /**
     * Execute a command
     *
     * @param string  $node      the node name
     * @param string  $action    the action
     * @param string  $sessionid the current session id
     * @param XepForm $form      command parameters
     * @param string  $to        send command to a specific dest
     *
     * @return XepCommand $this
     */
    public function execute($node, $action = null, $sessionid = null, XepForm $form = null, $to = null) {
        if ($action) $action = "action='$action'";
        if ($sessionid) $sessionid = "sessionid='$sessionid'";
        $req = array('type'=>'set', 'msg'=>"<command xmlns='" . self::NS . "' node='$node' $action $sessionid >$form</command>");
        if ($to !== null) $req['to'] = $to;
        $this->conn->sendIq($req);
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
        $this->conn->history($xml);
        $query = $xml->sub('query');
        if ($query->attrs['node'] != self::NS) return;
        foreach ($query->subs as $sub) {
            if ($sub->name == 'item') {
                $this->commands[$sub->attrs['node']] = array(
                    'jid'  => $sub->attrs['jid'],
                    'name' => $sub->attrs['name'],
                    'node' => $sub->attrs['node'],
                    'identities' => array(),
                    'features'   => array()
                    );
            }
        }
        if (count($this->commands) > 0) {
            foreach (array_keys($this->commands) as $node) $this->conn->xep('discover')->discoverInfo(null, $node);
        }
        $this->conn->event(self::EVENT_ITEMS, $this->commands);
    }

    /**
     * Handle info response
     *
     * @param XMPPHP_XMLObj $xml the result
     *
     * @return void
     */
    public function handlerInfo(XMPPHP_XMLObj $xml) {
        $this->conn->history($xml);
        $query = $xml->sub('query');
        $node = $query->attrs['node'];
        if (!isset($this->commands[$node])) return;
        foreach ($query->subs as $sub) {
            if ($sub->name == 'identity') {
                $this->commands[$node]['identities'][] = array(
                    'category' => $sub->attrs['category'],
                    'name'     => $sub->attrs['name'],
                    'type'     => $sub->attrs['type']
                    );
            }
            if ($sub->name == 'feature') {
                $this->commands[$node]['features'][$sub->attrs['var']] = $sub->attrs['var'];
            }
        }
        $this->conn->event(self::EVENT_INFO, array('node' => $node, 'command' => $this->commands[$node]));
    }

    /**
     * Handle command response
     *
     * @param XMPPHP_XMLObj $xml the result
     *
     * @return void
     */
    public function handlerCommand(XMPPHP_XMLObj $xml) {
        try {
            $res = $this->commonHandler($xml);
            if ($res->code != XepResponse::XEPRESPONSE_KO) {
                if ($xml->hasSub('command')) {
                    $command = $xml->sub('command');
                    $tmp = '';
                    foreach ($command->subs as $sub) {
                        $tmp .= $sub->toString();
                    }
                    $res->message = array(
                        'type'      => $xml->attrs['type'],
                        'node'      => $command->attrs['node'],
                        'action'    => $command->attrs['action'],
                        'sessionid' => $command->attrs['sessionid'],
                        'status'    => $command->attrs['status'],
                        'form'      => $tmp
                        );
                    $this->conn->event(self::EVENT_COMMAND, $res);
                }
            }
        } catch (Exception $e) {
            $res = new XepResponse($e->getMessage(), XepResponse::XEPRESPONSE_KO);
            $this->conn->event(self::EVENT_ERROR, $res);
        }
    }

}