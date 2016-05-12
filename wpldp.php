<?php
/**
 * Plugin Name: WP LDP
 * Plugin URI: https://github.com/Open-Initiative/wpldp
 * Description: This is a test for LDP
 * Text Domain: wpldp
 * Version: 0.9
 * Author: Sylvain LE BON, Benoit ALESSANDRONI
 * Author URI: http://www.happy-dev.fr/team/sylvain, http://benoit-alessandroni.fr/
 * License: GPL2
 */

// If the file is accessed outside of index.php (ie. directly), we just deny the access
defined('ABSPATH') or die("No script kiddies please!");

require_once('wpldp-utils.php');
require_once('wpldp-taxonomy.php');
require_once('wpldp-settings.php');

if (!class_exists('WpLdp')) {
    class WpLdp {

      protected static $version_number = '1.0.1';

      /**
       * Default Constructor
       **/
      public function __construct() {
        if ( get_magic_quotes_gpc() ) {
            $_POST      = array_map( 'stripslashes_deep', $_POST );
            $_GET       = array_map( 'stripslashes_deep', $_GET );
            $_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
            $_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );
        }

        // Entry point of the plugin
        add_action('init', array($this, 'wpldp_plugin_update'));
        add_action( 'init', array($this, 'load_translations_file'));
        add_action( 'init', array($this, 'create_ldp_type'));
        add_action( 'init', array($this, 'add_poc_rewrite_rule'));
        add_action( 'init', array($this, 'register_connection_types'));
        add_action( 'edit_form_advanced', array($this, 'wpldp_edit_form_advanced'));
        add_action( 'save_post', array($this, 'save_ldp_meta_for_post'));

        add_filter( 'template_include', array($this, 'include_template_function'));
        add_action( 'template_redirect', array($this, 'my_page_template_redirect' ));
        add_action( 'add_meta_boxes', array($this, 'display_container_meta_box' ));
        add_action( 'add_meta_boxes', array($this, 'display_media_meta_box' ));

        add_action( 'admin_footer', array($this, 'my_action_javascript' )); // Write our JS below here

        add_filter( 'post_type_link', array($this, 'ldp_resource_post_link'), 10, 3 );

        add_action('admin_enqueue_scripts', array($this, 'ldp_enqueue_script'));
        add_action('admin_enqueue_scripts', array($this, 'ldp_enqueue_stylesheet'));


      }


      /**
       * wpldp_plugin_update - Automatic database upgrade mechanism, planned for the future
       *
       * @return {boolean}  Update validation
       */
      function wpldp_plugin_update() {
        $plugin_version = get_option('wpldp_version');
        $update_option = null;

        if (self::$version_number !== $plugin_version) {
          if (self::$version_number > $plugin_version) {
            $update_option = $this->wpldp_db_upgrade();

            if ($update_option) {
              update_option('wpldp_version', self::$version_number);
            }
          }
        }
      }

      private function wpldp_db_upgrade() {
        $flush_cache = wp_cache_flush();
        global $wpdb;
        $wpdb->query(
          "UPDATE $wpdb->postmeta
              SET `meta_key` = replace( `meta_key` , 'ldp_', '' );"
        );

        $wpdb->query(
           "DELETE FROM $wpdb->options
            WHERE `option_name` LIKE '%transient%';"
        );

        $result = $wpdb->get_results(
          "SELECT `option_name`
           FROM $wpdb->options
           WHERE `option_name` LIKE '%ldp_container_%';"
        );

        foreach ( $result as $current ) {
          $option = get_option($current->option_name);
          if (!empty($option)) {
            if (!empty($option['ldp_model'])) {
              $option['ldp_model'] = str_replace('ldp_', '', $option['ldp_model']);
            }

            if (!empty($option['ldp_included_fields_list'])) {
              $option['ldp_included_fields_list'] = str_replace('ldp_', '', $option['ldp_included_fields_list']);
            }
            update_option($current->option_name, $option, false);
          }
        }

        $flush_cache = wp_cache_flush();
        return true;
      }

      #####################################
      # Loading translations file
      #####################################
      function load_translations_file() {
          $path        = dirname( plugin_basename( __FILE__ ) ) . '/languages';
          load_plugin_textdomain('wpldp', FALSE, $path);
          load_theme_textdomain('wpldp', $path);
      }

      #####################################
      # Rewriting function to access the POC from Wordpress
      #####################################
      function add_poc_rewrite_rule() {
          global $wp_rewrite;
          $poc_url = plugins_url('public/index.html', __FILE__);
          $poc_url = substr($poc_url, strlen( home_url() ) + 1);
          // The pattern is prefixed with '^'
          // The substitution is prefixed with the "home root", at least a '/'
          // This is equivalent to appending it to `non_wp_rules`
          $wp_rewrite->add_external_rule('av-poc.php', $poc_url);
      }

      function my_page_template_redirect() {
          if( is_page( 'av-poc' ) )
          {
              wp_redirect(plugins_url('public/index.html', __FILE__));
              exit();
          }
      }
      ##########################################
      # LDP Resource content type definition
      ##########################################
      function create_ldp_type() {
          register_post_type('ldp_resource',
              array(
                  'labels'  => array(
                      'name'              => __('Resources', 'wpldp'),
                      'singular_name'     => __('Resource', 'wpldp'),
                      'all_items'         => __('All resources', 'wpldp'),
                      'add_new_item'      => __('Add a resource', 'wpldp'),
                      'edit_item'         => __('Edit a resource', 'wpldp'),
                      'new_item'          => __('New resource', 'wpldp'),
                      'view_item'         => __('See the resource', 'wpldp'),
                      'search_items'      => __('Search for a resource', 'wpldp'),
                      'not_found'         => __('No corresponding resource', 'wpldp'),
                      'not_found_in_trash'=> __('No corresponding resource in the trash', 'wpldp'),
                      'add_new'           => __('Add a resource', 'wpldp'),
                  ),
                  'description'           => __('LDP Resource', 'wpldp'),
                  'public'                => true,
                  'show_in_nav_menu'      => true,
                  'show_in_menu'          => true,
                  'show_in_admin_bar'     => true,
                  'supports'              => array('title'),
                  'has_archive'           => true,
                  'rewrite'               => array('slug' => 'ldp/%ldp_container%'),
                  'menu_icon'             => 'dashicons-image-filter',
          ));
          flush_rewrite_rules();
      }

      /**
       	 * Add custom filter for handling the custom permalink
       	 *
       	 * @param type
       	 * @return void
      	 */
      function ldp_resource_post_link( $post_link, $id = 0 ){
          $post = get_post($id);

          if ( 'ldp_resource' == get_post_type( $post ) ) {
            if (is_object($post)){
              $terms = wp_get_object_terms( $post->ID, 'ldp_container' );
              if (!empty($terms)) {
                  return str_replace('%ldp_container%', $terms[0]->slug, $post_link);
              }
            }
          }

          return $post_link;
      }

      /**
       	 * Remove the original meta box on the ldp_resource edition page and
         * replace it with radio buttons selectors to avoid multiple selection
       	 *
       	 * @param type
       	 * @return void
      	 */
      function display_container_meta_box( $post_type ) {
        remove_meta_box( 'ldp_containerdiv', $post_type, 'side' );

        if( $post_type == 'ldp_resource' ) :
          add_meta_box(
            'ldp_containerdiv',
            __('Containers', 'wpldp'),
            array($this, 'container_meta_box_callback'),
            $post_type,
            'normal',
            'high'
          );

        endif;
      }

      /**
         * Generate the HTML for the radio button based meta box
         *
         * @param type
         * @return void
         */
      function container_meta_box_callback($post) {
        wp_nonce_field(
          'wpldp_save_container_box_data',
          'wpldp_container_box_nonce'
        );

        $value = get_the_terms($post->ID, 'ldp_container')[0];
        $terms = get_terms('ldp_container', array('hide_empty' => 0));
        echo '<ul>';
        foreach($terms as $term) {
          echo '<li id="ldp_container-' . $term->term_id . '" class="category">';
          echo '<label class="selectit">';
          if (!empty($value) && $term->term_id == $value->term_id) {
            echo '<input id="in-ldp_container-' . $term->term_id . '" type="radio" name="tax_input[ldp_container][]" value="' . $term->term_id . '" checked>';
          } else {
            echo '<input id="in-ldp_container-' . $term->term_id . '" type="radio" name="tax_input[ldp_container][]" value="' . $term->term_id . '">';
          }
          echo $term->name;
          echo '</input>';
          echo '</label>';
          echo '</li>';
        }
        echo "</ul>";
      }

      /**
         * Add an access to the media library from the ldp_resource edition page
         *
         * @param type
         * @return void
         */
      function display_media_meta_box ( $post_type ) {
        if ( $post_type == 'ldp_resource' ) {
          add_meta_box(
            'ldp_mediadiv',
            __('Media', 'wpldp'),
            array($this, 'media_meta_box_callback'),
            $post_type,
            'side'
          );
        }
      }

      function media_meta_box_callback($post) {
          echo '<p>' . __('If you need to upload a media during your editing, click here.', 'wpldp') . '</p>';
          echo '<a href="#" class="button insert-media add-media" data-editor="content" title="Add Media">';
          echo '  <span class="wp-media-buttons-icon"></span> Add Media';
          echo '</a>';
      }

      /**
       	 * Register the Post 2 posts available connection between custom content types
       	 *
       	 * @param type
       	 * @return void
      	 */
      function register_connection_types() {
        // p2p_register_connection_type(
        //   array(
        //     'name' => 'resource_to_user',
        //     'from' => 'ldp_resource',
        //     'to' => 'user',
        //     'admin_box' => array(
        //       'show' => 'any',
        //       'context' => 'side'
        //     )
        //   )
        // );


      }

      ################################
      # Resource publication
      ################################
      function include_template_function( $template_path ) {
          if ( get_post_type() == 'ldp_resource' ) {
              if ( is_single() ) {
                  // checks if the file exists in the theme first,
                  // otherwise serve the file from the plugin
                  if ( $theme_file = locate_template( array ( 'single-ldp_resource.php' ) ) ) {
                      $template_path = $theme_file;
                  } else {
                      $template_path = plugin_dir_path( __FILE__ ) . 'single-ldp_resource.php';
                  }
              }

              else {
                  // checks if the file exists in the theme first,
                  // otherwise serve the file from the plugin
                  if ( $theme_file = locate_template( array ( 'archive-ldp_resource.php' ) ) ) {
                      $template_path = $theme_file;
                  } else {
                      $template_path = plugin_dir_path( __FILE__ ) . 'archive-ldp_resource.php';
                  }
              }
          }

          return $template_path;
      }

      ################################
      # Admin form
      ################################
      function wpldp_edit_form_advanced($post) {
          if ($post->post_type == 'ldp_resource') {
              $resourceUri = WpLdpUtils::getResourceUri($post);

              $term = get_the_terms($post->post_id, 'ldp_container');
              if (!empty($term) && !empty($resourceUri)) {
                $termId = $term[0]->term_id;
                $termMeta = get_option("ldp_container_$termId");

                if (empty($termMeta) || !isset($termMeta['ldp_model'])) {
                  $ldpModel = '{"people":
                      {"fields":
                        [{
                          "title": "What\'s your name?",
                          "name": "ldp_name"
                        },
                        {
                          "title": "Who are you?",
                          "name": "ldp_description"
                        }]
                      }
                    }';
                } else {
                  $ldpModel = json_encode(json_decode($termMeta['ldp_model']));
                }

                echo('<br>');
                echo '<div id="ldpform"></div>';
                echo '<script>';
                echo "var store = new MyStore({
                            container: '$resourceUri',
                            context: '" . get_option('ldp_context', 'http://owl.openinitiative.com/oicontext.jsonld') ."',
                            template:\"{{{form '{$term[0]->slug}'}}}\",
                            models: $ldpModel
                      });";
                echo "store.render('#ldpform', '$resourceUri', undefined, undefined, '{$term[0]->slug}');";

                // echo "var actorsList = store.list('/ldp_container/actor/');";
                // echo "console.log(actorsList);";
                echo '</script>';
              }
          }
      }

      function save_ldp_meta_for_post($resource_id) {
        $fields = WpLdpUtils::getResourceFieldsList($resource_id);

        if (!empty($fields)) {
          foreach($_POST as $key => $value) {
            foreach($fields as $field) {
              if ($key === $field->name) {
                  update_post_meta($resource_id, $key, $value);
              }
            }
          }
        }
      }

      function ldp_enqueue_script() {
          global $pagenow, $post_type;
          $screen = get_current_screen();
          if ($post_type == 'ldp_resource') {
            wp_enqueue_media();
            wp_enqueue_script('', 'https://code.jquery.com/jquery-2.1.4.min.js');

            // Loading the LDP-framework library
            wp_register_script(
              'ldpjs',
              plugins_url('library/js/LDP-framework/ldpframework.js', __FILE__),
              array('jquery')
            );
            wp_enqueue_script('ldpjs');

            // Loading the JSONEditor library
            wp_register_script(
              'jsoneditorjs',
              plugins_url('library/js/node_modules/jsoneditor/dist/jsoneditor.min.js', __FILE__)
            );
            wp_enqueue_script('jsoneditorjs');

            // Loading the Plugin-javascript file
            wp_register_script(
              'wpldpjs',
              plugins_url('wpldp.js', __FILE__),
              array('jquery')
            );
            wp_enqueue_script('wpldpjs');
          }
      }

      function ldp_enqueue_stylesheet() {
        // Loading the WP-LDP stylesheet
        wp_register_style(
          'wpldpcss',
          plugins_url('resources/css/wpldp.css', __FILE__)
        );
        wp_enqueue_style('wpldpcss');

        // Loading the JSONEditor stylesheet
        wp_register_style(
          'jsoneditorcss',
          plugins_url('library/js/node_modules/jsoneditor/dist/jsoneditor.min.css', __FILE__)
        );
        wp_enqueue_style('jsoneditorcss');
      }

      #############################
      #       FOOTER SCRIPT
      #############################
      function my_action_javascript() { ?>
      	<script type="text/javascript" >
      	jQuery(document).ready(function($) {

      	});
      	</script> <?php
      	}
    }
} else {
    exit ('Class WpLdp already exists');
}

$wpLdp = new WpLdp();

?>
