<?php
$basepath = dirname(dirname(dirname(dirname(__FILE__))));

require_once 'PHPUnit/Framework.php';

require_once dirname(dirname(__FILE__)) . '/BbRest.php';
require_once dirname(dirname(__FILE__)) . '/config.inc';

$basepath = dirname(dirname(dirname(dirname(__FILE__))));
$path = array();
$path[] = get_include_path();
$path[] = $basepath . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'sixties';
$path[] = $basepath . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'ws';
set_include_path(implode(PATH_SEPARATOR, $path));

class BbRestTest extends PHPUnit_Framework_TestCase {
    public function test_nego() {
        $_SERVER['HTTP_ACCEPT']= 'text/html ; q=1,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $ws = new BbRest(array(), array());
        $this->assertEquals('xml', $ws->format);
        $_SERVER['HTTP_ACCEPT']= 'application/json, text/xml';
        $ws = new BbRest(array(), array());
        $this->assertEquals('json', $ws->format);
        $_SERVER['HTTP_ACCEPT']= 'text/XML,application/json';
        $ws = new BbRest(array(), array());
        $this->assertEquals('xml', $ws->format);
    }

    public function test_options() {
        global $mapping;
        global $config;

        $_SERVER['HTTP_ACCEPT']    = 'application/json';

        $ws = new BbRest($mapping, null);
        $ws->setConfig(null);

        $_SERVER['PHP_SELF']       = '';
        $_SERVER['REQUEST_URI']    = '';
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
/*
        ob_start();
        $ws->handle();
        $res = json_decode(ob_get_clean());
        $this->assertEquals(200, $res->code);
        $this->assertEquals(array_keys($mapping), $res->message);
*/
        $i = 0;
        foreach (array_keys($mapping) as $module) {
            $_SERVER['REQUEST_URI']    = '/' . $module;
            ob_start();
            $ws->handle();
            $res = json_decode(ob_get_clean());
            $this->assertEquals(200, $res->code);
            $i++;
        }
        $this->assertEquals(count(array_keys($mapping)), $i);
        die();
    }

    public function test_info() {
        global $mapping;
        global $config;
        $_SERVER['PHP_SELF']       = '';
        $_SERVER['REQUEST_URI']    = '/disco/info';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_ACCEPT']    = 'application/json';
        $ws = new BbRest($mapping, $config);

        // default server
        ob_start();
        $ws->handle();
        $xml = json_decode(ob_get_clean());
        $res = $xml->message;
        $this->assertEquals('200', (string)$xml->code);
        $this->assertEquals(200, $res->code);
        $server = $config['server'] . '!';
        $this->assertFalse(is_null($res->message->$server));
        $this->assertFalse(is_null($res->message->$server->identities));
        $this->assertFalse(is_null($res->message->$server->features));

        // Wrong server
        $_GET['server'] = 'foobar';
        ob_start();
        $ws->handle();
        $xml = json_decode(ob_get_clean());
        $res = $xml->message;
        $server = $config['server'] . '!';
        $this->assertEquals(500, $res->code);
        $this->assertEquals('404', $res->message->code);
        $this->assertEquals('cancel', $res->message->type);
        $this->assertEquals(array('remote-server-not-found'), $res->message->stanzas);

        // Sub server
        $_GET['server'] = 'pubsub.' . $config['server'];
        ob_start();
        $ws->handle();
        $xml = json_decode(ob_get_clean());
        $res = $xml->message;
        $server = 'pubsub.' . $config['server'] . '!';
        $this->assertEquals(200, $res->code);
        $this->assertEquals('pubsub', $res->message->$server->identities[0]->category);
        $this->assertEquals('Publish-Subscribe', $res->message->$server->identities[0]->name);
        $this->assertEquals('service', $res->message->$server->identities[0]->type);
    }

    public function test_item() {
        global $mapping;
        global $config;
        $_SERVER['PHP_SELF']       = '';
        $_SERVER['REQUEST_URI']    = '/disco/items';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_ACCEPT']    = 'application/json';
        $ws = new BbRest($mapping, $config);

        // default server
        ob_start();
        $ws->handle();
        $xml = json_decode(ob_get_clean());
        $res = $xml->message;
        $this->assertEquals('200', (string)$xml->code);
//var_dump($res);
/*
        $this->assertEquals($config['server'] . '/', reset(array_keys($res->message)));
        $this->assertEquals(array('identities', 'features'), array_keys(reset($res->message)));

        // Wrong server
        $_GET['server'] = 'foobar';
        ob_start();
        $ws->handle();
        $xml = simplexml_load_string(ob_get_clean());
        $res = unserialize($xml->message);
        $this->assertTrue($res instanceof XepResponse);
        $this->assertEquals(500, $res->code);
        $this->assertEquals(array('code'=>'404', 'type'=>'cancel', 'stanzas'=>array('remote-server-not-found')), $res->message);

        // Sub server
        $_GET['server'] = 'pubsub.' . $config['server'];
        ob_start();
        $ws->handle();
        $xml = simplexml_load_string(ob_get_clean());
        $res = unserialize($xml->message);
        $this->assertTrue($res instanceof XepResponse);
        $this->assertEquals(200, $res->code);
        var_dump($res->message);
*/
    }

}