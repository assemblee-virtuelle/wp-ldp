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

                <script>
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

                    function refreshCardFromHash() {
                      var hash = window.location.hash;
                      if (hash) {
                          displayResource(hash.substring(1, hash.length));
                      } else {
                        var resourceId = config.resourceBaseUrl + 'project/assemblee-virtuelle/';
                        displayProject('#detail', resourceId, '#project-detail-template');
                      }
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
                        refreshCardFromHash();
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
