////////////////////////////////////
//// Contains() polyfill method ///
///////////////////////////////////
if (!String.prototype.contains) {
    String.prototype.contains = function(s) {
        return this.indexOf(s) > -1
    }
}

/********************************************
************ NAVIGATION MENU HANDLING *******
*********************************************/
$("#menu-toggle").click(function(e) {
    e.preventDefault();
    $("#wrapper").toggleClass("toggled");
});

$("#menu-toggle-2").click(function(e) {
    e.preventDefault();
    $("#wrapper").toggleClass("toggled-2");
    $('#menu ul').hide();
});

function initMenu() {
$('#menu ul').hide();
$('#menu ul').children('.current').parent().show();
//$('#menu ul:first').show();
$('#menu li a').click(
  function() {
    var checkElement = $(this).next();
    if((checkElement.is('ul')) && (checkElement.is(':visible'))) {
      return false;
      }
    if((checkElement.is('ul')) && (!checkElement.is(':visible'))) {
      $('#menu ul:visible').slideUp('normal');
      checkElement.slideDown('normal');
      return false;
      }
    }
  );
}

/*********************************************
**************** POC FUNCTIONS ***************
**********************************************/
function displayProject(divName, itemId, templateId) {
  store.render(divName, itemId, templateId);
  refreshBrowsePanel(itemId, 'actor');
  refreshBrowsePanel(itemId, 'project');
  window.location.hash = itemId;
}

function displayActor(divName, itemId, templateId) {
  store.render(divName, itemId, templateId);
  refreshBrowsePanel(itemId, 'project');
  refreshBrowsePanel(itemId, 'actor');
  // store.get(itemId).then(function(object) {
  //   var postsFeedUrl;
  //   if (typeof object['foaf:weblog'] != 'undefined') {
  //     postFeedUrl = object['foaf:weblog'] + '#me';
  //   } else if (typeof object['foaf:accountName'] != 'undefined') {
  //     postsFeedUrl = 'http://localhost/wordpress/author/' + object['foaf:accountName'] + '#me';
  //   } else if (typeof object['foaf:nick'] != 'undefined') {
  //     postsFeedUrl = 'http://localhost/wordpress/author/' + object['foaf:nick'] + '#me';
  //   }
  //   console.log('postsFeedUrl', postsFeedUrl);
  //   store.get(postsFeedUrl).then(function(postObjects) {
  //     console.log('postsFeed', JSON.stringify(postObjects));
  //   });
  //   // store.render('#posts', postsFeedUrl, '#actor-posts-template');
  // });

  window.location.hash = itemId;
}

function refreshBrowsePanel(itemId, templatePrefix) {
  store.render(
    "#" + templatePrefix + "-browser",
    itemId,
    '#' + templatePrefix + '-browser-template'
  );
}

function displayResource(resourceIri) {
  if (resourceIri.contains('/project/')) {
    displayProject('#detail', resourceIri, '#project-detail-template');
  } else if (resourceIri.contains('/actor/')) {
    displayActor('#detail', resourceIri, '#actor-detail-template');
  }
}

function getKnownHostsList() {
  var knownHostsList = [ config.resourceBaseUrl ];
  if (typeof(Storage)) {
    var hostList = localStorage.getItem('ldp_hostname_list');
    if (hostList) {
      hostList = JSON.parse(hostList);
      if (hostList.host) {
        knownHostsList = hostList.host
      }
    }
  }

  return knownHostsList;
}

function refreshCardFromHash() {
  var hash = window.location.hash;
  if (hash) {
    var url_array = hash.substring(1, hash.length).split('/ldp/');
    if (url_array) {
      var hostname = url_array[0];
      if (hostname && typeof(Storage)) {
        var hostList = localStorage.getItem('ldp_hostname_list');
        if (hostList) {
          hostList = JSON.parse(hostList);
          var exists = false;
          if (hostList.host) {
            hostList.host.forEach(function(host) {
              if (host == hostname) {
                exists = true;
              }
            });
          }

          if (!exists) {
            hostList.host.push(hostname);
          }
        } else {
          hostList = {};
          hostList.host = [];
          hostList.host.push(hostname);
        }

        localStorage.setItem('ldp_hostname_list', JSON.stringify(hostList));
      }
    }
    displayResource(hash.substring(1, hash.length));
  } else {
    var resourceId = config.resourceBaseUrl + '/ldp/project/assemblee-virtuelle/';
    displayProject('#detail', resourceId, '#project-detail-template');
  }
}

function getTemplateAjax(path, callback) {
  var source, template;
  $.ajax({
      url: path,
      success: function (data) {
          source = data;
          template = Handlebars.compile(source);
          if (callback) callback(template);
      }
  });
}

function displayTemplate(template, div, data) {
  if (typeof(template) == 'string' && template.substring(0, 1) == '#') {
    var element = $(template);
    if (element && typeof element.attr('src') !== 'undefined') {
      getTemplateAjax(element.attr('src'), function(template) {
        $(div).html(template(data));
      });
    } else {
      template = Handlebars.compile(element.html());
      $(div).html(template(data));
    }
  } else {
    template = Handlebars.compile(template);
    $(div).html(template({object: data}));
  }
}

function loadGraphFromRdfViewer(){
  //  var hash = window.location.hash;
  //  var loadVal = hash.substring(1, hash.length);
  // Temporary Hack
   var loadVal = "http://benoit-alessandroni.fr/rdf/foaf.rdf";
   console.log('loadVal', loadVal);
    if (loadVal != null) {
        loadVal = decodeURIComponent(loadVal);
    }
    viewrdf("#chart",1000,1000,loadVal,300);
}

function loadOnClickEvent() {
  $('#card').click(function() {
    $('#graph-container').hide("slow");
    $('#main-container').show("slow");
    $('#main-container').width("100%");
    $('#main-container').height("100%");

    refreshCardFromHash();

  });

  $('#graph').click(function() {
    $('#main-container').hide("slow");
    $('#graph-container').show("slow");
    $('#graph-container').width("100%");
    $('#graph-container').height("100%");
    loadGraphFromRdfViewer();
  });
}

$(document).ready(function() {
  initMenu();
  loadOnClickEvent();
});
