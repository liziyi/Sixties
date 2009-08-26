<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(dirname(__FILE__)) . '/XMPP2.php';
require_once 'CommonTest.php';

class XepFormTest extends PHPUnit_Framework_TestCase
{

    public function test_load_params1() {
        $conn = new XMPP2_TEST('');

        $xml    = $conn->xmlParse("<toto><x /></toto>");
        $result = '';
        try {
            XepForm::load($xml);
        } catch (Exception $e) {
            $result = $e->getMessage();
        }
        $this->assertEquals("Xep_Form::load : wrong message", $result);
    }

    public function test_load_params2() {
        $conn = new XMPP2_TEST('');

        $xml    = $conn->xmlParse("<message><body>toto</body></message>");
        $result = '';
        try {
            XepForm::load($xml);
        } catch (Exception $e) {
            $result = $e->getMessage();
        }
        $this->assertEquals("Xep_Form::load : wrong message", $result);
    }

    public function test_load_message() {
        $conn = new XMPP2_TEST('');
        $data = <<<EOT
<message>
  <x xmlns='jabber:x:data' type='form'>
    <title>Title of my form</title>
    <instructions>instructions 1</instructions>
    <instructions>instructions 2</instructions>
    <field var='FORM_TYPE' type='hidden'>
      <value>http://jabber.org/protocol/pubsub#subscribe_authorization</value>
    </field>
    <field var='pubsub#node' type='text-single' label='Node ID'>
      <value>/home/larzac.org/toto/cleub1</value>
    </field>
    <field var='pubsub#subscriber_jid' type='jid-single' label='Subscriber Address'>
      <value>clochix@larzac.org</value>
    </field>
    <field var='pubsub#allow' type='boolean' label='Allow this Jabber ID to subscribe to this pubsub node?'>
      <value>false</value>
    </field>
  </x>
<message>
EOT;
        $xml    = $conn->xmlParse($data);
        $result = XepForm::load($xml);
        $this->assertTrue($result instanceof XepForm);
        $this->assertEquals('Title of my form', $result->getTitle());
        $this->assertEquals(array('instructions 1', 'instructions 2'), $result->getInstructions());
        $fields = $result->getFields();
        $this->assertEquals(4, count($fields));
    }
}
