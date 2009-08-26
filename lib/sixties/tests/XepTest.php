<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(dirname(__FILE__)) . '/XMPP2.php';
require_once 'CommonTest.php';

class Xep_TEST extends Xep
{
    public function commonHandler_test($xml){return $this->commonHandler($xml);}
}
class XepTest extends PHPUnit_Framework_TestCase
{

    public function test_handler() {
        $conn = new XMPP2_TEST('');
        $xep  = new Xep_TEST($conn);
        $data=<<<EOT
<iq type='error'>
  <error code='501' type='cancel'>
    <feature-not-implemented xmlns='urn:ietf:params:xml:ns:xmpp-stanzas'/>
    <unsupported />
  </error>
</iq>
EOT;
        $xml  = $conn->xmlParse($data);
        $res  = $xep->commonHandler_test($xml);
        $this->assertTrue($res instanceof XepResponse);
        $this->assertEquals(XepResponse::XEPRESPONSE_KO, $res->code);
        $this->assertTrue(is_array($res->message));
        $this->assertEquals('501', $res->message['code']);
        $this->assertEquals('cancel', $res->message['type']);
        $this->assertEquals(array('feature-not-implemented', 'unsupported'), $res->message['stanzas']);

    }


}
