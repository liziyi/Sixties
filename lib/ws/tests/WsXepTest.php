<?php
require_once 'PHPUnit/Framework.php';

require_once dirname(dirname(__FILE__)) . '/WsXep.php';
require_once dirname(dirname(__FILE__)) . '/WsXepDisco.php';
require_once dirname(dirname(__FILE__)) . '/WsXepPubsub.php';

require_once dirname(dirname(__FILE__)) . '/../vendors/addendum/annotations.php';
class UrlMap extends Annotation
{
}

class WsXepTest extends PHPUnit_Framework_TestCase
{
    public function test_options() {
        $ws = new WsXepDisco(null);
        $res = $ws->Options();
    }
}