<?php
// Include WordPress
define('WP_USE_THEMES', true);
include_once('../../../../wp-load.php');
get_header();
?>

<style media="screen">
    #detail-wrapper {
        margin-bottom: 3rem
    }
</style>

  <!-- Actor templates -->
  <script id="person-browser-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/person/person-browser.handlebars"></script>
  <script id="person-detail-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/person/person-detail.handlebars"></script>
  <script id="person-posts-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/person/person-posts.handlebars"></script>
  <script id="person-item-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/person/person-item.handlebars"></script>
  <script id="person-list-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/person/person-list.handlebars"></script>

  <!-- Artwork templates -->
  <script id="artwork-detail-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/artwork/artwork-detail.handlebars"></script>

  <!-- Document templates -->
  <script id="document-detail-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/document/document-detail.handlebars"></script>

  <!-- Event templates -->
  <script id="event-browser-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/event/event-browser.handlebars"></script>
  <script id="event-detail-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/event/event-detail.handlebars"></script>

  <!-- GoodOrService templates -->
  <script id="goodorservice-browser-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/goodorservice/goodorservice-browser.handlebars"></script>
  <script id="goodorservice-detail-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/goodorservice/goodorservice-detail.handlebars"></script>

  <!-- Group templates -->
  <script id="group-browser-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/group/group-browser.handlebars"></script>
  <script id="group-detail-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/group/group-detail.handlebars"></script>
  <script id="group-posts-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/group/group-posts.handlebars"></script>
  <script id="group-item-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/group/group-item.handlebars"></script>

  <!-- Post templates -->
  <script id="post-item-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/post/post-item.handlebars"></script>

  <!-- Project templates -->
  <script id="project-list-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/project/project-list.handlebars"></script>

  <!-- Resources templates -->
  <script id="resource-browser-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/resource/resource-browser.handlebars"></script>
  <script id="resource-item-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/resource/resource-item.handlebars"></script>

  <!-- Ideas templates -->
  <script id="idea-browser-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/idea/idea-browser.handlebars"></script>
  <script id="idea-item-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/idea/idea-item.handlebars"></script>

  <!-- Theme templates -->
  <script id="theme-browser-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/theme/theme-browser.handlebars"></script>
  <script id="theme-detail-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/theme/theme-detail.handlebars"></script>

  <!-- Thesis templates -->
  <script id="thesis-browser-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/thesis/thesis-browser.handlebars"></script>
  <script id="thesis-detail-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/thesis/thesis-detail.handlebars"></script>

  <!-- Event templates -->
  <script id="event-browser-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/event/event-browser.handlebars"></script>

  <!-- Initiative templates -->
  <script id="initiative-browser-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/initiative/initiative-browser.handlebars"></script>
  <script id="initiative-item-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/initiative/initiative-item.handlebars"></script>
  <script id="initiative-detail-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/initiative/initiative-detail.handlebars"></script>
  <script id="initiative-list-template" type="text/x-handlebars-template" src="<?=plugin_dir_url( __FILE__ )?>templates/initiative/initiative-list.handlebars"></script>

  <script>
      function getInitiativesList() {
        var initiativesList = [];

        var url = config.resourceBaseUrl + 'ldp/initiative/';
        store.get(url).then(function(object) {
          if (object['ldp:contains']) {
            jQuery.each(object['ldp:contains'], function(index, project) {
              store.get(project).then(function(data) {
                if ( data['foaf:name'] ) {
                  var currentProject = {
                    'id' : data['@id'],
                    'title' : data['foaf:name'],
                    'description' : data['pair:shortDescription'].substring(0, 147) + '...'
                  };
                  initiativesList.push(data);
                  displayTemplate('#initiative-list-template', '#detail', initiativesList);
                }
              });
            });
          } else {
            displayTemplate('#initiative-list-template', '#detail', undefined);
          }
        });
      }

      function getPersonsList() {
        var personsList = [];

        var url = config.resourceBaseUrl + 'ldp/person/';
        store.get(url).then(function(object) {
          if (object['ldp:contains']) {
            jQuery.each(object['ldp:contains'], function(index, project) {
              store.get(project).then(function(data) {
                if ( data['foaf:name'] ) {
                  // var currentProject = {
                  //   'id' : data['@id'],
                  //   'title' : data['foaf:name'],
                  //   'description' : data['foaf:shortDescription'].substring(0, 147) + '...'
                  // };
                  personsList.push(data);
                  displayTemplate('#person-list-template', '#person-detail', personsList);
                }
              });
            });
          } else {
            displayTemplate('#person-list-template', '#person-detail', undefined);
          }
        });
      }

    //   function getDocumentsList() {
    //     var resourcesList = [];
      //
    //     var url = config.resourceBaseUrl + 'ldp/document/';
    //     store.get(url).then(function(object) {
    //       if (object['ldp:contains']) {
    //         jQuery.each(object['ldp:contains'], function(index, project) {
    //           store.get(project).then(function(data) {
    //             if ( data['pair:name'] ) {
    //               var currentDocument = {
    //                 'id' : data['@id'],
    //                 'title' : data['pair:name'],
    //                 'description' : data['pair:shortDescription'].substring(0, 147) + '...'
    //               };
    //               resourcesList.push(data);
    //               displayTemplate('#resource-browser-template', '#resource-browser', resourcesList);
    //             }
    //           });
    //         });
    //       } else {
    //         displayTemplate('#resource-browser-template', '#resource-browser', undefined);
    //       }
    //     });
    //   }

    /**
     * Bootstrap de la collecte de données pour la page
     */
      jQuery(document).ready(function(){
          console.log('<?php echo get_option("ldp_context", "http://lov.okfn.org/dataset/lov/context"); ?>');
          window.config = {
            'containerUrl': '<?php echo site_url(); ?>/ldp_container/',
            'resourceBaseUrl' : '<?php echo site_url(); ?>/',
            'contextUrl': '<?php echo get_option("ldp_context", "http://lov.okfn.org/dataset/lov/context"); ?>'
          }

          Handlebars.registerHelper("log", function(something) {
              console.log(something);
          });

          window.store = new MyStore({
              container: config.containerUrl + 'person/',
              context: config.contextUrl,
              template: '#person-detail-template',
              partials: {
                'personItem': '#person-item-template'
                , 'personDetail': '#person-detail-template'
                , 'initiativeItem': '#initiative-item-template'
                , 'initiativeDetail': '#initiative-detail-template'
                , 'postItem': '#post-item-template'
                , 'ideaItem': '#idea-item-template'
                , 'resourceItem': '#resource-item-template'
              }
          });

          if ( window.location.hash ) {
            refreshCardFromHash();
          } else {
            getInitiativesList();
            getPersonsList();
            // getDocumentsList();
          }
      });

      jQuery(window).on('hashchange', function() {
        refreshCardFromHash();
      });
  </script>
  <div id="wrapper">
    <div id="content-wrapper">
      <div id="main-container" class="container-fluid">
        <div id="detail-wrapper" class="col-md-9">
            <div id="detail"></div>
            <div id="person-detail"></div>
        </div>
        <div id="browser" class="col-md-3">
          <div id="initiative-browser" class="row"></div>
          <div id="person-browser" class="row"></div>
          <div id="idea-browser" class="row"></div>
          <div id="resource-browser" class="row"></div>
          <div id="event-browser" class="row"></div>
          <div id="theme-browser" class="row"></div>
        </div>
      </div>
      <div id="graph-container" style="display: none;" height="1000px">
        <svg id="chart" width="1000px" height="1000px"></svg>
      </div>
    </div>
  </div>
<?php
      get_footer();
?>
