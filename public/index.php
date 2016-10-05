<?php
// Include WordPress
define('WP_USE_THEMES', true);
include_once('../../../../wp-load.php');

get_header();
?>
  <!-- Project templates -->
  <script id="project-browser-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/project/project-browser.handlebars"></script>
  <script id="project-detail-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/project/project-detail.handlebars"></script>
  <script id="project-item-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/project/project-item.handlebars"></script>

  <!-- Actor templates -->
  <script id="person-browser-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/person/person-browser.handlebars"></script>
  <script id="person-detail-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/person/person-detail.handlebars"></script>
  <script id="person-posts-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/person/person-posts.handlebars"></script>
  <script id="person-item-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/person/person-item.handlebars"></script>

  <!-- Group templates -->
  <script id="group-browser-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/group/group-browser.handlebars"></script>
  <script id="group-detail-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/group/group-detail.handlebars"></script>
  <script id="group-posts-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/group/group-posts.handlebars"></script>
  <script id="group-item-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/group/group-item.handlebars"></script>

  <!-- Post templates -->
  <script id="post-item-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/post/post-item.handlebars"></script>

  <!-- Project templates -->
  <script id="project-list-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/project/project-list.handlebars"></script>

  <!-- Resources templates -->
  <script id="resource-browser-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/resource/resource-browser.handlebars"></script>

  <!-- Ideas templates -->
  <script id="idea-browser-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/idea/idea-browser.handlebars"></script>

  <script>
      function getProjectsList() {
        var projectsList = [];

        var url = config.resourceBaseUrl + 'ldp/project/';
        store.get(url).then(function(object) {
          if (object['ldp:contains']) {
            jQuery.each(object['ldp:contains'], function(index, project) {
              store.get(project).then(function(data) {
                if (data.project_title && data.project_description) {
                  var currentProject = {
                    'id' : data['@id'],
                    'title' : data['foaf:name'],
                    'description' : data['foaf:shortDescription'].substring(0, 147) + '...'
                  };
                  projectsList.push(currentProject);
                  displayTemplate('#project-list-template', '#detail', projectsList);
                }
              });
            });
          } else {
            displayTemplate('#project-list-template', '#detail', undefined);
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
                if (data.project_title && data.project_description) {
                  var currentProject = {
                    'id' : data['@id'],
                    'title' : data['foaf:name'],
                    'description' : data['foaf:shortDescription'].substring(0, 147) + '...'
                  };
                  projectsList.push(currentProject);
                  displayTemplate('#person-list-template', '#person-detail', personsList);
                }
              });
            });
          } else {
            displayTemplate('#person-list-template', '#person-detail', undefined);
          }
        });
      }

      jQuery(function(){
          window.config = {
            'containerUrl': '<?php echo site_url(); ?>/ldp_container/',
            'resourceBaseUrl' : '<?php echo site_url(); ?>/',
            'contextUrl': 'http://owl.openinitiative.com/oicontext.jsonld'
          }

          window.store = new MyStore({
              container: config.containerUrl + 'person/',
              context: config.contextUrl,
              template: '#person-detail-template',
              partials: {
                'personItem': '#person-item-template',
                'personDetail': '#person-detail-template',
                'projectItem': '#project-item-template',
                'projectDetail': '#project-detail-template',
                'postItem': '#post-item-template'
              }
          });

          if ( window.location.hash ) {
            refreshCardFromHash();
          } else {
            getProjectsList();
            getPersonsList();
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
          <div id="project-browser" class="row"></div>
          <div id="person-browser" class="row"></div>
          <div id="resource-browser" class="row"></div>
          <div id="idea-browser" class="row"></div>
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
