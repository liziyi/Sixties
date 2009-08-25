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
 * @subpackage Common
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */

/**
 * BbBase : Base class
 *
 * @category   Library
 * @package    Sixties
 * @subpackage Common
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */
abstract class BbBase
{
    /**
     * @var BbLogger $logger the logger
     */
    private $_logger;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct() {
        // Init the logger
        $this->_logger = bbLogger::get();
    }

    /**
     * Set the logger
     *
     * @param BbLogger $logger a logger
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
     *
     * @return BbBase this
     */
    public function log($message, $severity = BbLogger::INFO) {
        if ($this->_logger) $this->_logger->log($message, $severity);
        else var_dump($message);
        return $this;
    }
}

/**
 * BbLogger : logger
 *
 * @category   Library
 * @package    Sixties
 * @subpackage Common
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */
class BbLogger
{
    const FATAL   = 0;
    const ERROR   = 1;
    const WARNING = 2;
    const INFO    = 3;
    const DEBUG   = 4;

    /**
     * @var BbLogger the logger instance
     */
    protected static $instance;
    /**
     * @var string path of the file to log
     */
    private $_logPath = 'php://output';

    /**
     * @var string maximum severity of messages to log
     */
    private $_level = 2;

    /**
     * @var array labels of the levels
     */
    protected $labels = array();

    /**
     * Constructor
     *
     * Constructor is protected, we use a factory
     *
     * @param string  $path  path of the file (std output is the default)
     * @param integer $level maximum level of info to log
     *
     * @return void
     */
    protected function __construct($path = 'php://output', $level = BbLogger::WARNING) {
        $this->_logPath = $path;
        $this->_level   = $level;

        $this->labels = array(
            self::FATAL   => 'fatal',
            self::ERROR   => 'error',
            self::WARNING => 'warning',
            self::INFO    => 'info',
            self::DEBUG   => 'debug');
    }
    /**
     * Factory
     *
     * @param string  $path  path of the file (std output is the default)
     * @param integer $level maximum level of info to log
     *
     * @return BbLogger
     */
    public static function get($path = 'php://output', $level = BbLogger::WARNING)
    {
        if (empty(self::$instance)) {
            self::$instance = new self($path, $level);
        }
        return self::$instance;
    }
    /**
     * Log a message
     *
     * If $severity is greater than the maximum level, message will be discarded
     *
     * @param string  $message the message
     * @param integer $level   a BbLogger constant
     * @param string  $context additionnal context info
     *
     * @return BbLogger this
     */
    public function log($message, $level = BbLogger::INFO, $context = '') {
        if ($level <= $this->_level) {
            $res = sprintf("[%s][%s][%s] %s\n", date('c'), $this->labels[$level], $context, $message);
            file_put_contents($this->_logPath, $res . "\n", FILE_APPEND);
        }
        return $this;
    }

}

/**
 * BbResponse : Base class for responses
 *
 * @category   Library
 * @package    Sixties
 * @subpackage Common
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 */
abstract class BbResponse
{
    public $code;
    public $message;

    /**
     * Constructor
     *
     * @param mixed   $message the content of the response
     * @param integer $code    ok or ko
     *
     * @return void
     */
    public function __construct($message = '', $code = 200){
        $this->message = $message;
        $this->code    = $code;
    }
}
