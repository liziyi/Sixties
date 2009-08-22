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
 * wsXepSearch : Interface with the MUC Module (Multi User Chat)
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
class WsXepMuc extends WsXep
{

    /**
     * Create a room
     *
     * Parameters:
     * - server (optionnal) the muc server
     * - room   (required) the room name
     *
     * @param array $params parameters
     *
     * @return XepResponse
     */
    public function roomPost($params) {
        $this->checkparams(array('room'), $params);
        $this->conn->xep('muc')->roomCreate($params['server'], $params['room']);
        $this->process(XepMuc::EVENT_ROOM_CREATED);
        return $this->configurationGet($params);
    }

    /**
     * Get the configuration form
     *
     * Parameters:
     * - server (optionnal) the muc server
     * - room   (required) the room name
     *
     * @param array $params parameters
     *
     * @return XepResponse
     */
    public function configurationGet($params){
        $this->checkparams(array('room'), $params);
        $this->conn->xep('muc')->configurationGet($params['server'], $params['room']);
        return $this->process(XepMuc::EVENT_CONFIG_FORM);
    }
}