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
 * Require base Xep class
 */
require_once dirname(__FILE__) . "/Xep.php";

/**
 * XepSearch : implement part of client-side of XEP 0055 : XMPP Search
 *
 * Only handles search using x-data forms
 *
 * This class uses id rather than XPath handlers, because it's not easy to
 * distinguish between responses with a form and response with results
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
class XepSearch extends Xep
{
    /**
     * Base namespace
     */
    const NS = 'jabber:iq:search';

    /**
     * Event thrown when the form is available
     */
    const EVENT_FORM   = 'search_event_form';

    /**
     * Event thrown when the result of a search is available
     */
    const EVENT_RESULT = 'search_event_result';

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
    }

    /**
     * Get the search form
     *
     * @param string $jid Jid of the server
     *
     * @return XepCommand $this
     */
    public function searchGet($jid) {
        $req = array('to' => $jid, 'msg' => '<query xmlns="' . self::NS . '"/>');
        $this->conn->addIdHandler($this->conn->getNextId(), 'handlerForm', $this);
        $this->conn->sendIq($req);
        return $this;
    }

    /**
     * Get the search form
     *
     * @param string  $jid  Jid of the server
     * @param XepForm $form Form containing the search parameters
     *
     * @return XepCommand $this
     */
    public function searchExecute($jid, $form) {
        $req = array('type' => 'set', 'to' => $jid, 'msg' => '<query xmlns="' . self::NS . '">' . (string)$form . '</query>');
        $this->conn->addIdHandler($this->conn->getNextId(), 'handlerResult', $this);
        $this->conn->sendIq($req);
        return $this;
    }

    /**
     * Handle result of a form request
     *
     * @param XMPPHP_XMLObj $xml the response
     *
     * @return void
     */
    public function handlerForm(XMPPHP_XMLObj $xml){
        try {
            $res = $this->commonHandler($xml);
            if ($res->code != XepResponse::XEPRESPONSE_KO) {
                $res->message = array();
                $query = $xml->sub('query');
                foreach ($query->subs as $sub) {
                    switch ($sub->name) {
                    case 'instructions':
                    case 'first':
                    case 'last':
                    case 'nick':
                    case 'email':
                        //@TODO
                    case 'x':
                        // Data form
                        $res->message['form'] = $sub->toString();
                        break;
                    }
                }
                $this->conn->event(self::EVENT_FORM, $res);
            }
        } catch (Exception $e) {
            $res = new XepResponse($e->getMessage(), XepResponse::XEPRESPONSE_KO);
            $this->conn->event(self::EVENT_ERROR, $res);
        }
    }

    /**
     * Handle result of a search
     *
     * @param XMPPHP_XMLObj $xml the response
     *
     * @return void
     */
    public function handlerResult(XMPPHP_XMLObj $xml){
        try {
            $res = $this->commonHandler($xml);
            if ($res->code != XepResponse::XEPRESPONSE_KO) {
                $res->message = array();
                $query = $xml->sub('query');
                foreach ($query->subs as $sub) {
                    switch ($sub->name) {
                    case 'x':
                        // Data form
                        $res->message['form'] = $sub->toString();
                        break;
                    default:
                        //@TODO
                    }
                }
                $this->conn->event(self::EVENT_RESULT, $res);
            }
        } catch (Exception $e) {
            $res = new XepResponse($e->getMessage(), XepResponse::XEPRESPONSE_KO);
            $this->conn->event(self::EVENT_ERROR, $res);
        }
    }

}