<?php
require_once 'BbRest.php';

$basepath = dirname(dirname(dirname(__FILE__)));
$path = array();
$path[] = get_include_path();
$path[] = $basepath . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'sixties';
$path[] = $basepath . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'ws';
set_include_path(implode(PATH_SEPARATOR, $path));

$mapping = array(
    'disco'  => 'WsXepDisco',
    'pubsub' => 'WsXepPubsub',
);
$config = array(
    'host'     => '',
    'port'     => 5222,
    'user'     => '',
    'password' => '',
    'server'   => ''
);
// Override default config !
require_once 'config.inc';

$rest = new BbRest($mapping, $config);
$rest->handle();