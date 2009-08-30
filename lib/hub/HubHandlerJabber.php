<?php
/**
 * This file is part of Sixties, a set of PHP classes for playing with XMPP PubSub
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
 * @subpackage Hub
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @link       https://labo.clochix.net/projects/show/sixties
 */

/**
 * HubHandlerJabber : send an instant message
 *
 * @category   Library
 * @package    Sixties
 * @subpackage Hub
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */
class HubHandlerJabber extends HubHandler
{

    /**
     * Constructor
     *
     * @param string  $jid      JID
     * @param string  $password user's password
     * @param string  $node     node name
     * @param string  $handler  class name of the handler
     * @param array   $params   parameters needed by the handler
     * @param integer $id       internal handler id
     *
     * @return void
     */
    public function __construct($jid, $password, $node, $handler, $params, $id = null) {
        parent::__construct($jid, $password, $node, $handler, $params, $id);
        // This class may be instanciated dynamicaly, so called without parameters
        if (func_num_args() > 0) {
            parent::__construct($jid, $password, $node, $handler, $params, $id);

            // Initialize fields
            $this->fields['to']      = new XepFormField('to', null, XepFormField::FIELD_TYPE_TEXTSINGLE, true, 'To');
            $this->fields['subject'] = new XepFormField('subject', null, XepFormField::FIELD_TYPE_TEXTSINGLE, true, 'Subject');

            $this->paramsCheck($params);
        }
    }

    /**
     * Send the event by email
     *
     * @param string $event the event
     *
     * @return HubHandler this
     */
    public function handle($event) {
        $conn = XMPP2::quickConnect($this->jid . '/' . uniqid('HubBot'), $this->password);
        $this->log(sprintf("Send message from %s to %s : %s", $this->jid, $this->params['to'], $this->params['subject']), BbLogger::DEBUG, 'hub');
        $conn->message($this->params['to'], $event, 'normal', $this->params['subject'], $event);
        $conn->disconnect();
        return $this;
    }

}