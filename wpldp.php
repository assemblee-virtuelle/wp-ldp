<?php
/**
 * Plugin Name: WP LDP
 * Plugin URI: https://github.com/assemblee-virtuelle/wpldp
 * Description: This is a plugin which aims to emulate the default caracteristics of a Linked Data Platform compatible server
 * Text Domain: wpldp
 * Version: 1.1.1
 * Author: Sylvain LE BON, Benoit ALESSANDRONI
 * Author URI: http://www.happy-dev.fr/team/sylvain, http://benoit-alessandroni.fr/
 * License: GPL2
 */
namespace WpLdp;

// If the file is accessed outside of index.php (ie. directly), we just deny the access
defined('ABSPATH') or die("No script kiddies please!");

require_once('wpldp-utils.php');
require_once('wpldp-container-taxonomy.php');
require_once('wpldp-site-taxonomy.php');
require_once('wpldp-settings.php');
require_once('wpldp-api.php');

if (!class_exists('\WpLdp\WpLdp')) {
    class WpLdp {

      /**
       * The front page url, defaulted as 'wp-ldp/front'
       */
      protected static $front_page_url = 'wp-ldp/front';
      protected static $ldp_root_url = 'ldp/$';
      protected static $ldp_site_url = 'site\b';

      /**
       * The current plugin version number
       */
      protected static $version_number = '1.1.1';

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

        register_activation_hook( __FILE__, array($this, 'wpldp_rewrite_flush' ) );
        register_deactivation_hook( __FILE__, array($this, 'wpldp_flush_rewrite_rules_on_deactivation' ) );

        register_activation_hook( __FILE__, array($this, 'generate_menu_item') );
        register_deactivation_hook( __FILE__, array($this, 'remove_menu_item' ) );

        // Entry point of the plugin
        add_action('init', array($this, 'wpldp_plugin_update'));
        add_action( 'init', array($this, 'load_translations_file'));
        add_action( 'init', array($this, 'create_ldp_type'));
        add_action( 'init', array($this, 'add_poc_rewrite_rule'));

        add_action( 'edit_form_advanced', array($this, 'wpldp_edit_form_advanced'));
        add_action( 'save_post', array($this, 'save_ldp_meta_for_post'));

        add_filter( 'template_include', array($this, 'include_template_function'));
        add_action( 'add_meta_boxes', array($this, 'display_container_meta_box' ));
        add_action( 'add_meta_boxes', array($this, 'display_media_meta_box' ));

        add_filter( 'post_type_link', array($this, 'ldp_resource_post_link'), 10, 3 );

        add_action('admin_enqueue_scripts', array($this, 'ldp_enqueue_stylesheet'));
        add_action('admin_enqueue_scripts', array($this, 'ldp_enqueue_script'));

        add_action('wp_enqueue_scripts', array($this, 'wpldpfront_enqueue_stylesheet'));
        add_action('wp_enqueue_scripts', array($this, 'wpldpfront_enqueue_script'));
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
          if (self::$version_number >= '1.1.0') {
              //Force reinitializing the ldp containers models:
              global $wpLdpSettings;
              if ( !empty( $wpLdpSettings ) ) {
                $wpLdpSettings->initialize_container( true );
              }
              $actor_term = get_term_by('slug', 'actor', 'ldp_container');
              $person_term = get_term_by('slug', 'person', 'ldp_container');
              wp_delete_term( $actor_term->term_id, 'ldp_container', array('default' => $person_term->term_id ) );

              $project_term = get_term_by('slug', 'project', 'ldp_container');
              $initiative_term = get_term_by('slug', 'initiative', 'ldp_container');
              wp_delete_term( $project_term->term_id, 'ldp_container', array('default' => $initiative_term->term_id ) );

              $resource_term = get_term_by('slug', 'resource', 'ldp_container');
              wp_delete_term( $resource_term->term_id, 'ldp_container' );

              $idea_term = get_term_by('slug', 'idea', 'ldp_container');
              wp_delete_term( $idea_term->term_id, 'ldp_container' );
          }

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


      /**
       * load_translations_file - Loading proper text domain
       *
       * @return {type}  description
       */
      function load_translations_file() {
          $path        = dirname( plugin_basename( __FILE__ ) ) . '/languages';
          load_plugin_textdomain('wpldp', FALSE, $path);
          load_theme_textdomain('wpldp', $path);
      }


      /**
       * add_poc_rewrite_rule - Special rewriting rule for accessing the Proof of concept pagt
       *
       * @return {type}  description
       */
      public function add_poc_rewrite_rule() {
          global $wp_rewrite;
          $poc_url = plugins_url('public/index.php', __FILE__);
          $poc_url = substr($poc_url, strlen( home_url() ) + 1);
          // The pattern is prefixed with '^'
          // The substitution is prefixed with the "home root", at least a '/'
          // This is equivalent to appending it to `non_wp_rules`
          $wp_rewrite->add_external_rule(self::$front_page_url, $poc_url);
          $ldp_root_file = plugins_url('public/index-ldp_ressource.php', __FILE__);
          $ldp_root_file = substr($ldp_root_file, strlen( home_url() ) + 1);
          $wp_rewrite->add_external_rule(self::$ldp_root_url, $ldp_root_file);
          $ldp_site_file = plugins_url('public/index-ldp_site.php', __FILE__);
          $ldp_site_file = substr($ldp_site_file, strlen( home_url() ) + 1);
          $wp_rewrite->add_external_rule(self::$ldp_site_url, $ldp_site_file);
          //flush_rewrite_rules( true );
      }

      /**
       * create_ldp_type - LDP Resource post type creation and registration
       *
       * @return {type}  description
       */
      public function create_ldp_type() {
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


      /**
       * media_meta_box_callback - Specific metqbox for uploading a file to the media library from a resource edit
       *
       * @param  {type} $post description
       * @return {type}       description
       */
      public function media_meta_box_callback($post) {
          echo '<p>' . __('If you need to upload a media during your editing, click here.', 'wpldp') . '</p>';
          echo '<a href="#" class="button insert-media add-media" data-editor="content" title="Add Media">';
          echo '  <span class="wp-media-buttons-icon"></span> Add Media';
          echo '</a>';
      }

      /**
       * include_template_function - Including the template for displaying a resource
       *
       * @param  {type} $template_path description
       * @return {type}                description
       */
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

      /**
       * wpldp_edit_form_advanced - Rendering the form for entering the data
       *
       * @param  {type} $post Current post we are working on
       * @return {type} HTML Form
       */
      public function wpldp_edit_form_advanced($post) {
          if ($post->post_type == 'ldp_resource') {
              $resourceUri = WpLdpUtils::getResourceUri( $post );

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

                echo '<br>';
                echo '<div id="ldpform"></div>';
                echo '<script>';
                echo "var store = new MyStore({
                            container: '$resourceUri',
                            context: '" . get_option('ldp_context', 'http://lov.okfn.org/dataset/lov/context') ."',
                            template:\"{{{form '{$term[0]->slug}'}}}\",
                            models: $ldpModel
                      });";
                echo "var wpldp = new wpldp( store ); wpldp.init();";
                echo "wpldp.render('#ldpform', '$resourceUri', undefined, undefined, '{$term[0]->slug}');";
                echo '</script>';
              }
          }
      }


      /**
       * save_ldp_meta_for_post - Save the LDP Resource Post Meta on save
       *
       * @param  {int} $resource_id The current resource id
       * @return {type}
       */
      public function save_ldp_meta_for_post($resource_id) {
        $fields = WpLdpUtils::getResourceFieldsList($resource_id);

        if (!empty($fields)) {
          foreach($_POST as $key => $value) {
            foreach($fields as $field) {
              $field_name = \WpLdp\WpLdpUtils::getFieldName( $field );
              if ( isset( $field_name ) ) {
                if ($key === $field_name ||
                      (substr($key, 0, strlen($field_name)) === $field_name)
                  ) {
                    update_post_meta($resource_id, $key, $value);
                }
              }
            }
          }
        }
      }

      /**
       * ldp_enqueue_script - Loading requested javascript, on the admin only
       *
       * @return {type}  description
       */
      public function ldp_enqueue_script() {
          global $pagenow, $post_type;
          $screen = get_current_screen();
          if ($post_type == 'ldp_resource') {
            wp_enqueue_media();

            // Loading the LDP-framework library
            wp_register_script(
              'ldpjs',
              plugins_url('library/js/LDP-framework/ldpframework.js', __FILE__),
              array('jquery')
            );
            wp_enqueue_script('ldpjs');

            // Loading the JqueryUI library
            wp_register_script(
              'jqueryui',
              plugins_url('library/js/jquery-ui/jquery-ui.min.js', __FILE__)
            );
            wp_enqueue_script('jqueryui');

            // Loading the JSONEditor library
            wp_register_script(
              'jsoneditorjs',
              plugins_url('library/js/node_modules/jsoneditor/dist/jsoneditor.min.js', __FILE__)
            );
            wp_enqueue_script('jsoneditorjs');

            // Loading the Handlebars library
            wp_register_script(
                'handlebarsjs',
                plugins_url('library/js/handlebars/handlebars.js', __FILE__),
                array('ldpjs')
            );
            wp_enqueue_script('handlebarsjs');

            // Loading the Handlebars library
            wp_register_script(
                'select2',
                plugins_url('library/js/select2/dist/js/select2.full.min.js', __FILE__),
                array('jquery')
            );
            wp_enqueue_script('select2');

            // Loading the Plugin-javascript file
            wp_register_script(
              'wpldpjs',
              plugins_url('wpldp.js', __FILE__),
              array('jquery', 'select2')
            );
            wp_enqueue_script('wpldpjs');

            // Loading the Wikipedia autocomplete library
            wp_register_script(
                'lookup',
                plugins_url('public/resources/js/wikipedia.js', __FILE__),
                array('ldpjs')
            );
            wp_enqueue_script('lookup');
          }
      }

      /**
       * wpldpfront_enqueue_script - Method used to load all proper javascript resource on the frontend
       *
       * @return {type}  description
       */
      public function wpldpfront_enqueue_script() {
        $current_url = $_SERVER["REQUEST_URI"];
        if ( strstr( $current_url, self::$front_page_url ) ) {
        //   wp_enqueue_script('', 'https://code.jquery.com/jquery-2.1.4.min.js');

          // Loading the LDP-framework library
          wp_register_script(
            'ldpjs',
            plugins_url('library/js/LDP-framework/ldpframework.js', __FILE__),
            array('jquery')
          );
          wp_enqueue_script('ldpjs');

          // Loading the Plugin-javascript file
          wp_register_script(
            'wpldpjs',
            plugins_url('wpldp.js', __FILE__),
            array('jquery')
          );
          wp_enqueue_script('wpldpjs');

          // Loading the BootstrapJS library
          wp_register_script(
            'bootstrapjs',
            plugins_url('public/library/bootstrap/js/bootstrap.min.js', __FILE__),
            array('ldpjs')
          );
          wp_enqueue_script('bootstrapjs');

          // Loading the Handlebars library
          wp_register_script(
            'handlebarsjs',
            plugins_url('library/js/handlebars/handlebars.js', __FILE__),
            array('ldpjs')
          );
          wp_enqueue_script('handlebarsjs');

          // Loading the project specific JS library
          wp_register_script(
            'avpocjs',
            plugins_url('public/resources/js/av.js', __FILE__),
            array('ldpjs')
          );
          wp_enqueue_script('avpocjs');
        }
      }

      /**
       * ldp_enqueue_stylesheet - Loading requested stylesheet in the admin only
       *
       * @return {type}  description
       */
      public function ldp_enqueue_stylesheet() {
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

        // Loading the JQueryUI stylesheet
        wp_register_style(
          'jqueryuicss',
          plugins_url('library/js/jquery-ui/jquery-ui.css', __FILE__)
        //   'http://ajax.googleapis.com/ajax/libs/jqueryui/1.12.0/themes/base/jquery-ui.css'
        );
        wp_enqueue_style('jqueryuicss');

        // Loading the JQueryUIStructure stylesheet
        wp_register_style(
          'jqueryuistructurecss',
          plugins_url('library/js/jquery-ui/jquery-ui.structure.css', __FILE__)
        );
        wp_enqueue_style('jqueryuistructurecss');
      }


      /**
       * wpldpfront_enqueue_stylesheet - Method used to properly load specific stylesheets for the plugin frontend
       *
       * @return {type}  description
       */
      public function wpldpfront_enqueue_stylesheet() {
        $current_url = $_SERVER["REQUEST_URI"];
        if ( strstr( $current_url, self::$front_page_url ) ) {
          // Loading the WP-LDP stylesheet
          wp_register_style(
            'bootstrapcss',
            plugins_url('public/library/bootstrap/css/bootstrap.min.css', __FILE__)
          );
          wp_enqueue_style('bootstrapcss');

          // Loading the WP-LDP stylesheet
          wp_register_style(
            'font-asewomecss',
            plugins_url('public/library/font-awesome/css/font-awesome.min.css', __FILE__)
          );
          wp_enqueue_style('font-asewomecss');
        }
      }

      /**
       * generate_menu_item - Add a menu item to the primary navigation menu to access
       * the WP-ldp front page to navigate into our pairs
       *
       * @return {type}  description
       */
      public static function generate_menu_item() {
        $menu_name = 'primary';
        $locations = get_nav_menu_locations();

        if ( !empty( $locations ) && isset( $locations[ $menu_name ] ) ) {
          $menu_id = $locations[ $menu_name ] ;

          if ( !empty( $menu_id ) ) {
            wp_update_nav_menu_item(
              $menu_id,
              0,
              array(
                'menu-item-title' =>  __('Ecosystem', 'wpldp'),
                'menu-item-classes' => 'home',
                'menu-item-url' => home_url( self::$front_page_url, 'relative' ),
                'menu-item-status' => 'publish'
              )
            );
          }
        }
      }

      /**
       * remove_menu_item - Removing the additional menu item on plugin deactivation
       *
       * @return {type}  description
       */
      public static function remove_menu_item() {
        $menu_name = 'primary';
        $locations = get_nav_menu_locations();
        $menu_id = $locations[ $menu_name ] ;
        $items = wp_get_nav_menu_items( $menu_id );
        $menu_object = wp_get_nav_menu_object( $menu_id );
        foreach ( $items as $key => $item ) {
          if ( strstr( $item->url, self::$front_page_url ) ) {
            wp_delete_post( $item->ID, true );
            unset( $items[$key] );
          }
        }
      }

      /**
       * wpldp_rewrite_flush - Forcing the flush of rewrite rules on plugin activation
       * to prevent impossibility to access the LDP resources
       *
       * @return {type}  description
       */
      public function wpldp_rewrite_flush() {
        // Register post type to activate associated rewrite rules
        delete_option('rewrite_rules');
        $this->create_ldp_type();
        $this->add_poc_rewrite_rule();
        // Flush rules to be certain of the possibility to access the new CPT
        flush_rewrite_rules( true );
      }

      /**
       * wpldp_flush_rewrite_rules_on_deactivation - Same thing - for deactivation only
       *
       * @return {type}  description
       */
      public function wpldp_flush_rewrite_rules_on_deactivation() {
        flush_rewrite_rules( true );
        delete_option('rewrite_rules');
      }
    }

    $wpLdp = new WpLdp();
} else {
    exit ('Class WpLdp already exists');
}


?>
