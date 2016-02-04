<?php
/**
 * Plugin Name: WP LDP
 * Plugin URI: http://www.happy-dev.fr
 * Description: This is a test for LDP
 * Text Domain: wpldp
 * Version: 0.1
 * Author: Sylvain LE BON
 * Author URI: http://www.happy-dev.fr/team/sylvain
 * License: GPL2
 */

// If the file is accessed outside of index.php (ie. directly), we just deny the access
defined('ABSPATH') or die("No script kiddies please!");

if (!class_exists('WpLdp')) {
    class WpLdp {
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
        add_action( 'init', array($this, 'load_translations_file'));
        add_action( 'init', array($this, 'create_ldp_type'));
        add_action( 'init', array($this, 'add_poc_rewrite_rule'));
        add_action( 'init', array($this, 'register_connection_types'));
        add_action( 'edit_form_after_title', array($this, 'myprefix_edit_form_after_title'));
        add_action( 'save_post', array($this, 'test_save'));
        add_action( 'admin_menu', array($this, 'ldp_menu'));
        add_action( 'admin_init', array($this, 'backend_hooking'));
        //add_action('update_option', 'initialize_container');

        add_filter( 'template_include', array($this, 'include_template_function'));
        add_action( 'template_redirect', array($this, 'my_page_template_redirect' ));
        add_action( 'add_meta_boxes', array($this, 'display_container_meta_box' ));
        add_action( 'init', array($this, 'register_container_taxonomy'), 0 );

        add_action( 'ldp_container_add_form_fields', array($this, 'add_custom_tax_fields_oncreate'));

        add_action( 'ldp_container_edit_form_fields', array($this, 'add_custom_tax_fields_onedit'));
        add_action( 'create_ldp_container', array($this, 'save_custom_tax_field'));
        add_action( 'edited_ldp_container', array($this, 'save_custom_tax_field'));
        add_action( 'admin_footer', array($this, 'my_action_javascript' )); // Write our JS below here

        add_filter( 'post_type_link', array($this, 'ldp_resource_post_link'), 10, 3 );

      }


      function initialize_container($option, $oldValue, $_newValue) {
        if ($option === 'ldp_container_init') {
          var_dump("This is the good option update");
          die();
        }
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
            'side'
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
          elseif ( is_tax('ldp_container') ) {
              // checks if the file exists in the theme first,
              // otherwise serve the file from the plugin
              if ( $file_theme = locate_template( array ( 'taxonomy-ldp_resource.php' ) ) ) {
                  $template_path = $theme_file;
              } else {
                  $template_path = plugin_dir_path( __FILE__ ) . 'taxonomy-ldp_resource.php';
              }

          }
          return $template_path;
      }

      ################################
      # Admin form
      ################################
      function myprefix_edit_form_after_title($post) {
          if ($post->post_type == 'ldp_resource') {
              $container = get_permalink();
              $term = get_the_terms($post->post_id, 'ldp_container');
              if (!empty($term)) {
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
                            container: '$container',
                            context: '" . get_option('ldp_context', 'http://owl.openinitiative.com/oicontext.jsonld') ."',
                            template:\"{{{form '{$term[0]->slug}'}}}\",
                            models: $ldpModel
                      });";
                echo "store.render('#ldpform', '$container', undefined, undefined, '{$term[0]->slug}', 'ldp_');";
                echo "var actorsList = store.list('/ldp_container/actor/');";
                echo "console.log(actorsList);";
                echo '</script>';
              }
          }
      }

      function test_save($resource_id) {
          foreach($_POST as $key => $value) {
              if(substr($key, 0, 4) == "ldp_") {
                  update_post_meta($resource_id, $key, $value);
              }
          }
      }

      function ldp_enqueue_script() {
          wp_enqueue_script('', 'https://code.jquery.com/jquery-2.1.4.min.js');

          // Loading the LDP-framework library
          wp_register_script(
            'ldpjs',
            plugins_url('library/js/LDP-framework/mystore.js', __FILE__),
            array('jquery')
          );
          wp_enqueue_script('ldpjs');

          // Loading the JSONEditor library
          wp_register_script(
            'jsoneditorjs',
            plugins_url('library/js/node_modules/jsoneditor/dist/jsoneditor.min.js', __FILE__)
          );
          wp_enqueue_script('jsoneditorjs');
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

      function backend_hooking() {
          add_action('admin_enqueue_scripts', array($this, 'ldp_enqueue_script'));
          add_action('admin_enqueue_scripts', array($this, 'ldp_enqueue_stylesheet'));
          add_settings_section(
            'ldp_context',
            __('WP-LDP Settings', 'wpldp'),
            function() {
              echo __('The generals settings of the WP-LDP plugin.', 'wpldp');
            },
            'wpldp'
          );

          add_settings_field(
            'ldp_context',
            __('WP-LDP Context', 'wpldp'),
            array($this, 'ldp_context_field'),
            'wpldp',
            'ldp_context'
          );

          add_settings_field(
            'ldp_container_init',
            __('Do you want to initialize PAIR containers ?', 'wpldp'),
            array($this, 'ldp_container_init_field'),
            'wpldp',
            'ldp_context'
          );

          register_setting( 'ldp_context', 'ldp_context' );
          register_setting( 'ldp_container_init', 'ldp_context' );
      }

      ################################
      # Taxonomy
      ################################
      // Register Custom Taxonomy
      function register_container_taxonomy() {

      	$labels = array(
      		'name'                       => __( 'Containers', 'wpldp' ),
      		'singular_name'              => __( 'Container', 'wpldp' ),
      		'menu_name'                  => __( 'Containers', 'wpldp' ),
      		'all_items'                  => __( 'All Items', 'wpldp' ),
      		'parent_item'                => __( 'Parent Item', 'wpldp' ),
      		'parent_item_colon'          => __( 'Parent Item:', 'wpldp' ),
      		'new_item_name'              => __( 'New Item Name', 'wpldp' ),
      		'add_new_item'               => __( 'Add New Item', 'wpldp' ),
      		'edit_item'                  => __( 'Edit Item', 'wpldp' ),
      		'update_item'                => __( 'Update Item', 'wpldp' ),
      		'view_item'                  => __( 'View Item', 'wpldp' ),
      		'separate_items_with_commas' => __( 'Separate items with commas', 'wpldp' ),
      		'add_or_remove_items'        => __( 'Add or remove items', 'wpldp' ),
      		'choose_from_most_used'      => __( 'Choose from the most used', 'wpldp' ),
      		'popular_items'              => __( 'Popular Items', 'wpldp' ),
      		'search_items'               => __( 'Search Items', 'wpldp' ),
      		'not_found'                  => __( 'Not Found', 'wpldp' ),
      	);
      	$rewrite = array(
      		'slug'                       => '',
      		'with_front'                 => false,
      		'hierarchical'               => false,
      	);
      	$args = array(
      		'labels'                     => $labels,
      		'hierarchical'               => true,
      		'public'                     => true,
      		'show_ui'                    => true,
      		'show_admin_column'          => true,
      		'show_in_nav_menus'          => true,
      		'show_tagcloud'              => true,
      		// 'rewrite'                    => $rewrite,
      	);
      	register_taxonomy( 'ldp_container', 'ldp_resource', $args );

      }

      /**
       	 * Adds a LDP Model field to our custom LDP containers taxonomy
         * in creation mode
         *
       	 * @param int $term the concrete term
       	 * @return void
      	 */
      function add_custom_tax_fields_oncreate($term) {
        echo "<div class='form-field form-required term-model-wrap'>";
        echo "<label for='ldp_model'>" . __('Model', 'wpldp'). "</label>";
        echo "<textarea id='ldp_model' type='text' name='ldp_model' cols='40' rows='20'></textarea>";
        echo "<p class='description'>" . __('The LDP-compatible JSON Model for this container', 'wpldp'). "</p>";
        echo "</div>";
      }

      /**
         * Adds a LDP Model field to our custom LDP containers taxonomy
         * in edition mode
         *
       	 * @param int $term the concrete term
       	 * @return void
      	 */
      function add_custom_tax_fields_onedit($term) {
        $termId = $term->term_id;
        $termMeta = get_option("ldp_container_$termId");
        $ldpModel = stripslashes_deep($termMeta['ldp_model']);

        echo "<tr class='form-field form-required term-model-wrap'>";
        echo "<th scope='row'><label for='ldp_model_editor'>" . __('Model editor mode', 'wpldp'). "</label></th>";
        echo "<td><div id='ldp_model_editor' style='width: 1000px; height: 400px;'></div></td>";
        echo "<p class='description'>" . __('The LDP-compatible JSON Model for this container', 'wpldp'). "</p></td>";
        echo "</tr>";
        echo "<input type='hidden' id='ldp_model' name='ldp_model' value='$ldpModel'/>";

        echo "</tr>";

        echo '<script>
                var container = document.getElementById("ldp_model_editor");
                var options = {
                  mode:"tree",
                  modes: ["code", "form", "text", "tree", "view"],
                  change: function () {
                    var input = document.getElementById("ldp_model");
                    if (input) {
                      if (editor) {
                        var json = editor.get();
                        input.value = JSON.stringify(json);
                      }
                    }
                  }
                };
                window.editor = new JSONEditor(container, options);

                var json = ' . json_encode(json_decode($ldpModel)) . ';
                editor.set(json);
                editor.expandAll();
              </script>';
      }

      /**
       	 * Save the value of the posted custom field for the custom taxonomy
         * in the options WP table
       	 *
       	 * @param type
       	 * @return void
      	 */
      function save_custom_tax_field($termID) {
        if (isset($_POST['ldp_model'])) {
          $termMeta = get_option("ldp_container_$termID");
          if (!is_array($termMeta)) {
            $termMeta = array();
          }

          $termMeta['ldp_model'] = stripslashes_deep($_POST['ldp_model']);
          update_option("ldp_container_$termID", $termMeta);
        }
      }

      ################################
      # Settings
      ################################
      function ldp_menu() {
          add_options_page(
              __('WP-LDP Settings', 'wpldp'),
              __('WP-LDP Settings', 'wpldp'),
              'edit_posts',
              'wpldp',
              array($this, 'wpldp_options_page')
          );
      }
      function wpldp_options_page() {
          echo '<div class="wrap">';
          echo '<h2>' . __('WP-LDP Settings', 'wpldp') . '</h2>';
          echo '<form method="post" action="options.php">';
            settings_fields('ldp_context');
            do_settings_sections('wpldp');
            submit_button();
          echo '</form>';
          echo '</div>';
      }

      function ldp_context_field() {
          echo "<input type='text' size='150' name='ldp_context' value='" . get_option('ldp_context', 'http://owl.openinitiative.com/oicontext.jsonld') . "' />";
      }

      function ldp_container_init_field() {
          $optionValue = !empty(get_option('ldp_container_init', false)) ? true : false;
          // var_dump($optionValue);
          // die();
          echo "<input type='checkbox' name='ldp_container_init' value='ldp_container_init' " . checked(1, get_option('ldp_container_init'), false) . " />";
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
