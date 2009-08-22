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
 * Load CURL extension
 */
if (!extension_loaded('curl')) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        throw new Exception("Using windows is such a bad idea... Check if CURL is available and remove this line");
        dl('ssleay32.dll');
        dl('libeay32.dll');
        dl('php_curl.dll');
    } else {
        dl('curl.so');
    }
}

/**
 * HubHandlerWebhook : call a web hook
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
class HubHandlerWebhook extends HubHandler
{
    /**
     * Constructor
     *
     * @param string  $jid     JID
     * @param string  $node    node name
     * @param string  $handler class name of the handler
     * @param array   $params  parameters needed by the handler
     * @param integer $id      internal handler id
     *
     * @return void
     */
    public function __construct($jid, $node, $handler, $params, $id = null) {
        // This class may be instanciated dynamicaly, so called without parameters
        if (func_num_args() > 0) {
            parent::__construct($jid, $node, $handler, $params, $id);

            // Initialize fields
            $this->fields['url']    = new XepFormField('url', null, XepFormField::FIELD_TYPE_TEXTSINGLE, true, 'url');
            $this->fields['var']    = new XepFormField('var', null, XepFormField::FIELD_TYPE_TEXTSINGLE, true, 'var name');
            $this->fields['method'] = new XepFormField('method', null, XepFormField::FIELD_TYPE_LISTSINGLE, false, 'method');
            $this->fields['method']->setOptions(
                array(
                    array('label'=>'GET', 'value'=>'GET'),
                    array('label'=>'POST', 'value'=>'POST')
                )
            );

            $this->checkParams($params);
        }
    }

    /**
     * Send the event to an url
     *
     * @param string $event the event
     *
     * @return HubHandler this
     */
    public function handle($event) {
        $ch = curl_init();

        $params = array($this->params['var'] => $event);

        switch ($this->params['method']) {
        case 'GET':
            curl_setopt($ch, CURLOPT_URL, $this->params['url'] . '?' . http_build_query($params));
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            break;
        case 'POST':
            curl_setopt($ch, CURLOPT_URL, $this->params['url']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_exec($ch);
        curl_close($ch);

        return $this;
    }

}