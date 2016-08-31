<?php
// Include WordPress
define('WP_USE_THEMES', true);
include_once('../../../../wp-load.php');

get_header();
?>
        <!DOCTYPE html>
        <html>
            <head>
                <title>Virtual-assembly proof of concept</title>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <link href="../wp-content/plugins/wp-ldp/public/library/bootstrap/css/bootstrap.min.css" rel="stylesheet">
                <link href="../wp-content/plugins/wp-ldp/public/resources/css/sidebar.css" rel="stylesheet">
                <link href="../wp-content/plugins/wp-ldp/public/library/font-awesome/css/font-awesome.min.css" rel="stylesheet">
                <link href="../wp-content/plugins/wp-ldp/public/resources/css/av.css" type="text/css" rel="stylesheet" />

                <script type="text/javascript" src="../wp-content/plugins/wp-ldp/public/library/jquery/jquery.min.js"></script>
                <script type="text/javascript" src="../wp-content/plugins/wp-ldp/library/js/LDP-framework/ldpframework.js"></script>
                <script type="text/javascript" src="../wp-content/plugins/wp-ldp/library/js/handlebars/handlebars.js"></script>
                <script type="text/javascript" src="../wp-content/plugins/wp-ldp/public/library/bootstrap/js/bootstrap.min.js"></script>
                <script type="text/javascript" src="../wp-content/plugins/wp-ldp/public/resources/js/av.js"></script>

                <!-- Project templates -->
                <script id="project-browser-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/project/project-browser.handlebars"></script>
                <script id="project-detail-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/project/project-detail.handlebars"></script>
                <script id="project-item-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/project/project-item.handlebars"></script>

                <!-- Actor templates -->
                <script id="actor-browser-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/actor/actor-browser.handlebars"></script>
                <script id="actor-detail-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/actor/actor-detail.handlebars"></script>
                <script id="actor-posts-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/actor/actor-posts.handlebars"></script>
                <script id="actor-item-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/actor/actor-item.handlebars"></script>

                <!-- Post templates -->
                <script id="post-item-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/post/post-item.handlebars"></script>

                <!-- Project templates -->
                <script id="project-list-template" type="text/x-handlebars-template" src="../wp-content/plugins/wp-ldp/public/templates/project/project-list.handlebars"></script>

                <script>
                    function getProjectsList() {
                      var projectsList = [];

                      var url = config.resourceBaseUrl + 'ldp/project/';
                      store.get(url).then(function(object) {
                        if (object['ldp:contains']) {
                          $.each(object['ldp:contains'], function(index, project) {
                            store.get(project).then(function(data) {
                              if (data.project_title && data.project_description) {
                                var currentProject = {
                                  'id' : data['@id'],
                                  'title' : data.project_title,
                                  'description' : data.project_description.substring(0, 147) + '...'
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

                    $(function(){
                        window.config = {
                          'containerUrl': '<?php echo site_url(); ?>/ldp_container/',
                          'resourceBaseUrl' : '<?php echo site_url(); ?>/',
                          'contextUrl': 'http://owl.openinitiative.com/oicontext.jsonld'
                        }

                        window.store = new MyStore({
                            container: config.containerUrl + 'actor/',
                            context: config.contextUrl,
                            template: '#actor-detail-template',
                            partials: {
                              'actorItem': '#actor-item-template',
                              'actorDetail': '#actor-detail-template',
                              'projectItem': '#project-item-template',
                              'projectDetail': '#project-detail-template',
                              'postItem': '#post-item-template'
                            }
                        });

                        if ( window.location.hash ) {
                          refreshCardFromHash();
                        } else {
                          getProjectsList();
                        }
                    });

                    $(window).on('hashchange', function() {
                      refreshCardFromHash();
                    });
                </script>
            </head>
            <body>
              <div id="wrapper">
                <!-- Sidebar -->
                <div id="sidebar-wrapper">
                  <ul class="sidebar-nav nav-pills nav-stacked" id="menu">
                    <li class="active">
                        <a href="#" id="menu"><span class="fa-stack fa-lg pull-left"><i class="fa fa-bars fa-stack-1x "></i></span></a>
                    </li>
                    <li>
                        <a href="#" id="graph"><span class="fa-stack fa-lg pull-left"><i class="fa fa-search fa-stack-1x "></i></span></a>
                    </li>
                    <li>
                        <a href="#" id="map"><span class="fa-stack fa-lg pull-left"><i class="fa fa-map-o fa-stack-1x "></i></span></a>
                    </li>
                    <li>
                        <a href="#" id="card"><span class="fa-stack fa-lg pull-left"><i class="fa fa-info-circle fa-stack-1x "></i></span></a>
                    </li>
                    <li>
                        <a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-reply fa-stack-1x "></i></span></a>
                    </li>
                  </ul>
                </div>
                <div id="content-wrapper">
                  <div id="main-container" class="container-fluid">
                    <div id="detail-wrapper" class="col-md-9">
                        <div id="detail"></div>
                    </div>
                    <div id="browser" class="col-md-3">
                      <div id="project-browser" class="row"></div>
                      <div id="actor-browser" class="row"></div>
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
