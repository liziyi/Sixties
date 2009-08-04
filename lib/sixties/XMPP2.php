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
 * @package   Sixties
 * @category  Library
 * @license   http://www.gnu.org/licenses/gpl.txt GPL
 * @author    Clochix <clochix@clochix.net>
 * @copyright 2009 Clochix.net
 */

require_once $libs . '/vendors/xmpphp/XMPPHP/XMPP.php';

/**
 * XMPP2 : some extends to XMPPHP_XMPP for our use
 *
 * @package   Sixties
 * @category  Library
 * @license   http://www.gnu.org/licenses/gpl.txt GPL
 * @author    Clochix <clochix@clochix.net>
 * @copyright 2009 Clochix.net
 * @version   $Id$
 */
class XMPP2 extends XMPPHP_XMPP {

    public $logXml = false;

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
     * Send a query to the server
     *
     * @param array $params hashmap of parameters. Requerid : msg; optionnal : to and type (get or set)
     *
     * @return : mixed : FALSE or integer
     */
    public function sendIq($params){
        return $this->send(sprintf('<iq id="%s" from="%s" to="%s" type="%s">%s</iq>',
            $this->getId(),
            $this->fulljid,
            ($params['to']?$params['to']:$this->host),
            ($params['type']?$params['type']:'get'),
            $params['msg']
            ));
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


}