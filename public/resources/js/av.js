////////////////////////////////////
//// Contains() polyfill method ///
///////////////////////////////////
if (!String.prototype.contains) {
    String.prototype.contains = function(s) {
        return this.indexOf(s) > -1
    }
}

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
