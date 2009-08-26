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
 * @category   Library
 * @package    Sixties
 * @subpackage Xep
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @link       https://labo.clochix.net/projects/show/sixties
 */

/**
 * Require base Xep class
 */
require_once dirname(__FILE__) . "/Xep.php";

/**
 * XepMuc : implement client-side XEP 0045 : Multi-User Chat
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
class XepMuc extends Xep
{

    /**
     * Base namespace
     */
    const NS = 'http://jabber.org/protocol/muc';

    const EVENT_ROOM_CREATED = 'muc_event_room_created';
    const EVENT_CONFIG_FORM  = 'muc_event_configuration_form';

    /**
     * @var string the default conference server
     */
    protected $mucHost = null;

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
        $this->mucHost = ($host ? $host : 'conference.' . $conn->getHost());
    }

    /**
     * Create a room
     *
     * @param string $server the muc server name
     * @param string $room   the name of the room
     * @param string $nick   the nickname into thid room
     *
     * @return XepCommand $this
     */
    public function roomCreate($server, $room, $nick = null) {
        if ($nick == null) $nick = $this->conn->getLogin();
        if ($server == null) $server = $this->mucHost;
        $room = sprintf("%s@%s/%s", $room, $server, $nick);
        $msg  = '<x xmlns="' . self::NS . '" />';
        //@FIXME : presence => no id => won't work :S
        $this->addCommonHandler(self::EVENT_ROOM_CREATED);
        $this->conn->sendPresence($room, $msg);
        return $this;
    }

    /**
     * Request a room's configuration form
     *
     * @param string $server the pubsub server name
     * @param string $room   the name of the room
     *
     * @return XepCommand $this
     */
    public function configurationGet($server, $room) {
        if ($server == null) $server = $this->mucHost;
        if (strpos($room, '@') === false) $room = $room . '@' . $server;
        $req = array('to' => $room, 'msg' => "<query xmlns='http://jabber.org/protocol/muc#owner'/>");
        $this->addCommonHandler(self::EVENT_CONFIG_FORM);
        $this->conn->sendIq($req);
        return $this;
    }

}