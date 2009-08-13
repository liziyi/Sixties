<?php
// Reduce timeout
ini_set('max_execution_time', 5);

$basepath = dirname(dirname(dirname(__FILE__)));

require_once 'BbRest.php';

$path = array();
$path[] = get_include_path();
$path[] = $basepath . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'sixties';
$path[] = $basepath . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'ws';
set_include_path(implode(PATH_SEPARATOR, $path));

//@TODO : implement security
$mapping = array(
    'disco'   => 'WsXepDisco',
    'pubsub'  => 'WsXepPubsub',
    'command' => 'WsXepCommand'
);
$config = array(
    'host'     => '???',
    'port'     => 5222,
    'user'     => '???',
    'password' => '???',
    'server'   => ''
);
// Override default config !
require_once 'config.inc';

$rest = new BbRest($mapping, $config);
$rest->handle();