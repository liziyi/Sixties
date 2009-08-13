function bbXmpp(){
  var _instance   = this;
  var _wsServices = {}

  var _error = function(message) {
    $('#messages').prepend('<li>' + message + '</li>');
  }
  var _handleResponse = function(res, handler, desc) {
    desc = (desc?desc + ' : ': '');
    if (res['code'] != '200' || typeof res.message == 'undefined') {
      _error(desc + "Service error " + res['code']);
    } else if (res.message['code'] != '200' || typeof res.message.message == 'undefined') {
      _error(desc + "Module error " + res.message['code']);
    } else {
      if (desc != '') _error(desc + 'OK');
      if (handler) handler(res);
      else console.log('no handler')
    }

  }
  
  this.cancel = function cancel(id) {
    $('#form_' + id).empty();
  }
  /**
   * Submit a form
   * 
   * @param id the form id
   * 
   * @return void
   */
  this.submit = function submit(id) {
    var data = {};
    // Get the value of all fields
    $('#form_' + id + ' *[name]').each(function(){data[this.name]=this.value})
    // for radio buttons, we need a second pass
    $('#form_' + id + ' input:radio[name]:checked').each(function(){data[this.name]=$(this).val()})
    // Get the method to call
    var method = $('#form_' + id).attr('action');
    console.log(method, data);
    // and call it
    _instance[method](data)
  }
  /**
   * 
   */
  this.discoInfoSuccess = function discoInfoSuccess(res) {
    res = res.message;
    if (res.code == 200) {
      $.each(res.message, function(host, hostval){
        if (!nodes[host]) nodes[host] = {}
        $.each(hostval, function(key, val){
          nodes[host][key] = val;
        })
      })
    }
  }
  /**
   * 
   * @param server
   * @param node
   * @return
   */
  this.discoInfo = function discoInfo(server, node){
    var data = {}
    if (server) data['server'] = server;
    if (node) data['node'] = node;
    $.ajax({
      type: "GET",
      url: "/sixties/ws/disco/info",
      dataType: "json",
      data: data,
      success: function(res){_handleResponse(res, discoInfoSuccess)}
    });
  };
  /**
   * 
   * @param res
   * @return
   */
  this.discoItemsSuccess = function discoItemsSuccess(res){
    res = res.message;
    if (res.code == 200) {
      $.each(res.message, function(host, hostval){
        if (!nodes[host]) nodes[host] = {}
        $.each(hostval, function(key, val){
          nodes[host][key] = val;
        })
      })
    }
  }
  this.discoItems = function discoItems(server, node){
    var data = {}
    if (server) data['server'] = server;
    if (node) data['node'] = node;
    $.ajax({
      type: "GET",
      url: "/sixties/ws/disco/items",
      dataType: "json",
      data: data,
      success: function(res){_handleResponse(res, discoItemsSuccess)}
    })
  };
  this.discoServicesSuccess = function discoServicesSuccess(res) {
    res = res.message;
    var tmp = {}
    $.each(res.message, function(host, hostval){
      if (!tmp[host]) tmp[host] = {}
      $.each(hostval, function(key, val){
        tmp[host][key] = val;
      })
    })
    function toTree(key, val){
      var res = {
        attributes: { id : key}, 
        data: (val['name'] ? val['name'] : key), 
        children: []
      }
      nodes[key] = val;
      if (val['items']) $.each(val['items'], function(k, v){res['children'].push(toTree(k, v))})
      return(res);
    }
    $.each(tmp, function(k, v){$('#main_tree').append(tree.create(toTree(k, v), -1));})
  }
  /**
   * Discover tree
   * 
   * @param server
   * @param node
   * 
   * @return void
   */
  this.discoServices = function discoServices(server, node){
    var data = {}
    if (server) data['server'] = server;
    if (node) data['node'] = node;
    $.ajax({
      type: "GET",
      url: "/sixties/ws/disco/services",
      dataType: "json",
      data: data,
      success: function(res){_handleResponse(res, _instance.discoServicesSuccess)}
    })
  };

  /**
   * Create an HTML form from a XMPP data form using an XSL transform
   * 
   * @param data
   * @param action
   * @param params
   * 
   * @return
   */
  this.loadForm = function loadForm(data, action, params, title) {
    // For use of XSLT processor, see https://developer.mozilla.org/index.php?title=en/The_XSLT%2F%2FJavaScript_Interface_in_Gecko
    var parser = new DOMParser();
    var doc = parser.parseFromString(data, "text/xml");
    var xsltProcessor = new XSLTProcessor();
    // Base for form's id : random number between 1 and 65000
    var baseid = Math.floor(Math.random() * 65000) + 1;
    // Finally import the .xsl
    xsltProcessor.importStylesheet(formStylesheet);
    xsltProcessor.setParameter(null, "baseid", baseid);
    var fragment = xsltProcessor.transformToFragment(doc, document);
    $('#main_form').empty().append(fragment);
    // Update input fields name
    $('#form_' + baseid + ' *[name]').each(function(){$(this).attr('name', 'form[' + this.name + ']')})
    // Add buttons
    var form = $('#form_' + baseid);
    var buttons = '<div class="form_field form_field_button">';
    buttons += '<input type="button" value="cancel" onclick="xmpp.cancel(' + baseid + ')" />';
    buttons += '<input type="button" value="submit" onclick="xmpp.submit(' + baseid + ')" />';
    buttons += '</div>';
    // Set the name of the callback method
    form.attr('action', action).prepend(buttons).append(buttons);
    // Add title
    if (title && $('#form_' + baseid + '.form_title').length == 0) { 
      form.prepend('<div class="form_title" xmlns:data="jabber:x:data">' + title + '</div>');
      $('#main_form').dialog('option', 'title', title);
    }
    if (params) {
      $.each(params, function(k, v) {form.append('<input type="hidden" name="' + k + '" value="' + v + '" class="form_field form_field_type_hidden" />')})
    }
    $('#main_form').dialog('open');
  }

  /**
   * Get the configuration of a node
   * @param node
   * @return
   */
  this.nodeGetConfiguration = function nodeGetConfiguration(node) {
    var data = {}
    if (node) data['node'] = node;
    $.ajax({
        type: "OPTIONS",
        url: "/sixties/ws/pubsub/node",
        data: data,
        dataType: 'json',
        success: function(res){
          res.action = 'config'
          _handleResponse(res, function(res2){
            _instance.loadForm(res.message.message, 'nodeSetConfiguration', data, 'Configuration of node ' + node);
          })
        }
      })
  }

   /**
    * Set the configuration of a node
    * @param config
    * @return
    */
   this.nodeSetConfiguration = function nodeSetConfiguration(data) {
      $.ajax({
        type: "PUT",
        url: "/sixties/ws/pubsub/node",
        data: data,
        dataType: 'json',
        success: function(res){_handleResponse(res, null, 'Update ' + data['node'])}
      })
   }
    
    this.affiliationGet = function affiliationGet(node) {
      var data = {}
      var onSuccess;
      if (node) {
        data['node'] = node;
        onSuccess = function(res){
          var tmp ='';
          $.each(res.message.message, function(k, v){tmp += '<tr><td>'+v['jid']+'</td><td>'+v['node']+'</td><td>'+v['affiliation']+'</td></tr>'})
          $('#node_affiliations tbody').empty().append($(tmp));
          $('#tabs').tabs('select', 'tabs-node')
        }
      } else {
        onSuccess = function(res){
          console.log(res);
          var tmp ='';
          $.each(res.message.message, function(k, v){tmp += '<tr><td>'+v['jid']+'</td><td>'+v['node']+'</td><td>'+v['affiliation']+'</td></tr>'})
          $('#affiliations tbody').empty().append($(tmp));
          $('#tabs').tabs('select', 'tabs-subs')
        }
      }
      $.ajax({
          type: "GET",
          url: "/sixties/ws/pubsub/affiliation",
          data: data,
          dataType: 'json',
          success: function(res){_handleResponse(res, onSuccess, 'Retrieve affiliations')}
      })
    }
    this.commandExecute = function commandExecute(pNode) {
      var data = {}
      if (pNode) data['node'] = pNode;
      $.ajax({
          type: "POST",
          url: "/sixties/ws/command/execute",
          data: data,
          dataType: 'json',
          success: function(res){
            res.action = '???'
            _handleResponse(res, function(res2){
              var message = res.message.message;
              //@TODO : manage form status
              _instance.loadForm(message.form, '', data, 'Result of command');
            })
          }
        })
    }
  /**
   * Init
   * @return
   */
  this.init = function init() {
/*
    // Get available web services
    $.ajax({type: "OPTIONS", url: "/sixties/ws/", dataType: "json", success: function(res){
      if (res.code == 200) {
        $.each(res.message, function(k, module){
          $.ajax({type: "OPTIONS", url: "/sixties/ws/" + module, dataType: "json", success: function(res2){
            if (res2.code == 200 && res2.message.code == 200) _wsServices[module] = res2.message.message
          }})
        })
      }
    }})
    console.log(_wsServices)
*/
    // Load tree
    this.discoServices();
    // load stylesheet for forms
    $.get("/sixties/ws/form.xsl", function(res){formStylesheet = res}, 'xml')
  }
   
  this.treeNodeSelected = function treeNodeSelected(pNode, pTree) {
    var id = $(pNode).attr('id');
    var node = nodes[id]
    var content    = '';
    var identities = '';
    var features   = '';
    var actions    = '';
    $.each(node['identities'], function(k, v){
      var catDsc = '', typeDsc = '';
      if (gRegistars['categories'][v['category']]) {
        var c = gRegistars['categories'][v['category']];
        catDsc = ' ( ' + c['desc'] + ' ) ';
        if (c['type'][v['type']]) typeDsc = ' ( ' + c['type'][v['type']]['desc'] + ' ) ';
        if (v['category'] == 'automation' && v['type'] == 'command-node') actions = actions.concat('<input type="button" value="Executer" onclick="xmpp.commandExecute(\'',node['node'],'\');" />')
      }
      identities = identities.concat('<dl><dt>Name</dt><dd>',v['name'],'</dd><dt>Category</dt><dd>',v['category'],catDsc,'</dd><dt>Type</dt><dd>',v['type'],typeDsc,'</dd>')
    })
    $.each(node['features'], function(k, v){
      var dsc = '';
      if (gRegistars['features'][v]) dsc = ' ( ' + gRegistars['features'][v]['desc'] + ' ' + gRegistars['features'][v]['doc'] + ' ) ';
      features = features.concat('<li>',v,dsc,'</li>');
    })
    var name = (node['name'] ? node['name'] : id);
    content = content.concat('<h2>',name,'</h2>\n');
    if (node['jid']) content = content.concat('<dl><dt>Jid</dt><dd>',node['jid'],'</dd><dt>Name</dt><dd>',node['name'],'</dd><dt>Node</dt><dd>',node['node'],'</dd>');
    content = content.concat('<h3>Identities</h3>\n', identities, '', '<h3>Features</h3>\n<ul>',features,'</ul>');
    if (actions) content = content.concat('<h3>Actions</h3>\n<form><fieldset>',actions,'</fieldset></form>\n');
    $('#main_node').empty().append(content);
  }
}
