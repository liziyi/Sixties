<?php
/**
 * Bot management script
 */
if ($argc != 2) {
    echo "Usage : {$argv[0]} [start|reload]\n";
    exit(1);
}

// Default configuration
$config = array(
    'db_dsn'      => null,
    'db_user'     => null,
    'db_password' => null,
    'connection'  => array(),
);

/**
 * Include configuration file
 */
require_once 'config.inc';

/**
 * Include Hub class
 */
require_once '../../lib/hub/Hub.php';

$logger = BbLogger::get('/tmp/xmpp2.log', BbLogger::DEBUG);

$pidFile = 'hub.pid';

switch ($argv[1]) {
case 'start':
    $repo = new HubRepo($config['db_dsn'], $config['db_user'], $config['db_password']);
    $hub  = new Hub($repo);

    $hub->loadConnections();

    // save current process id
    file_put_contents($pidFile, posix_getpid());

    // Manage signals
    declare(ticks = 1);
    function sig_handler($signo) {
        global $hub;
        echo "SIGNAL reçu : $signo\n";
        $hub->loadConnections();
    }
    pcntl_signal(SIGUSR1, "sig_handler");

    echo "Hub ready...\n";
    $hub->process();
    echo "Hub ended\n";
    break;
case 'reload':
    posix_kill(file_get_contents($pidFile), SIGUSR1);
    break;
default:
    echo "Usage : {$argv[0]} [start|reload]\n";
    exit(1);
}