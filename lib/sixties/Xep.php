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

require_once dirname(__FILE__) . '/XMPP2.php';

/**
 * Xep : parent class for all XEP
 *
 * @category  Library
 * @package   Sixties
 * @author    Clochix <clochix@clochix.net>
 * @copyright 2009 Clochix.net
 * @license   http://www.gnu.org/licenses/gpl.txt GPL
 * @version   $Id$
 * @link      https://labo.clochix.net/projects/show/sixties
 */
class Xep
{
    /**
     * @var XMPPHP_XMPP conn : the current connection
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
        $this->conn = $conn;
    }

    /**
     * Wrapper to logger
     *
     * @param string  $msg      the message
     * @param integer $runlevel one of XMPPHP_Log::LEVEL_*
     *
     * @return void
     */
    public function log($msg, $runlevel = XMPPHP_Log::LEVEL_INFO) {
        $this->conn->getLog()->log($msg, $runlevel);
    }
}