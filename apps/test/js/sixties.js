function bbXmpp(){
  var _instance   = this;
  var _wsServices = {};

  /**
   * Current selected node
   */
  this.current_node = null;

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
    msg.click(function(){$(this).remove();});
    return $('#messages').prepend(msg).children().eq(0);
  };
  /**
   * 
   */
  var _beforeSend = function _beforeSend(xhr) {
    xhr.setRequestHeader("Authorization", "Basic xxx");
    xhr.setRequestHeader("Content-Location", "server:port");
  };
  /**
   * 
   */
  var _handleResponse = function _handleResponse(res, handler, desc) {
    desc = (desc?desc + ' : ': '');
    if (res['code'] != '200' || typeof res.message == 'undefined') {
      _message(desc + "Service error " + res['code']);
    } else if (res.message['code'] != '200' || typeof res.message.message == 'undefined') {
      var error_message = '';
      if (typeof res.message.message == 'string') error_message = res.message;
      else if (typeof res.message == 'object' && res.message['message']['stanzas']) error_message = res.message['message']['stanzas'].join();
      _message(desc + "Module error " + res.message['code'] + ' ' + error_message, 'error');
    } else {
      if (desc !== '') _message(desc + 'OK');
      if (handler) handler(res);
    }

  };
  var _ajaxErrorCallback = function _ajaxErrorCallback(XMLHttpRequest, textStatus, errorThrown) {
    switch (textStatus) {
      case 'error':
        break;
      case 'notmodified':
        break;
      case 'parsererror':
        textStatus = 'Enable to parser server response';
        break;
      case 'timeout':
        textStatus = 'Timeout';
        break;
      default:
        break;
    }
    _message("ERROR : " + textStatus, 'error');
  };
  /**
   * Create a loader and return it
   */
  var _createLoader = function _createLoader(msg) {
    return _message('<img src="throbber.gif" />&nbsp;' + msg);
  };
  /**
   * Delete a loader
   */
  var _deleteLoader = function _deleteLoader(loader){
    loader.effect('highlight', {}, 1000).fadeOut(1000, function(o){$(o).remove;});
    return _instance;
  };
  this.formCancel = function formCancel() {
    $('#main_form').empty().dialog('close');
  };
  /**
   * Submit a form
   * 
   * @param id the form id
   * 
   * @return void
   */
  this.formSubmit = function formSubmit(id) {
    var data = {};
    // Get the value of all fields
    $('#form_' + id + ' *[name]').each(function(){data[this.name]=$(this).val();});
    // for radio buttons, we need a second pass
    $('#form_' + id + ' input:radio[name]:checked').each(function(){data[this.name]=$(this).val();});
    // Get the method to call
    var method = $('#form_' + id).attr('action');
    // and call it
    _instance[method](data);
  };
  /**
   * 
   */
  this.discoSuccess = function discoSuccess(res) {
    res = res.message;
    if (res.code == 200) {
      $.each(res.message, function(host, hostval){
        if (!nodes[host]) nodes[host] = {
            jid: host.split('!')[0],
            node: host.split('!').slice(1).join('!'),
            name: ''
        };
        $.each(hostval, function(key, val){
          nodes[host][key] = val;
        });
      });
    }
  };
  /**
   * 
   * @param server
   * @param node
   * @return
   */
  this.discoInfo = function discoInfo(server, node){
    var data = {};
    if (server) data['server'] = server;
    if (node) data['node'] = node;
    $.ajax({
      type: "GET",
      url: "/sixties/ws/disco/info",
      dataType: "json",
      data: data,
      beforeSend: _beforeSend,
      success: function(res){_handleResponse(res, _instance.discoSuccess);},
      error: _ajaxErrorCallback
    });
  };
  this.discoItems = function discoItems(server, node){
    var data = {};
    if (server) data['server'] = server;
    if (node) data['node'] = node;
    $.ajax({
      type: "GET",
      url: "/sixties/ws/disco/items",
      dataType: "json",
      data: data,
      beforeSend: _beforeSend,
      success: function(res){_handleResponse(res, _instance.discoSuccess);},
      error: _ajaxErrorCallback
    });
  };
  this.discoServicesSuccess = function discoServicesSuccess(res) {
    res = res.message;
    var tmp = {};
    $.each(res.message, function(host, hostval){
      if (!tmp[host]) tmp[host] = {};
      $.each(hostval, function(key, val){
        tmp[host][key] = val;
      });
    });
    function toTree(key, val){
      var res = {
        attributes: { id : key}, 
        data: (val['name'] ? val['name'] : key), 
        children: []
      };
      nodes[key] = val;
      if (val['items']) $.each(val['items'], function(k, v){res['children'].push(toTree(k, v));});
      return(res);
    }
    $('#main_tree').empty();
    $.each(tmp, function(k, v){$('#main_tree').append(tree.create(toTree(k, v), -1));});
  };
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
    var data = {};
    if (server) data['server'] = server;
    if (node) data['node'] = node;
    $.ajax({
      type: "GET",
      url: "/sixties/ws/disco/services",
      dataType: "json",
      data: data,
      beforeSend: _beforeSend,
      success: function(res){_handleResponse(res, _instance.discoServicesSuccess);},
      error: _ajaxErrorCallback,
      complete: function(){_deleteLoader(loader);}
    });
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
  this.formLoad = function formLoad(data, action, status, params, title) {
    // For use of XSLT processor, see
    // https://developer.mozilla.org/index.php?title=en/The_XSLT%2F%2FJavaScript_Interface_in_Gecko
    var baseid = Math.floor(Math.random() * 65000) + 1;
    var fragment;
    if (data && data !== '') {
      var parser = new DOMParser();
      var doc = parser.parseFromString(data, "text/xml");
      var xsltProcessor = new XSLTProcessor();
      // Base for form's id : random number between 1 and 65000
      // Finally import the .xsl
      xsltProcessor.importStylesheet(formStylesheet);
      xsltProcessor.setParameter(null, "baseid", baseid);
      fragment = xsltProcessor.transformToFragment(doc, document);
    } else {
      fragment = '<form id="form_' + baseid + '"><div class="form_field /></form>';
    }
    $('#main_form').empty().append(fragment);
    // Update input fields name
    $('#form_' + baseid + ' *[name]').each(function(){var e=$(this);e.attr('name', 'form['+this.name+']'+(e.attr('multiple')?'[]':''));});

    
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
      buttons += '<input type="button" value="Cancel" onclick="xmpp.formCancel()" />';
    }
    if (status == 'executing') {
      buttons += '<input type="button" value="Submit" onclick="xmpp.formSubmit(' + baseid + ')" />';
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
      $.each(params, function(k, v) {if (v) {form.append('<input type="hidden" name="' + k + '" value="' + v + '" class="form_field form_field_type_hidden" />');}});
    }
    $('#main_form').dialog('open');
  };

  this.nodeCreate = function nodeCreate(server, node, type) {
    var form = '<x xmlns="jabber:x:data" type="form"><field label="Name" type="text-single" var="name"></field></x>';
    data = {server: server, node: node, type: type};
    this.formLoad(form, 'nodeSubmitForm', 'executing', data, 'Create a ' + type + ' under ' + node);
  };
  /**
   * Submit the node creation form 
   */
  this.nodeSubmitForm = function nodeSubmitForm(pData) {
    var loader = _createLoader('Try to create the node...');
    var newNodeName = pData['form[name]'];
    var newNodePath = (pData['node'] == '/' ? '' : pData['node']) + '/' + newNodeName;
    var data = {server: pData['server'], type: pData['type'], node: newNodePath};
    $.ajax({
        type: "POST",
        url: '/sixties/ws/pubsub/node',
        data: data,
        dataType: 'json',
        beforeSend: _beforeSend,
        success: function(res){
          if (res['code'] == '200' && res['message']['code'] == '200'){
            // Get new node infos
            _instance.discoInfo(pData['server'], newNodePath);
            // close the dialog
            $('#main_form').empty().dialog('close');
            // update the tree
            var selected = tree.selected;
            var newNode = {
              attributes: { id : selected.attr('id') + (/!/.test(selected.attr('id'))?'':'!') + '/' + newNodeName}, 
              data: newNodeName,
              children: []
            };
            tree.create(newNode, selected);
            tree.refresh(selected);
          }
          _handleResponse(res, null, 'Node ' + data['node'] + ' creation');
        },
        error: _ajaxErrorCallback,
        complete: function(){_deleteLoader(loader);}
      });
  };
  /**
   * Delete a node 
   */
  this.nodeDelete = function nodeDelete(server, node) {
    var loader = _createLoader('Try to delete the node...');
    var data = {server: server, node: node};
    $.ajax({
        type: "DELETE",
        url: '/sixties/ws/pubsub/node',
        data: data,
        dataType: 'json',
        beforeSend: _beforeSend,
        success: function(res){
          _handleResponse(res, null, 'Deletion of node ' + data['node']);
          //@TODO : remove node from tree
        },
        error: _ajaxErrorCallback,
        complete: function(){_deleteLoader(loader);}
      });
  };
  /**
   * Get the configuration of a node
   * 
   * @param node
   * @return
   */
  this.nodeGetConfiguration = function nodeGetConfiguration(server, node) {
    var loader = _createLoader('Retrieving configuration...');
    var data = {};
    if (server) data['server'] = server;
    if (node) data['node'] = node;
    $.ajax({
        type: "OPTIONS",
        url: "/sixties/ws/pubsub/node",
        data: data,
        dataType: 'json',
        beforeSend: _beforeSend,
        success: function(res){
          res.action = 'config';
          _handleResponse(res, function(res2){
            _instance.formLoad(res.message.message, 'nodeSetConfiguration', 'executing', data, 'Configuration of node ' + node);
          });
        },
        error: _ajaxErrorCallback,
        complete: function(){_deleteLoader(loader);}
      });
  };

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
      success: function(res){
        if (res['code'] == '200') $('#main_form').empty().dialog('close');
        _handleResponse(res, null, 'Update ' + data['node']);
      },
      error: _ajaxErrorCallback
    });
  };

  /**
   * Get Affiliations
   * @param node
   * @return
   */
  this.affiliationsGet = function affiliationsGet(node) {
    var data = {};
    var onSuccess;
    var createArray = function(v) {
      var nodeParams = "null, '" + v['node'] + "'";
      var res  = '';
      res += '<td>'+v['jid']+'</td><td>'+v['node']+'</td><td>'+v['affiliation']+'</td>';
      res += '<td>';
      res += '<a class="array_action" title="Edit node" onclick="xmpp.nodeGetConfiguration(' + nodeParams +');"><span class="ui-icon ui-icon-pencil" /></a>';
      res += '<a class="array_action" title="Delete node" onclick="xmpp.nodeDelete('+nodeParams+');xmpp.affiliationsGet('+node+')"><span class="ui-icon ui-icon-trash" /></a>';
      res += '</td>';
      return '<tr>' + res + '</tr>';
    };
    if (node) {
      var tmp = node.split('!');
      if (tmp.length == 1) {
        data['node'] = tmp[0];
      } else {
        data['server'] = tmp.shift();
        data['node'] = tmp.join('!');
      }
      onSuccess = function(res){
        var tmp ='';
        $.each(res.message.message, function(k, v){tmp += createArray(v);});
        if (tmp !== '') {
          $('#node_affiliations tbody').empty().append($(tmp));
        } else {
          $('#node_affiliations tbody').empty();
        }
        $('#tabs').tabs('select', 'tabs-node');
      };
    } else {
      onSuccess = function(res){
        var tmp ='';
        $.each(res.message.message, function(k, v){tmp += createArray(v);});
        if (tmp !== '') {
          $('#affiliations tbody').empty().append($(tmp));
        } else {
          $('#affiliations tbody').empty();
        }
        $('#tabs').tabs('select', 'tabs-subs');
      };
    }
    var loader = _createLoader('Retrieving affiliations...');
    $.ajax({
        type: "GET",
        url: "/sixties/ws/pubsub/affiliation",
        data: data,
        dataType: 'json',
        beforeSend: _beforeSend,
        success: function(res){_handleResponse(res, onSuccess);},
        error: _ajaxErrorCallback,
        complete: function(){_deleteLoader(loader);}
    });
  };

  /**
   * Get subscriptions
   * @param node
   * @return
   */
  this.subscriptionsGet = function subscriptionsGet(node) {
    var data = {};
    var onSuccess;
    var createArray = function(v) {
      var nodeParams = "null, '" + v['node'] + "', '" + v['subid'] + "'";
      var res  = '';
      res += '<td>'+v['jid']+'</td><td>'+v['node']+'</td><td>'+v['subid']+'</td><td>'+v['subscription']+'</td>';
      res += '<td>';
      res += '<a class="array_action" title="Edit subscription" onclick="xmpp.subscriptionEdit(' + nodeParams +');"><span class="ui-icon ui-icon-pencil" /></a>';
      res += '<a class="array_action" title="Delete subscription" onclick="xmpp.subscriptionDelete('+nodeParams+');xmpp.subscriptionsGet('+node+')"><span class="ui-icon ui-icon-trash" /></a>';
      res += '</td>';
      return '<tr>' + res + '</tr>';
    };
    if (node) {
      var tmp = node.split('!');
      if (tmp.length == 1) {
        data['node'] = tmp[0];
      } else {
        data['server'] = tmp.shift();
        data['node'] = tmp.join('!');
      }
      onSuccess = function(res){
        var tmp ='';
        $.each(res.message.message, function(k, v){tmp += createArray(v);});
        if (tmp !== '') {
          $('#node_subscriptions tbody').empty().append($(tmp));
        } else {
          $('#node_subscriptions tbody').empty();
        }
        $('#tabs').tabs('select', 'tabs-node');
      };
    } else {
      onSuccess = function(res){
        var tmp ='';
        $.each(res.message.message, function(k, v){tmp += createArray(v);});
        if (tmp !== '') {
          $('#subscriptions tbody').empty().append($(tmp));
        } else {
          $('#subscriptions tbody').empty();
        }
        $('#tabs').tabs('select', 'tabs-subs');
      };
    }
    var loader = _createLoader('Retrieving subscriptions...');
    $.ajax({
        type: "GET",
        url: "/sixties/ws/pubsub/subscription",
        data: data,
        dataType: 'json',
        beforeSend: _beforeSend,
        success: function(res){_handleResponse(res, onSuccess);},
        error: _ajaxErrorCallback,
        complete: function(){_deleteLoader(loader);}
    });
  };

  /**
   * Subscribe to a node
   */
  this.subscriptionCreate = function subscriptionCreate(server, node) {
    var loader = _createLoader('Subscribing to ' + node);
    var data = {server: server, node: node};
    $.ajax({
        type: "POST",
        url: "/sixties/ws/pubsub/subscription",
        data: data,
        dataType: 'json',
        beforeSend: _beforeSend,
        success: function(res){
          _handleResponse(res, function(res2){
            // Update display of user's subscriptions
            _instance.subscriptionsGet();
            // close the dialog
            $('#main_form').empty().dialog('close');
          });
        },
        error: _ajaxErrorCallback,
        complete: function(){_deleteLoader(loader);}
      });
  };
  /**
   * Get the options of a subscription
   */
  this.subscriptionEdit = function subscriptionEdit(server, node, subid) {
    var loader = _createLoader('Retrieving options...');
    var data = {server: server, node: node, subid: subid};
    $.ajax({
        type: "OPTIONS",
        url: "/sixties/ws/pubsub/subscription",
        data: data,
        dataType: 'json',
        beforeSend: _beforeSend,
        success: function(res){
          res.action = 'config';
          _handleResponse(res, function(res2){
            _instance.formLoad(res.message.message, 'subscriptionSetConfiguration', 'executing', data, 'Configuration subscription to node ' + node);
          });
        },
        error: _ajaxErrorCallback,
        complete: function(){_deleteLoader(loader);}
      });
  };
  /**
   * Set the options of a subscription
   */
  this.subscriptionSetConfiguration = function subscriptionSetConfiguration(data) {
    $.ajax({
      type: "PUT",
      url: "/sixties/ws/pubsub/subscription",
      data: data,
      dataType: 'json',
      success: function(res){
        _handleResponse(res, function(res){$('#main_form').empty().dialog('close');}, 'Subscription updated');
      },
      error: _ajaxErrorCallback
    });
  };

  this.subscriptionDelete = function subscriptionDelete(server, node, subid) {
    var loader = _createLoader('Try to delete the subscription...');
    var data = {server: server, node: node, subid: subid};
    $.ajax({
        type: "DELETE",
        url: '/sixties/ws/pubsub/subscription',
        data: data,
        dataType: 'json',
        beforeSend: _beforeSend,
        success: function(res){
          _message('Subscription deleted');
          // Update users's subscriptions
          _instance.subscriptionsGet();          
        },
        error: _ajaxErrorCallback,
        complete: function(){_deleteLoader(loader);}
      });
  };

  /**
   * Execute a command on a node
   * 
   * @param pNode
   * @return
   */
  this.commandExecute = function commandExecute(pTo, pNode, pData) {
    var loader = _createLoader('Executing command...');
    var data = {};
    if (pData) data = pData;
    if (pNode) data['node'] = pNode;
    if (pTo) data['to'] = pTo;
    $.ajax({
        type: "POST",
        url: "/sixties/ws/command/execute",
        data: data,
        dataType: 'json',
        beforeSend: _beforeSend,
        success: function(res){
          _handleResponse(res, function(res2){
            var message = res.message.message;
            var params = {};
            if (message['node']) params['node'] = message['node'];
            if (message['sessionid']) params['sessionid'] = message['sessionid'];
            if (pTo) params['to'] = pTo;
            _instance.formLoad(message.form, 'commandSubmitForm', message['status'], params);
          });
        },
        error: _ajaxErrorCallback,
        complete: function(){_deleteLoader(loader);}
      });
  };
  /**
   * Submit a command form
   * @param data
   * @return
   */
  this.commandSubmitForm = function commandSubmitForm(data) {
    _instance.commandExecute(null, data);
  };
  
  this.contentGetForm = function contentGetForm(server, node) {
    //@TODO : use a local cache to not load the form each time
    var loader = _createLoader('Retrieving form...');
    var data = {};
    $.ajax({
        type: "OPTIONS",
        url: "/sixties/ws/pubsub/atom",
        data: data,
        dataType: 'json',
        beforeSend: _beforeSend,
        success: function(res){
          _handleResponse(res, function(res2){
            var message = res.message.message;
            if (message['form']) {
              _instance.formLoad(message.form, 'contentSubmitForm', 'executing', {server: server, node: node}, 'Publish');
            }
          });
        },
        error: _ajaxErrorCallback,
        complete: function(){_deleteLoader(loader);}
      });
  };
  this.contentSubmitForm = function contentSubmitForm(pData) {
    var loader = _createLoader('Trying to publish...');
    var res = '';
    var id;
    $.each(pData, function(k, v){
      if (v !== '' && k.substr(0,5) == 'form[') {
        var tag = k.substring(5, k.length-1).toLowerCase();
        if (tag == 'title') id = v;
        res = res.concat('<',tag,'><![CDATA[',v,']]></',tag,'>');
      }
    });
    res = '<entry xmlns="http://www.w3.org/2005/Atom">'+res+'</entry>';
    var data = {
        server: pData['server'],
        node: pData['node'],
        item: res
    };
    if (id) data['id'] = id;
    $.ajax({
        type: "POST",
        url: "/sixties/ws/pubsub/item",
        data: data,
        dataType: 'json',
        beforeSend: _beforeSend,
        success: function(res){
          if (res['code'] == '200' && res['message']['code'] == '200'){
            // Update tree
            _instance.discoItems(pData['server'], pData['node']);
            //@TODO : update the tree
            // close the dialog
            $('#main_form').empty().dialog('close');
          }
          _handleResponse(res, null, 'Publish');
        },
        error: _ajaxErrorCallback,
        complete: function(){_deleteLoader(loader);}
      });
  };
  /**
   * Request the search form
   * @param jid jid of the search service
   * @return
   */
  this.directorySearch = function directorySearch(jid) {
    var loader = _createLoader('Retrieving search form...');
    var data = {};
    if (jid) data['jid'] = jid;
    $.ajax({
        type: "GET",
        url: "/sixties/ws/search/search",
        data: data,
        dataType: 'json',
        beforeSend: _beforeSend,
        success: function(res){
          _handleResponse(res, function(res2){
            var message = res.message.message;
            if (message['form']) {
              _instance.formLoad(message.form, 'directorySubmitForm', 'executing', {'jid': jid}, 'Search');
            }
          });
        },
        error: _ajaxErrorCallback,
        complete: function(){_deleteLoader(loader);}
      });
  };
  /**
   * Perform a search
   * @param data
   * @return
   */
  this.directorySubmitForm = function directorySubmitForm(pData) {
    var loader = _createLoader('Searching...');
    var data = {};
    if (pData) data = pData;
    $.ajax({
        type: "POST",
        url: "/sixties/ws/search/search",
        data: data,
        dataType: 'json',
        beforeSend: _beforeSend,
        success: function(res){
          _handleResponse(res, function(res2){
            var message = res.message.message;
            _instance.formLoad(message.form, null, message['completed']);
          });
        },
        error: _ajaxErrorCallback,
        complete: function(){_deleteLoader(loader);}
      });
  };

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
    this.discoServices('pubsub.larzac.org');
    // load stylesheet for forms
    $.get("/sixties/ws/form.xsl", function(res){formStylesheet = res;}, 'xml');
  };
  
  /**
   * Callback when a tree node is selected : display node details, actions, etc
   * 
   * @param pNode
   * @param pTree
   * @return
   */
  this.treeNodeSelected = function treeNodeSelected(pNode, pTree) {
    var id            = $(pNode).attr('id');
    var node          = nodes[id];
    var content       = '';
    var identities    = '';
    var features      = '';
    var actions       = [];
    _instance.current_node = id;
    // identities
    if (node['identities']) {
      $.each(node['identities'], function(k, v){
        var catDsc = '', typeDsc = '';
        // Add descriptions from the registry
        if (gRegistars['categories'][v['category']]) {
          var c = gRegistars['categories'][v['category']];
          catDsc = ' ( ' + c['desc'] + ' ) ';
          if (c['type'][v['type']]) typeDsc = ' ( ' + c['type'][v['type']]['desc'] + ' ) ';
        }
        var jidnode = (node['jid']?"'"+node['jid']+"'":'null')+','+(node['node']?"'"+node['node']+"'":"'/'");
        switch (v['category']) {
          case 'automation':
            switch (v['type']) {
              case 'command-node':
                actions.push({value: 'Execute', action: "xmpp.commandExecute(" + jidnode + ");"});
                break;
              case 'command-list':
                break;
              case 'rpc':
                break;
              case 'soap':
                break;
              case 'translation':
                break;
              default:
                break;
            }
            break;
          case 'directory':
            switch (v['type']) {
              case 'user':
                actions.push({value: 'Search', action: "xmpp.directorySearch("+(node['jid']?"'"+node['jid']+ "'":'')+");"});
                break;
              default:
                //@TODO
                break;
            }
            break;
          case 'gateway':
            break;
          case 'headline':
            break;
          case 'hierarchy':
            break;
          case 'proxy':
            break;
          case 'pubsub':
            switch (v['type']) {
              case 'collection':
                actions.push({value: 'Configure', action: "xmpp.nodeGetConfiguration(" + jidnode + ");"});
                actions.push({value: 'Create new collection', action: "xmpp.nodeCreate(" + jidnode + ", 'collection');"});
                actions.push({value: 'Create new node', action: "xmpp.nodeCreate(" + jidnode + ", 'leaf');"});
                actions.push({value: 'Delete node', action: "xmpp.nodeDelete(" + jidnode + ", 'node');"});
                break;
              case 'leaf':
                // In fact it may be a collection, so allow sub-node creation.
                actions.push({value: 'Configure', action: "xmpp.nodeGetConfiguration(" + jidnode + ");"});
                actions.push({value: 'Create new collection', action: "xmpp.nodeCreate(" + jidnode + ", 'collection');"});
                actions.push({value: 'Create new node', action: "xmpp.nodeCreate(" + jidnode + ", 'leaf');"});
                actions.push({value: 'Publish content', action: "xmpp.contentGetForm(" + jidnode + ");"});
                actions.push({value: 'Delete node', action: "xmpp.nodeDelete(" + jidnode + ", 'node');"});
                break;
              case 'pep':
                actions.push({value: '??', action: ""});
                break;
              case 'service':
                actions.push({value: 'Create new collection', action: "xmpp.nodeCreate(" + jidnode + ", 'collection');"});
                actions.push({value: 'Create new node', action: "xmpp.nodeCreate(" + jidnode + ", 'leaf');"});
                break;
              default:
                break;
            }
            actions.push({value: 'Subscribe', action: "xmpp.subscriptionCreate(" + jidnode + ", 'node');"});
            var render = '';
            $.each(actions, function(k, v){
              render = render.concat('<input type="button" value="',v['value'],'" onclick="',v['action'],'" />');
            });
            $('#node_actions').empty().append('<form><fieldset>' + render + '</fieldset></form>\n');
            break;
          case 'server':
            break;
          case 'store':
            break;
          default:
            break;
        }
        identities = identities.concat('<dl><dt>Name</dt><dd>',v['name'],'</dd><dt>Category</dt><dd>',v['category'],catDsc,'</dd><dt>Type</dt><dd>',v['type'],typeDsc,'</dd></dl>');
      });
    }
    // Features
    if (node['features']) {
      $.each(node['features'], function(k, v){
        var dsc = '';
        // Add descriptions from the registry
        if (gRegistars['features'][v]) dsc = ' ( ' + gRegistars['features'][v]['desc'] + ' ' + gRegistars['features'][v]['doc'] + ' ) ';
        features = features.concat('<li>',v,dsc,'</li>');
      });
    }
    // Render
    var name = (node['name'] ? node['name'] : id);
    content = content.concat('<h2>',name,'</h2>\n');
    if (node['jid']) content = content.concat('<dl><dt>Jid</dt><dd>',node['jid'],'</dd><dt>Name</dt><dd>',node['name'],'</dd><dt>Node</dt><dd>',node['node'],'</dd></dl>');
    content = content.concat('<h3>Identities</h3>\n', identities, '', '<h3>Features</h3>\n<ul>',features,'</ul>');
    if (actions.length > 0) {
      var render = '';
      $.each(actions, function(k, v){
        render = render.concat('<input type="button" value="',v['value'],'" onclick="',v['action'],'" />');
      });
      content = content.concat('<h3>Actions</h3>\n<form><fieldset>',render,'</fieldset></form>\n');
    }
    $('#main_node').empty().append(content);
  };
}
