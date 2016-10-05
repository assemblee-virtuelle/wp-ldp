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
jQuery("#menu-toggle").click(function(e) {
    e.preventDefault();
    jQuery("#wrapper").toggleClass("toggled");
});

jQuery("#menu-toggle-2").click(function(e) {
    e.preventDefault();
    jQuery("#wrapper").toggleClass("toggled-2");
    jQuery('#menu ul').hide();
});

function initMenu() {
jQuery('#menu ul').hide();
jQuery('#menu ul').children('.current').parent().show();
//jQuery('#menu ul:first').show();
jQuery('#menu li a').click(
  function() {
    var checkElement = jQuery(this).next();
    if((checkElement.is('ul')) && (checkElement.is(':visible'))) {
      return false;
      }
    if((checkElement.is('ul')) && (!checkElement.is(':visible'))) {
      jQuery('#menu ul:visible').slideUp('normal');
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
  refreshBrowsePanel(itemId, 'resource');
  refreshBrowsePanel(itemId, 'idea');
  window.location.hash = itemId;
}

function displayPerson(divName, itemId, templateId) {
  store.render(divName, itemId, templateId);
  refreshBrowsePanel(itemId, 'project');
  refreshBrowsePanel(itemId, 'actor');
  refreshBrowsePanel(itemId, 'resource');
  refreshBrowsePanel(itemId, 'idea');
  window.location.hash = itemId;
}

function displayGroup(divName, itemId, templateId) {
  store.render(divName, itemId, templateId);
  refreshBrowsePanel(itemId, 'project');
  refreshBrowsePanel(itemId, 'actor');
  refreshBrowsePanel(itemId, 'resource');
  refreshBrowsePanel(itemId, 'idea');
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
    var url_array = resourceIri.substring(1).split('/ldp/');
    var segmentsIRI = url_array[1].split('/');
    var templateConcept = '#'+segmentsIRI[0]+'-detail-template';
    var displayFunction = 'display'+segmentsIRI[0].substring(0,1).toUpperCase()+segmentsIRI[0].substring(1);
    console.log(displayFunction);
    console.log(window[displayFunction]);
    window[displayFunction].call(window,'#detail', resourceIri, templateConcept);
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
      //   Mise en cache de l'URL de la source de données
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
    // Modif : L'info pertinente est dans url_array[1] ?
    displayResource(hash.substring(1));
  } else {
    var resourceId = config.resourceBaseUrl + '/ldp/project/assemblee-virtuelle/';
    displayProject('#detail', resourceId, '#project-detail-template');
  }
}

function getTemplateAjax(path, callback) {
  var source, template;
  jQuery.ajax({
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
    var element = jQuery(template);
    if (element && typeof element.attr('src') !== 'undefined') {
      getTemplateAjax(element.attr('src'), function(template) {
        jQuery(div).html(template(data));
      });
    } else {
      template = Handlebars.compile(element.html());
      jQuery(div).html(template(data));
    }
  } else {
    template = Handlebars.compile(template);
    jQuery(div).html(template({object: data}));
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
  jQuery('#card').click(function() {
    jQuery('#graph-container').hide("slow");
    jQuery('#main-container').show("slow");
    jQuery('#main-container').width("100%");
    jQuery('#main-container').height("100%");

    refreshCardFromHash();

  });

  jQuery('#graph').click(function() {
    jQuery('#main-container').hide("slow");
    jQuery('#graph-container').show("slow");
    jQuery('#graph-container').width("100%");
    jQuery('#graph-container').height("100%");
    loadGraphFromRdfViewer();
  });
}

jQuery(document).ready(function() {
  initMenu();
  loadOnClickEvent();
});
