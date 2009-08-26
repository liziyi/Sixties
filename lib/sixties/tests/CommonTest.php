<?php
Class XMPP2_TEST extends XMPP2
{
    public function __construct($host, $port = 5222, $user = '', $password = '', $resource = 'XMPPHP', $server = null, $printlog = false, $loglevel = null) {
        parent::__construct($host, $port, $user, $password, $resource, $server, $printlog, $loglevel);

        $this->disconnected  = true;
        $this->debugMode     = true;
        $this->eventhandlers = array();
        $this->idhandlers    = array();
        $this->nshandlers    = array();
        $this->xpathhandlers = array();
    }

    public function xmlParse($data) {
        $this->xmlobj = array();
        xml_parse($this->parser, $data, false);
        return $this->xmlobj[0];
    }

}
