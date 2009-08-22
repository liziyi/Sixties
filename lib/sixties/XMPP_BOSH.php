<?php
/**
 * XMPPHP: The PHP XMPP Library
 * Copyright (C) 2008  Nathanael C. Fritz
 * This file is part of SleekXMPP.
 *
 * XMPPHP is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * XMPPHP is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with XMPPHP; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category  Xmpphp
 * @package   XMPPHP
 * @author    Nathanael C. Fritz <JID: fritzy@netflint.net>
 * @author    Stephan Wentz <JID: stephan@jabber.wentz.it>
 * @author    Michael Garvin <JID: gar@netflint.net>
 * @copyright 2008 Nathanael C. Fritz
 */

/**
 * Require base Xep class
 */
require_once dirname(__FILE__) . "/XMPP2.php";

/**
 * XMPPHP Main Class
 *
 * @category   Library
 * @package    Sixties
 * @subpackage XMPPHP
 * @author     Nathanael C. Fritz <JID: fritzy@netflint.net>
 * @author     Stephan Wentz <JID: stephan@jabber.wentz.it>
 * @author     Michael Garvin <JID: gar@netflint.net>
 * @copyright  2008 Nathanael C. Fritz
 * @version    $Id$
 */
class XMPPHP_BOSH extends XMPP2 {

        protected $rid;
        protected $sid;
        protected $http_server;
        protected $http_buffer = Array();
        protected $session = false;
        protected $lastactivity = 0;
        protected $reconnect = 0;

        const MAX_INACTIVITY = 30;

        /**
         * Connect to the server
         *
         * @param string  $server  http server
         * @param integer $wait    parameter for BOSH
         * @param boolean $session store connection into PHP session ?
         */
        public function connect($server, $wait='1', $session=false) {
            $this->http_server = $server;
            $this->use_encryption = false;
            $this->session = $session;

            $this->rid = 3001;
            $this->sid = null;
            if($session)
            {
                $this->loadSession();
            }
            if(!$this->sid) {
                $body = $this->__buildBody();
                $body->addAttribute('hold', '1');
                $body->addAttribute('to', $this->host);
                $body->addAttribute('route', "xmpp:{$this->host}:{$this->port}");
                $body->addAttribute('secure', 'true');
                $body->addAttribute('xmpp:version', '1.0', 'urn:xmpp:xbosh');
                $body->addAttribute('wait', strval($wait));
                $body->addAttribute('ack', '1');
                $body->addAttribute('inactivity', self::MAX_INACTIVITY);
                $buff = "<stream:stream xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams'>";
                xml_parse($this->parser, $buff, false);
                $response = $this->__sendBody($body);
                $rxml = new SimpleXMLElement($response);
                $this->sid = $rxml['sid'];

            } else {
                $buff = "<stream:stream xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams'>";
                xml_parse($this->parser, $buff, false);
            }
        }

        /**
         * Send a request to the server
         *
         * @param SimpleXMLElement $body the request to send
         *
         * @return string server response as string
         */
        protected function __sendBody($body=null) {
            if(!$body) {
                $body = $this->__buildBody();
            }
            //@FIXME ejabberd seems to have problems with NS
            $input = str_replace('default:', '', $body->asXML());

            $ch = curl_init($this->http_server);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $header = array('Accept-Encoding: gzip, deflate', 'Content-Type: text/xml; charset=utf-8');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header );
            curl_setopt($ch, CURLOPT_VERBOSE, 0);
            $output = '';
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $this->xmlDump($input, "SENT");
            $output = curl_exec($ch);
            $this->xmlDump($output, "RECEIVED");
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $res = substr($code, 0, 1);
            if ($res == '4' || $res == '5') {
                $this->log->log("SERVER returns $code, try to reconnect",  XMPPHP_Log::LEVEL_ERROR);
                $this->sid = null;
                if ($this->reconnect < 5) {
                    $this->reconnect++;
                    //$this->connect($this->http_server, 1, false);
                } else {
                    $this->log->log("Reconnect uncessfull, abort",  XMPPHP_Log::LEVEL_ERROR);
                }
            }
            $this->lastactivity = time();
            $this->http_buffer[] = $output;
            curl_close($ch);
            return $output;
        }

        /**
         * Create a body element
         *
         * @param XMPPHP_XMLObj $xml datas
         *
         * @return SimpleXMLElement
         */
        protected function __buildBody($sub=null) {
            $xml = new SimpleXMLElement("<body xmlns='http://jabber.org/protocol/httpbind' xmlns:xmpp='urn:xmpp:xbosh' xmlns:stream='http://etherx.jabber.org/streams' />");
            $xml->addAttribute('content', 'text/xml; charset=utf-8');
            $xml->addAttribute('rid', $this->rid);
            $this->rid += 1;
            if($this->sid) $xml->addAttribute('sid', $this->sid);
            //if($this->sid) $xml->addAttribute('xmlns', 'http://jabber.org/protocol/httpbind');
            $xml->addAttribute('xml:lang', 'en');
            if($sub) { // ok, so simplexml is lame
                $p = dom_import_simplexml($xml);
                $c = dom_import_simplexml($sub);
                $cn = $p->ownerDocument->importNode($c, true);
                $p->appendChild($cn);
                $xml = simplexml_import_dom($p);
            }
            return $xml;
        }

        /**
         * Process
         *
         * @return void
         */
        public function __process() {
            if($this->http_buffer) {
                $this->__parseBuffer();
            } else {
                $this->__sendBody();
                $this->__parseBuffer();
            }
        }

        /**
         * Parse the current http buffer
         *
         * @return void
         */
        protected function __parseBuffer() {
            while ($this->http_buffer) {
                $idx = key($this->http_buffer);
                $buffer = $this->http_buffer[$idx];
                unset($this->http_buffer[$idx]);
                if($buffer) {
                    $xml = new SimpleXMLElement($buffer);
                    $children = $xml->xpath('child::node()');
                    foreach ($children as $child) {
                        $buff = $child->asXML();
                        $this->log->log("RECV: $buff",  XMPPHP_Log::LEVEL_VERBOSE);
                        xml_parse($this->parser, $buff, false);
                    }
                }
            }
        }

        /**
         * Send a request
         *
         * @param string $msg message to send, as XML string
         *
         * @return void
         */
        public function send($msg) {
            $this->log->log("SEND: $msg",  XMPPHP_Log::LEVEL_VERBOSE);
            $msg = new SimpleXMLElement($msg);
            //$msg->addAttribute('xmlns', 'jabber:client');
            $this->__sendBody($this->__buildBody($msg));
            //$this->__parseBuffer();
        }

        /**
         * Reset connection
         *
         * @return void
         */
        public function reset() {
            $this->xml_depth = 0;
            unset($this->xmlobj);
            $this->xmlobj = array();
            $this->setupParser();
            //$this->send($this->stream_start);
            $body = $this->__buildBody();
            $body->addAttribute('to', $this->host);
            $body->addAttribute('xmpp:restart', 'true', 'urn:xmpp:xbosh');
            $buff = "<stream:stream xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams'>";
            $response = $this->__sendBody($body);
            $this->been_reset = true;
            xml_parse($this->parser, $buff, false);
        }

        /**
         * Load connection from PHP session
         *
         * @return void
         */
        protected function loadSession() {
            if (   !isset($_SESSION['XMPPHP_BOSH_lastactivity'])
                || (time() - (int)$_SESSION['XMPPHP_BOSH_lastactivity'] > self::MAX_INACTIVITY ))
                return;

            if(isset($_SESSION['XMPPHP_BOSH_RID'])) $this->rid = $_SESSION['XMPPHP_BOSH_RID'];
            if(isset($_SESSION['XMPPHP_BOSH_SID'])) $this->sid = $_SESSION['XMPPHP_BOSH_SID'];
            if(isset($_SESSION['XMPPHP_BOSH_authed'])) $this->authed = $_SESSION['XMPPHP_BOSH_authed'];
            if(isset($_SESSION['XMPPHP_BOSH_jid'])) $this->jid = $_SESSION['XMPPHP_BOSH_jid'];
            if(isset($_SESSION['XMPPHP_BOSH_fulljid'])) $this->fulljid = $_SESSION['XMPPHP_BOSH_fulljid'];
            if(isset($_SESSION['XMPPHP_BOSH_lastactivity'])) $this->lastactivity = $_SESSION['XMPPHP_BOSH_lastactivity'];
        }

        /**
         * Save current connection to PHP session
         *
         * @return void
         */
        public function saveSession() {
            $_SESSION['XMPPHP_BOSH_RID']          = (string) $this->rid;
            $_SESSION['XMPPHP_BOSH_SID']          = (string) $this->sid;
            $_SESSION['XMPPHP_BOSH_authed']       = (boolean) $this->authed;
            $_SESSION['XMPPHP_BOSH_jid']          = (string) $this->jid;
            $_SESSION['XMPPHP_BOSH_fulljid']      = (string) $this->fulljid;
            $_SESSION['XMPPHP_BOSH_lastactivity'] = (string) $this->lastactivity;
        }
}
