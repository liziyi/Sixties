function bbXmpp(){
  var _instance   = this;
  var _wsServices = {}

  /**
   * Display a message
   */
  var _message = function _message(message, type) {
    var msg  = $('<li class="ui-corner-all"></li>'); 
    var icon = $('<span style="float: left; margin: 0 0.3em;" class="ui-icon "/>');
    if (type=='error') {
      icon.addClass('ui-icon-alert');
      msg.addClass('ui-state-error');
    } else {
      icon.addClass('ui-icon-info');
      msg.addClass('ui-state-highlight');
    }
    msg.append(icon).append(message);
    msg.click(function(){$(this).remove()});
    return $('#messages').prepend(msg).children().eq(0);
  }
  var _handleResponse = function(res, handler, desc) {
    desc = (desc?desc + ' : ': '');
    if (res['code'] != '200' || typeof res.message == 'undefined') {
      _message(desc + "Service error " + res['code']);
    } else if (res.message['code'] != '200' || typeof res.message.message == 'undefined') {
      _message(desc + "Module error " + res.message['code'], 'error');
    } else {
      if (desc != '') _message(desc + 'OK');
      if (handler) handler(res);
      else console.log('no handler')
    }

  }
  var _ajaxErrorCallback = function _ajaxErrorCallback(XMLHttpRequest, textStatus, errorThrown) {
    _message("ERROR : " + textStatus, 'error');
  }
  /**
   * Create a loader and return it
   */
  var _createLoader = function _createLoader(msg) {
    return _message('<img src="throbber.gif" />&nbsp;' + msg);
  }
  /**
   * Delete a loader
   */
  var _deleteLoader = function _deleteLoader(loader){
    loader.effect('highlight', {}, 1000).fadeOut(1000, function(o){$(o).remove});
    return _instance;
  }
  this.cancel = function cancel(id) {
    $('#main_form').dialog('close');
    $('#form_' + id).remove();
  }
  /**
   * Submit a form
   * 
   * @param id
   *          the form id
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
      success: function(res){_handleResponse(res, discoInfoSuccess)},
      error: _ajaxErrorCallback
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
      success: function(res){_handleResponse(res, discoItemsSuccess)},
      error: _ajaxErrorCallback
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
    var loader = _createLoader('Retrieving available services...');
    var data = {}
    if (server) data['server'] = server;
    if (node) data['node'] = node;
    $.ajax({
      type: "GET",
      url: "/sixties/ws/disco/services",
      dataType: "json",
      data: data,
      success: function(res){_handleResponse(res, _instance.discoServicesSuccess)},
      error: _ajaxErrorCallback,
      complete: function(){_deleteLoader(loader)}
    })
  };
  /**
   * Create an HTML form from a XMPP data form using an XSL transform
   * 
   * @param {Object} data   The form
   * @param {String} action Name of the callback method
   * @param {String} status One of 'executing', 'completed' or 'canceled'
   * @param {Object} params Set of additionnal parameters, hidden fields in the form
   * @param {String} title  Title of form has no
   * @return
   */
  this.loadForm = function loadForm(data, action, status, params, title) {
    // For use of XSLT processor, see
    // https://developer.mozilla.org/index.php?title=en/The_XSLT%2F%2FJavaScript_Interface_in_Gecko
    var baseid = Math.floor(Math.random() * 65000) + 1;
    if (data && data != '') {
      var parser = new DOMParser();
      var doc = parser.parseFromString(data, "text/xml");
      var xsltProcessor = new XSLTProcessor();
      // Base for form's id : random number between 1 and 65000
      // Finally import the .xsl
      xsltProcessor.importStylesheet(formStylesheet);
      xsltProcessor.setParameter(null, "baseid", baseid);
      var fragment = xsltProcessor.transformToFragment(doc, document);
    } else {
      var fragment = '<form id="form_' + baseid + '"><div class="form_field /></form>';
    }
    $('#main_form').empty().append(fragment);
    // Update input fields name
    $('#form_' + baseid + ' *[name]').each(function(){$(this).attr('name', 'form[' + this.name + ']')})

    
    var form = $('#form_' + baseid);
    // Info
    if (status == 'completed') {
      $('#form_' + baseid + ' .form_field').eq(0).before('<p class="form_info ui-state-highlight ui-corner-all">Ok</p>');
    }
    if (status == 'canceled') {
      $('#form_' + baseid + ' .form_field').eq(0).before('<p class="form_info ui-state-error ui-corner-all">Action cancelled</p>');
    }
    // Add buttons
    var buttons = '';
    buttons += '<div class="form_field form_field_button">';
    if (status == 'executing' || status == 'canceled') {
      buttons += '<input type="button" value="Cancel" onclick="xmpp.cancel(' + baseid + ')" />';
    }
    if (status == 'executing') {
      buttons += '<input type="button" value="Submit" onclick="xmpp.submit(' + baseid + ')" />';
    }
    if (status == 'completed') {
      buttons += '<input type="button" value="Ok" onclick="xmpp.cancel(' + baseid + ')" />';
    }
    buttons += '</div>';
    // Set the name of the callback method
    form.attr('action', action)/* .prepend(buttons) */.append(buttons);
    // Add title
// if (title && $('#form_' + baseid + '.form_title').length == 0) {
// form.prepend('<div class="form_title" xmlns:data="jabber:x:data">' + title +
// '</div>');
// }
    var formTitle = $('#form_' + baseid + ' .form_title').text();
    $('#main_form').dialog('option', 'title', (formTitle?formTitle:title));
    if (params) {
      $.each(params, function(k, v) {if (v) {form.append('<input type="hidden" name="' + k + '" value="' + v + '" class="form_field form_field_type_hidden" />')}})
    }
    $('#main_form').dialog('open');
  }

  /**
   * Get the configuration of a node
   * 
   * @param node
   * @return
   */
  this.nodeGetConfiguration = function nodeGetConfiguration(node) {
    var loader = _createLoader('Retrieving configuration...');
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
            _instance.loadForm(res.message.message, 'nodeSetConfiguration', 'executing', data, 'Configuration of node ' + node);
          })
        },
        error: _ajaxErrorCallback,
        complete: function(){_deleteLoader(loader)}
      })
  }

   /**
     * Set the configuration of a node
     * 
     * @param config
     * @return
     */
    this.nodeSetConfiguration = function nodeSetConfiguration(data) {
      $.ajax({
        type: "PUT",
        url: "/sixties/ws/pubsub/node",
        data: data,
        dataType: 'json',
        success: function(res){_handleResponse(res, null, 'Update ' + data['node'])},
        error: _ajaxErrorCallback
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
      var loader = _createLoader('Retrieving affiliations...');
      $.ajax({
          type: "GET",
          url: "/sixties/ws/pubsub/affiliation",
          data: data,
          dataType: 'json',
          success: function(res){_handleResponse(res, onSuccess)},
          error: _ajaxErrorCallback,
          complete: function(){_deleteLoader(loader)}
      })
    }

    /**
     * Execute a command on a node
     * 
     * @param pNode
     * @return
     */
    this.commandExecute = function commandExecute(pNode, pData, pTo) {
      var loader = _createLoader('Executing command...');
      var data = {}
      if (pData) data = pData;
      if (pNode) data['node'] = pNode;
      if (pTo) data['to'] = pTo;
      $.ajax({
          type: "POST",
          url: "/sixties/ws/command/execute",
          data: data,
          dataType: 'json',
          success: function(res){
            _handleResponse(res, function(res2){
              var message = res.message.message;
              console.log(message);
              var params = {};
              if (message['node']) params['node'] = message['node'];
              if (message['sessionid']) params['sessionid'] = message['sessionid'];
              if (pTo) params['to'] = pTo;
              _instance.loadForm(message.form, 'commandSubmitForm', message['status'], params);
            })
          },
          error: _ajaxErrorCallback,
          complete: function(){_deleteLoader(loader)}
        })
    }
    /**
     * Submit a form
     * @param data
     * @return
     */
    this.commandSubmitForm = function commandSubmitForm(data) {
      _instance.commandExecute(null, data);
    }

  /**
   * Init
   * 
   * @return
   */
  this.init = function init() {
/*
 * // Get available web services $.ajax({type: "OPTIONS", url: "/sixties/ws/",
 * dataType: "json", success: function(res){ if (res.code == 200) {
 * $.each(res.message, function(k, module){ $.ajax({type: "OPTIONS", url:
 * "/sixties/ws/" + module, dataType: "json", success: function(res2){ if
 * (res2.code == 200 && res2.message.code == 200) _wsServices[module] =
 * res2.message.message }}) }) } }, error: _ajaxErrorCallback })
 * console.log(_wsServices)
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
    if (node['identities']) {
      $.each(node['identities'], function(k, v){
        var catDsc = '', typeDsc = '';
        if (gRegistars['categories'][v['category']]) {
          var c = gRegistars['categories'][v['category']];
          catDsc = ' ( ' + c['desc'] + ' ) ';
          if (c['type'][v['type']]) typeDsc = ' ( ' + c['type'][v['type']]['desc'] + ' ) ';
          if (v['category'] == 'automation' && v['type'] == 'command-node') {
            var tmp = "'" + node['node'] + "'";
            if (node['jid'])  tmp +=", null, '" + node['jid'] + "'";
            actions = actions.concat('<input type="button" value="Executer" onclick="xmpp.commandExecute(' + tmp + ');" />')
          }
        }
        identities = identities.concat('<dl><dt>Name</dt><dd>',v['name'],'</dd><dt>Category</dt><dd>',v['category'],catDsc,'</dd><dt>Type</dt><dd>',v['type'],typeDsc,'</dd>')
      })
    }
    if (node['features']) {
      $.each(node['features'], function(k, v){
        var dsc = '';
        if (gRegistars['features'][v]) dsc = ' ( ' + gRegistars['features'][v]['desc'] + ' ' + gRegistars['features'][v]['doc'] + ' ) ';
        features = features.concat('<li>',v,dsc,'</li>');
      })
    }
    var name = (node['name'] ? node['name'] : id);
    content = content.concat('<h2>',name,'</h2>\n');
    if (node['jid']) content = content.concat('<dl><dt>Jid</dt><dd>',node['jid'],'</dd><dt>Name</dt><dd>',node['name'],'</dd><dt>Node</dt><dd>',node['node'],'</dd>');
    content = content.concat('<h3>Identities</h3>\n', identities, '', '<h3>Features</h3>\n<ul>',features,'</ul>');
    if (actions) content = content.concat('<h3>Actions</h3>\n<form><fieldset>',actions,'</fieldset></form>\n');
    $('#main_node').empty().append(content);
  }
}
