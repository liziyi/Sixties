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
 * wsXepCommand : Interface with the Command Module
 *
 * !!! Simple mockup, will probably be rewrited
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
class WsXepCommand extends WsXep
{

    /**
     * Execute a command
     *
     * Parameters:
     * - node      (required)
     * - action    (optionnal)
     * - sessionid (optionnal)
     * - to        (optionnal)
     * - form      (optionnal) array of parameters of the command
     *
     * @param array $params parameters
     *
     * @return XepResponse
     */
    public function executePost($params) {
        $this->checkparams(array('node'), $params);
        $form = $this->formLoad($params['form']);
        $this->conn->xep('command')->execute($params['node'], $params['action'], $params['sessionid'], $form, $params['to']);
        return $this->process('command_event_command');
    }
}