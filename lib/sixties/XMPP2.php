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

require_once dirname(dirname(__FILE__)) . '/vendors/xmpphp/XMPPHP/XMPP.php';
require_once dirname(__FILE__) . '/Command.php';
require_once dirname(__FILE__) . '/Discover.php';
require_once dirname(__FILE__) . '/Form.php';
require_once dirname(__FILE__) . '/Pubsub.php';

/**
 * XMPP2 : some extends to XMPPHP_XMPP for our use
 *
 * @category  Library
 * @package   Sixties
 * @author    Clochix <clochix@clochix.net>
 * @copyright 2009 Clochix.net
 * @license   http://www.gnu.org/licenses/gpl.txt GPL
 * @version   $Id$
 * @link      https://labo.clochix.net/projects/show/sixties
 */
class XMPP2 extends XMPPHP_XMPP
{

    /**
     * @var boolean should we display raw XML sent and received ?
     */
    public $logXml = false;

    /**
     * @var boolean set to true only for unit tests purpose
     */
    public $debugMode = false;

    /**
     * Array to to history of requests and responses
     */
    public $history = array();

    /**
     * @var array xep array of available extentions
     */
    protected $xep = array();

    /**
     * Constructor
     *
     * @param string  $host     host
     * @param integer $port     post
     * @param string  $user     user
     * @param string  $password password
     * @param string  $resource ressource
     * @param string  $server   server
     * @param boolean $printlog print the logs ?
     * @param string  $loglevel log level
     */
    public function __construct($host, $port = 5222, $user = '', $password = '', $resource = 'XMPPHP', $server = null, $printlog = false, $loglevel = null) {
        parent::__construct($host, $port, $user, $password, $resource, $server, $printlog, $loglevel);

        $this->addXPathHandler('{jabber:client}message', 'handlerFormMessage', $this);
        $this->xep['command']  = new XepCommand($this);
        $this->xep['discover'] = new XepDiscover($this);
        $this->xep['pubsub']   = new XepPubsub($this);
    }

    /**
     * Return the current connexion host
     *
     * @return string
     */
    public function getHost() {
        return $this->host;
    }
    /**
     * Return the last used Id
     *
     * @return integer
     */
    public function getLastId() {
        return $this->lastid;
    }
    /**
     * Get the Jid of the current user
     *
     * @return string
     */
    public function getBaseJid() {
        return $this->basejid;
    }

    /**
     * Return a service
     *
     * @param string $service service type
     *
     * @return Xep the service
     */
    public function xep($service) {
        return $this->xep[$service];
    }

    /**
     * Send a query to the server
     *
     * @param array $params hashmap of parameters. Requerid : msg; optionnal : to and type (get or set)
     *
     * @throws XMPPHP_Exception
     *
     * @return : void
     */
    public function sendIq($params){
        $res = $this->send(sprintf('<iq id="%s" from="%s" to="%s" type="%s">%s</iq>',
            $this->getId(),
            $this->fulljid,
            ($params['to']?$params['to']:$this->host),
            ($params['type']?$params['type']:'get'),
            $params['msg']));
        $this->history[$this->getLastId()] = array(
            'sent' => time(),
            'type' => 'iq'
        );
        if ($res === false) throw new XMPPHP_Exception("Error sending iq");
    }

    /**
     * Send a data form
     *
     * @param string  $to   receiver
     * @param XepForm $form the form
     *
     * @return XMPP2 $this
     */
    public function sendForm($to, XepForm $form) {
        $res = $this->send(sprintf('<message from="%s" to="%s" id="%s">%s</message>',
            $this->fulljid,
            $to,
            $this->getId(),
            (string)$form));

        $this->history[$this->getLastId()] = array(
            'sent' => time(),
            'type' => 'message'
        );
        if ($res === false) throw new XMPPHP_Exception("Error sending message");
    }

    /**
     * Log a result in the history
     *
     * @param XMPPHP_XMLObj $xml the result
     *
     * @return $this
     */
    public function history(XMPPHP_XMLObj $xml) {
        if ($xml->attrs['id']) {
            $this->history[$xml->attrs['id']]['received'] = time();
        }
        return $this;
    }

    /**
     * Nice dump of XML data
     *
     * @param string $string the string containing the XML
     * @param string $title  a title to display
     *
     * @return void
     */
    public function xmlDump($string, $title = '') {
        if ($this->logXml) {
            $string = trim($string);
            if (!empty($string)) {
                $string = "<xml>$string</xml>";
                if ($title) echo "\n\n========== $title ==========\n";
                $doc = new DOMDocument('1.0');
                if (@$doc->loadXML($string)) {
                    $doc->preserveWhiteSpace = false;
                    $doc->formatOutput = true;
                    echo $doc->saveXML();
                } else {
                    echo "$string\n";
                }
            }
        }
    }

    /******************************************************************************************************************
     *
     * Handlers
     *
     *****************************************************************************************************************/

    /**
     * Handle form messages
     *
     * @param XMPPHP_XMLObj $xml the response
     *
     * @return void
     */
    public function handlerFormMessage(XMPPHP_XMLObj $xml) {
        $this->history($xml);
        if ($xml->hasSub('x')) {
            $form = new XepForm($xml->sub('x'));
            $this->event('form_message_handled', $form);
        }
    }


}