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
 * We use XepFormFields
 */
require_once dirname(dirname(__FILE__)) . '/sixties/XepForm.php';

/**
 * HubHandler : Base class for all handlers
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
class HubHandler
{
    /**
     * @var string $id internal handler id
     */
    protected $id;
    /**
     * @var string $jid
     */
    protected $jid;
    /**
     * @var string $node
     */
    protected $node;
    /**
     * @var string $handler class name of the handler
     */
    protected $handler;
    /**
     * @var array $params parameters of by the handler
     */
    protected $params;
    /**
     * @var array $fields parameters needed by the handler
     */
    protected $fields = array();

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
        $this->id      = $id;
        $this->jid     = $jid;
        $this->node    = $node;
        $this->handler = $handler;
        $this->params  = $params;
    }

    /**
     * Get the internal id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }
    /**
     * Set the internal id
     *
     * @param integer $id the internal id
     *
     * @return HubHandler this
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }
    /**
     * Get the user JID
     *
     * @return string
     */
    public function getJid() {
        return $this->jid;
    }
    /**
     * Set the user JID
     *
     * @param string $jid the user JID
     *
     * @return HubHandler this
     */
    public function setJid($jid) {
        $this->jid = $jid;
        return $this;
    }
    /**
     * Get the node name
     *
     * @return string
     */
    public function getNode() {
        return $this->node;
    }
    /**
     * Set the node name
     *
     * @param string $node the node name
     *
     * @return HubHandler this
     */
    public function setNode($node) {
        $this->node = $node;
        return $this;
    }
    /**
     * Get the handler class name
     *
     * @return string
     */
    public function getHandler() {
        return $this->handler;
    }
    /**
     * Set the handler class name
     *
     * @param string $handler the handler class name
     *
     * @return HubHandler this
     */
    public function setHandler($handler) {
        $this->handler = $handler;
        return $this;
    }
    /**
     * Get the handler parameters
     *
     * @return array
     */
    public function getParams() {
        return $this->params;
    }
    /**
     * Set the handler parameters
     *
     * @param array $params the handler parameters
     *
     * @return HubHandler this
     */
    public function setParams($params) {
        $this->params = $params;
        return $this;
    }

    /**
     * Check the presence of mandatory parameters
     *
     * @param array $actual the actual parameters
     *
     * @throws 400 on error
     *
     * @return boolean
     */
    protected function checkparams($actual) {
        if (!is_array($actual)) $actual = array();
        foreach ($this->fields as $paramkey => $paramval) {
            if (!isset($actual[$paramkey])/* && $paramval->getRequired()*/) {
                throw new WsException("Missing parameter $paramkey ", 400);
            }
        }
        return true;
    }

    /**
     * Handle an event
     *
     * @param string $event the event
     *
     * @return HubHandler this
     */
    public function handleEvent($event) {
        throw new Exception("Method handleEvent must be overridden");
    }

    /**
     * Get list of available handlers
     *
     * @return array of class names
     */
    final static public function handlersGet() {
        return ('Webhook');
    }

    /**
     * Load a custom handler
     *
     * @param string $class  class name
     * @param array  $params parameters for the constructor
     *
     * @return HubHandler
     */
    final static public function handlerLoad($class, $params) {
        $handler = null;
        $classname = 'HubHandler' . ucfirst($class);
        if (!class_exists($classname)) {
            // Try to load the class
            $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . $classname . '.php';
            if (file_exists($filename)) {
                include_once $filename;
            }
        }
        if (class_exists($classname)) {
            $handler = @new $classname();
            call_user_func_array(array($handler, '__construct'), $params);
        } else {
            //@TODO
        }
        return $handler;
    }
}