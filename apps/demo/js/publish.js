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
 * @category   UI
 * @package    Sixties
 * @subpackage Demo
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @link       https://labo.clochix.net/projects/show/sixties
 */

/**
 * Sample publish widget : create a bookmarklet like this to call this script
 *
 * javascript:(function(){if(window.bbPub){bbPub.pub();}else{window.bbPubUrl='http://XXXX/';var s=document.createElement('script');s.src=window.bbPubUrl+'demo/js/publish.js';document.getElementsByTagName('head')[0].appendChild(s);}})();
 * 
 * @category   UI
 * @package    Sixties
 * @subpackage Demo
 * @class      bbXmpp
 * @author     Clochix <clochix@clochix.net>
 * @copyright  2009 Clochix.net
 * @license    http://www.gnu.org/licenses/gpl.txt GPL
 * @version    $Id$
 * @link       https://labo.clochix.net/projects/show/sixties
 * 
 * @param {String} aBaseUrl base URL of the web services
 * @param {String} aContext 'admin' or 'light'
 */

var bbPub = {
    aff: {},
    pub : function pub() {
      var form = '';
      var div   = function(aLabel, aInput, aAttr) {
        var attrs = '';
        $.each(aAttr, function(k,v){attrs+=' bbPub_'+v;});
        return '<div class="bbPub_field '+attrs+'"><label class="bbPub_label">'+aLabel+'</label>'+aInput+'</div>'; 
      };
      var links = '<fieldset><legend> ( - ) </legend>'
        + '<label class="bbPub_sublabel">Lien</label><input type="text" size="60" name="href[]"/>'
        + '<label class="bbPub_sublabel">Titre</label><input type="text" size="60" name="hreftitle[]"/>'
        + '<label class="bbPub_sublabel">Type</label><input type="text" size="10" name="hreftype[]"/>'
        + '<label class="bbPub_sublabel">Langue</label><input type="text" size="10" name="hreflang[]"/>'
        + '</fieldset>';
      var nodes = '';
      $.each(bbPub.aff, function(k,v){
        if ((v.node.substr(0,1) == '/') && (v.affiliation == 'owner' || v.affiliation == 'publisher')) nodes += '<option value="'+v.node+'">'+v.node+'</option>';
      });
      form += div('Nœud', '<select name="node">'+nodes+'</select>', ['required']);
      form += div('Titre', '<input type="text" size="60" name="title"/>', ['required']);
      form += div('Résumé', '<input type="text" size="60" name="summary"/>', ['folded']);
      form += div('Contenu', '<textarea name="content" cols="40" rows="10" />', []);
      form += div('Mots clés (séparés par des virgules)', '<input type="text" size="40" name="category"/>', ['folded']);
      form += div('Liens', links, ['multiple_links']);
      form += div('Licence', '<input type="text" size="60" name="rights"/>', ['folded']);
      form += '<input type="button" value="Publier"  onclick="bbPub.send()"/><input type="button" value="Annuler" onclick="$(\'#bbPub_form\').remove();" />';
      form = '<form class="bbPub_form" id="bbPub_form"><h2 class="bbPub_title">Publier</h2>'+form+'</form>'; 
      var jForm = $(form);
      // make the form draggable
      jForm.draggable({ handle: '.bbPub_title' });
      // insert form
      $('BODY').append(jForm);
      // click on labels toggle their visibility
      $('.bbPub_field > label').click(function(){$(this).nextAll().toggle();});
      $('.bbPub_folded > label').nextAll().hide();
      // add and remove links
      $('.bbPub_multiple_links > label').before($('<span style="float: right; margin-right: 300px">&nbsp;(&nbsp;+&nbsp;)&nbsp;</span>').click(function(){$(this).parent().append($(links)).find('legend');}));
      $('.bbPub_multiple_links legend').live('click', function(){jQuery(this).parent().remove();});
      // init values
      $('.bbPub_multiple_links input[name=href\\[\\]]').eq(0).val(document.URL);
      $('.bbPub_multiple_links input[name=hreftitle\\[\\]]').eq(0).val(document.title);
      $('.bbPub_form input[name=title]').eq(0).val(document.title);
      $('.bbPub_form textarea[name=content]').eq(0).val(window.getSelection().toString());
    },
    send: function send() {
      var data=[];
      $.each(jQuery('#bbPub_form *[name]'), function(k,v){a=$(v);data.push(a.attr('name')+'='+a.val());});
      $.ajax({
        type: 'GET',
        url: bbPubUrl + 'ws/pubsub/atom',
        data: '__method=POST&'+data.join('&'),
        dataType: 'jsonp',
        success: function(res){
          if (res['code'] != '200' || typeof res['message'] == 'undefined') {
            if (typeof res['message'] == 'string') $('bbPub_message').text('Erreur : ' + res['message']).show();
            else $('bbPub_message').text('Erreur').show();
          } else if (res.message['code'] != '200' || typeof res.message.message == 'undefined') {
            if (typeof res.message.message == 'string') $('bbPub_message').text('Erreur : ' + res.message.message).show();
            else $('bbPub_message').text('Erreur').show();
          } else {
            $('#bbPub_form').remove();
          }
        }
      });
    }
};

// load jquery
bbPub.s        = document.createElement('script');
bbPub.s.src    = bbPubUrl + 'demo/js/jquery-publish.js';
bbPub.s.onload = function(){
  // Load available nodes
  $.ajax({
    type: 'GET',
    url: bbPubUrl + 'ws/pubsub/affiliation',
    data: {},
    dataType: 'jsonp',
    success: function(res){
      if (res['code'] != '200' || typeof res['message'] == 'undefined') {
        if (typeof res['message'] == 'string') $('bbPub_message').text('Erreur : ' + res['message']).show();
        else $('bbPub_message').text('Erreur').show();
      } else if (res.message['code'] != '200' || typeof res.message.message == 'undefined') {
        if (typeof res.message.message == 'string') $('bbPub_message').text('Erreur : ' + res.message.message).show();
        else $('bbPub_message').text('Erreur').show();
      } else {
        bbPub.aff = res.message.message;
      }
      bbPub.pub();
    }
});
};
bbPub.c = document.createElement('style');
bbPub.c.innerHTML = ".bbPub_form{border:1px solid #888;background-color:#EEE;width:400px;position:absolute;top:10px;left:50%;margin-left:-200px;padding:5px;font-size:8pt;-moz-border-radius:10px;}\n"
  + ".bbPub_title{margin:-5px -5px 5px -5px;padding:5px;border-bottom: 1px solid #888;}\n"
  + ".bbPub_label{display:block;text-align:left;}\n"
//  + ".bbPub_sublabel{display:block;width: 100px; float: left;text-align:left;}\n"
  + ".bbPub_field {margin:2px 0;}"
  + ".bbPub_field textarea{width:372px}"
  + ".bbPub_field fieldset label {padding: 5px}"
  + ".bbPub_field fieldset * {float:left;}"
;
document.getElementsByTagName('head')[0].appendChild(bbPub.c);
document.getElementsByTagName('head')[0].appendChild(bbPub.s);
