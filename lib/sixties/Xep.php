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
 * Common classes
 */
require_once dirname(dirname(__FILE__)) . '/bb/BbCommon.php';

/**
 * Require XMPP library
 */
require_once dirname(__FILE__) . '/XMPP2.php';

/**
 * Xep : parent class for all XEP
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
abstract class Xep extends BbBase
{
    /**
     * @var XMPPHP_XMPP $conn : the current connection
     */
    protected $conn;
    /**
     * Create object and register handlers
     *
     * @param XMPP2 $conn the connexion
     *
     * @return void
     */
    public function __construct(XMPP2 $conn) {
        parent::__construct();
        static $initialized = false;
        $this->conn = $conn;
        if (!$initialized) {
            // Handle errors. Use a static var to prevent this from been called for every Xep object
            $this->conn->addXPathHandler('iq/error', 'commonHandler', $this);
            $initialized = true;
        }
    }

    const EVENT_ERROR = 'xep_event_error';
    const EVENT_OK    = 'xep_event_ok';

    /**
     * Register a common id handler
     *
     * The result of the next IQ request will thrown a $event
     *
     * @param string $event the event to fire
     *
     * @return Xep $this
     */
    protected function addCommonHandler($event) {
        $nextid = $this->conn->getNextId();
        $this->conn->historySet($nextid, 'expected', $event);
        $this->conn->addIdHandler($nextid, 'handlerIdCommon', $this);

        return $this;
    }

    /**
     * This method should be called prior handling result.  It handles errors
     *
     * @param XMPPHP_XMLObj $xml the result
     *
     * @return XepResponse
     */
    public function commonHandler($xml) {
        $this->conn->history($xml);
        if ($xml->attrs['type'] == 'error') {
            $message = array();
            if ($xml->hasSub('error')) {
                $error = $xml->sub('error');
                $query = $xml->sub('query');
                $message['server']  = $xml->attrs['from'];
                $message['ns']      = $query->attrs['xmlns'];
                $message['node']    = $query->attrs['node'];
                $message['code']    = $error->attrs['code'];
                $message['type']    = $error->attrs['type'];
                $message['stanzas'] = array();
                foreach ($error->subs as $sub) $message['stanzas'][] = $sub->name;
                $this->log("Receive error : ({$error->attrs['code']}) {$error->attrs['type']} : " . implode(',', $message['stanzas']), BbLogger::ERROR, 'Xep');
            }
            $res = new XepResponse($message, XepResponse::XEPRESPONSE_KO);
            // Send an event. Client should use this event to prevent listening forever
            $this->conn->event(self::EVENT_ERROR, $res);
            return($res);
        } else {
            return(new XepResponse());
        }
    }

    /**
     * Handle misc request response
     *
     * If response code is OK, throws the event registered in history for this call
     *
     * @param XMPPHP_XMLObj $xml the response
     *
     * @return void
     */
    public function handlerIdCommon(XMPPHP_XMLObj $xml){
        try {
            $res = $this->commonHandler($xml);
            if ($res->code != XepResponse::XEPRESPONSE_KO) {
                $res->message = $xml->toString();
                $event        = $this->conn->historyGet($xml->attrs['id'], 'expected');
                $this->conn->event($event, $res);
            }
        } catch (Exception $e) {
            $res = new XepResponse($e->getMessage(), XepResponse::XEPRESPONSE_KO);
            $this->conn->event(self::EVENT_ERROR, $res);
        }
    }
}

/**
 * XepResponse : Class for the objects returned by every handler
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
class XepResponse extends BbResponse
{
    const XEPRESPONSE_OK = 200;
    const XEPRESPONSE_KO = 500;
}

/**
 * XepException : Exceptions throwns by Xep classes service
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
class XepException extends Exception
{
}