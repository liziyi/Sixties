<?php
/**
 * Light GUI, only allow to subscribe to nodes
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" type="text/css" href="css/blitzer/jquery-ui-1.7.2.custom.css" />
<link rel="stylesheet" type="text/css" href="css/form.css" />
<link rel="stylesheet" type="text/css" href="css/layout.css" />
<link rel="stylesheet" type="text/css" href="css/layout-light.css" />
<title>Test</title>
</head>
<body>
	<div id="header">
        <a class="botton" onclick="gXmpp.connect('');" href="#">Connect</a>
        JID : <span id='user_jid'></span>
	</div>
	<div id="body">
		<div id="col_right" class="ui-widget ui-widget-content">
		    <h2>Messages</h2>
		    <p><em>(click to delete)</em></p>
			<ul id="messages">
			</ul>
		</div>
		<div id="col_center" style="display: none" class="ui-widget ui-widget-content" >
            <h2>Canaux disponibles</h2>
            <table id='nodes_list' summary='List of subscriptions to the node'>
              <thead>
                <tr>
                  <th>Canal</th>
                  <th>&nbsp;</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>
	   </div>
		<br style="clear: both" " />
	</div>
	<div id="footer">
Clochix 2009 Cf <a href="https://labo.clochix.net/projects/show/sixties">Sixties</a>
	</div>
    <div id="main_form"></div>
    <!-- jQuery and UI -->
	<script type="text/javascript" src="js/jquery.js"></script>
	<script type="text/javascript" src="js/jquery-ui-1.7.2.custom.min.js"></script>
    <!-- base64 -->
    <script type="text/javascript" src="js/jquery.base64.js"></script>
    <!-- sixties -->
    <script type="text/javascript" src="js/sixties.js"></script>
    <script type="text/javascript" src="registars.php"></script>
    <!-- init -->
	<script type="text/javascript">/*<![CDATA[*/
	var gNodes = {}
	var gXmpp;
	var gFormStylesheet;
	$(document).ready(function(){
      $('#col_center').show();
      gXmpp = new BbXmpp('../ws/', 'light');
	  $('#main_form').dialog({autoOpen: false, hide: 'puff', modal: true, width: 'auto'})
	  gXmpp.init();
	});
	/*]]>*/</script>
</body>
</html>