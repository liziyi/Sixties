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
 * @category   Application
 * @package    Sixties
 * @subpackage Ws
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @link       https://labo.clochix.net/projects/show/sixties
 */


// Set custom timeout
ini_set('max_execution_time', 120);

$basepath = dirname(dirname(dirname(__FILE__)));

require_once $basepath . '/lib/ws/BbRest.php';

$path = array();
$path[] = get_include_path();
$path[] = $basepath . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'hub';
$path[] = $basepath . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'sixties';
$path[] = $basepath . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'ws';
set_include_path(implode(PATH_SEPARATOR, $path));

//@TODO : implement security
$mapping = array(
    'command' => 'WsXepCommand',
    'disco'   => 'WsXepDisco',
    'muc'     => 'WsXepMuc',
    'pubsub'  => 'WsXepPubsub',
    'search'  => 'WsXepSearch',
    'hub'     => 'WsHub'
);
$config = array(
    'host'     => '???',
    'port'     => 5222,
    'user'     => '???',
    'password' => '???',
    'server'   => ''
);
$config['db_dsn']  = '';
$config['db_user'] = null;
$config['db_pass'] = null;

// Override default config !
require_once 'config.inc';

if ($_SERVER['PHP_AUTH_USER']) {
    $config['user']     = $_SERVER['PHP_AUTH_USER'];
    $config['password'] = $_SERVER['PHP_AUTH_PW'];
}
$logger = BbLogger::get('/tmp/xmpp.log', BbLogger::DEBUG);

$rest = new BbRest($mapping, $config);

$rest->handle();