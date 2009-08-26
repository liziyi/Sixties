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
 * Require base XMPPHP_XMPP class
 */
require_once dirname(dirname(__FILE__)) . '/XMPPHP/XMPP.php';
/**
 * Require base Xep class
 */
require_once dirname(__FILE__) . '/Xep.php';
/**
 * Require XepForm class
 */
require_once dirname(__FILE__) . '/XepForm.php';

/**
 * XMPP2 : some extends to XMPPHP_XMPP for our use
 *
 * @category   Library
 * @package    Sixties
 * @subpackage XMPPHP
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */
class XMPP2 extends XMPPHP_XMPP
{
    /**
     * @var BbLogger $logger the logger
     */
    private $_logger;

    /**
     * @var boolean should we display raw XML sent and received ?
     */
    public $logXml = false;

    /**
     * @var boolean set to true only for unit tests purpose
     */
    public $debugMode = false;

    /**
     * @var array history of requests and responses
     */
    protected $history = array();

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
        $this->_logger = bbLogger::get();

        parent::__construct($host, $port, $user, $password, $resource, $server, $printlog, $loglevel);

        $this->addXPathHandler('{jabber:client}message', 'handlerFormMessage', $this);

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
     * Return the current connexion port
     *
     * @return string
     */
    public function getPort() {
        return $this->port;
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
     * Get the next Jid
     *
     * Yes, I know, it's quite useful
     *
     * @return integer
     */
    public function getNextId() {
        return $this->lastid + 1;
    }

    /**
     * Get the login of the current user
     *
     * @return string
     */
    public function getLogin() {
        return $this->user;
    }

    /**
     * Get the bare JID of the current user
     *
     * @return string
     */
    public function getBareJid() {
        return $this->basejid;
    }

    /**
     * Get the full JID of the current user (with the resource)
     *
     * @return string
     */
    public function getFullJid() {
        return $this->fulljid;
    }

    /**
     * Connect to a Jabber server with minimal parameters
     *
     * @param string $jid      bare (user@server) or full (user@server/resource) JID
     * @param string $password password
     *
     * @return XMPP2
     */
    public function quickConnect($jid, $password) {
        $tmp = explode('@', $jid);
        if (count($tmp) != 2) {
            $this->log("Wrong jid $jid", BbLogger::ERROR, 'XMPP2');
            throw new XMPPHP_Exception("Wrong jid $jid");
        }
        $user     = $tmp[0];
        $tmp2     = explode('/', $tmp[1]);
        $server   = $tmp2[0];
        $resource = (count($tmp2) == 2 ? $tmp2[1] : uniqid('XMPPHP'));
        $conn = new self($server, '5222', $user, $password, $resource);
        $conn->connect();
        $conn->processUntil('session_start');
        return $conn;
    }

    /**
     * Return a service.
     *
     * Services are loaded on demand
     *
     * @param string $service service type
     *
     * @return Xep the service
     */
    public function xep($service) {
        $service = strtolower($service);
        // If the service is not already loaded, try to load it. This avoid
        if (!isset($this->xep[$service])) {
            $classname = 'Xep' . ucfirst($service);
            if (!class_exists($classname)) {
                // Try to load the class
                $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . $classname . '.php';
                if (file_exists($filename)) {
                    include_once $filename;
                    $this->xep[$service] = new $classname($this);
                }
            } else {
                $this->xep[$service] = new $classname($this);
            }
            if (!isset($this->xep[$service])) {
                $this->log("Unable to load XEP $service", BbLogger::ERROR, 'XMPP2');
                throw new XMPPHP_Exception("Enable to load XEP $service");
            }
        }
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
        $lastid = $this->getId();
        $res = $this->send(
            sprintf(
                '<iq id="%s" from="%s" to="%s" type="%s" >%s</iq>',
                $lastid,
                $this->fulljid,
                ($params['to']?$params['to']:$this->host),
                ($params['type']?$params['type']:'get'),
                $params['msg']
            )
        );
        if (!isset($this->history[$lastid])) $this->history[$lastid] = array();
        $this->history[$lastid]['sent'] = time();
        $this->history[$lastid]['type'] = 'iq';
        if ($res === false) throw new XMPPHP_Exception("Error sending iq");
    }

    /**
     * Send a message
     *
     * @param string $to      receiver
     * @param string $message the message
     *
     * @return XMPP2 $this
     */
    public function sendMessage($to, $message) {
        $lastid = $this->getId();
        $res = $this->send(
            sprintf(
                '<message from="%s" to="%s" id="%s">%s</message>',
                $this->fulljid,
                $to,
                $lastid,
                $message
            )
        );
        if (!isset($this->history[$lastid])) $this->history[$lastid] = array();
        $this->history[$lastid]['sent'] = time();
        $this->history[$lastid]['type'] = 'message';
        if ($res === false) throw new XMPPHP_Exception("Error sending message");
    }

    /**
     * Send presence
     *
     * @param string $to      receiver
     * @param string $message the message
     *
     * @return XMPP2 $this
     */
    public function sendPresence($to, $message) {
        $lastid = $this->getId();
        $res = $this->send(
            sprintf(
                '<presence from="%s" to="%s">%s</presence>',
                $this->fulljid,
                $to,
                $message
            )
        );
        $lastid = $this->getLastId();
        if (!isset($this->history[$lastid])) $this->history[$lastid] = array();
        $this->history[$lastid]['sent'] = time();
        $this->history[$lastid]['type'] = 'message';
        if ($res === false) throw new XMPPHP_Exception("Error sending presence");
    }

    /**
     * Log a result in the history
     *
     * @param XMPPHP_XMLObj $xml the result
     *
     * @return XMPP2 $this
     */
    public function history(XMPPHP_XMLObj $xml) {
        if ($xml->attrs['id']) {
            $this->history[$xml->attrs['id']]['received'] = time();
        }
        return $this;
    }

    /**
     * Set a value in the request history
     *
     * @param string $id  the IQ id
     * @param string $key key
     * @param string $val value
     *
     * @return XMPP2 $this
     */
    public function historySet($id, $key, $val) {
        if (!isset($this->history[$id])) $this->conn->history[$id] = array();
        $this->history[$id][$key] = $val;
        return $this;
    }

    /**
     * Get a value from the request history
     *
     * @param string $id  the IQ id
     * @param string $key key
     *
     * @return mixed
     */
    public function historyGet($id, $key) {
        if (!isset($this->history[$id])) return null;
        else return $this->history[$id][$key];
    }

    /**
     * Set the logger
     *
     * @param BbLogger $logger the logger instance
     *
     * @return BbBase this
     */
    public function loggerSet(BbLogger $logger) {
        $this->_logger = $logger;
        return $this;
    }

    /**
     * Get the logger
     *
     * @return BbLogger
     */
    public function loggerGet() {
        return $this->_logger;
    }

    /**
     * Log a message
     *
     * @param string  $message  the message
     * @param integer $severity a BbLogger constant
     * @param string  $context  additionnal context info
     *
     * @return BbBase this
     */
    public function log($message, $severity = BbLogger::INFO, $context = '') {
        if ($this->_logger) $this->_logger->log($message, $severity, $context);
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
                if (substr($string, 0, 2) != '<?') $string = "<xml>$string</xml>";
                $res = '';
                if ($title) $res .= "\n\n========== $title ==========\n";
                $res .= date('c') . "\n\n";
                $doc = new DOMDocument('1.0');
                if (@$doc->loadXML($string)) {
                    $doc->preserveWhiteSpace = false;
                    $doc->formatOutput = true;
                    $res .= $doc->saveXML();
                } else {
                    $res .= "$string\n";
                }
                if (PHP_SAPI != 'cli') {
                    //$res = nl2br(htmlspecialchars($res));
                }
                $this->log($res, BbLogger::DEBUG);
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