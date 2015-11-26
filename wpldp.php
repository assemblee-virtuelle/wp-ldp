<?php
/**
 * Plugin Name: WP LDP
 * Plugin URI: http://www.happy-dev.fr
 * Description: This is a test for LDP
 * Text Domain: ??
 * Version: 0.1
 * Author: Sylvain LE BON
 * Author URI: http://www.happy-dev.fr/team/sylvain
 * License: GPL2
 */

// If the file is accessed outside of index.php (ie. directly), we just deny the access
defined('ABSPATH') or die("No script kiddies please!");

if ( get_magic_quotes_gpc() ) {
    $_POST      = array_map( 'stripslashes_deep', $_POST );
    $_GET       = array_map( 'stripslashes_deep', $_GET );
    $_COOKIE    = array_map( 'stripslashes_deep', $_COOKIE );
    $_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );
}

// Entry point of the plugin
add_action('init', 'create_ldp_type');
add_action('edit_form_after_title', 'myprefix_edit_form_after_title');
add_action('save_post', 'test_save');
add_action('admin_menu', 'ldp_menu');
add_action('admin_init', 'backend_hooking');

add_filter( 'template_include', 'include_template_function');

##########################################
# LDP Resource content type definition
##########################################
function create_ldp_type() {
    register_post_type('ldp_resource',
        array(
            'labels'  => array(
                'name'              => 'Resources',
                'singular_name'     => 'Resource',
                'all_items'         => 'All resources',
                'add_new_item'      => 'Ajouter une ressource',
                'edit_item'         => 'Édition d\'une ressource',
                'new_item'          => 'Nouvelle ressource',
                'view_item'         => 'Voir la ressource',
                'search_items'      => 'Rechercher une ressource',
                'not_found'         => 'Aucun ressource correspondante',
                'not_found_in_trash'=> 'Aucun ressource correspondante dans la corbeille',
                'add_new'           => 'Ajouter une ressource',
            ),
            'description'           => 'LDP Resource',
            'public'                => true,
            'show_in_nav_menu'      => true,
            'show_in_menu'          => true,
            'show_in_admin_bar'     => true,
            'supports'              => array('title'),
            'has_archive'           => true,
            'rewrite'               => array('slug' => '%ldp_container%'),
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
    if (is_object($post)){
        $terms = wp_get_object_terms( $post->ID, 'ldp_container' );
        if (!empty($terms)) {
            return str_replace('%ldp_container%', $terms[0]->slug, $post_link);
        }
    }
    return $post_link;
}
add_filter( 'post_type_link', 'ldp_resource_post_link', 1, 3 );

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
      'Containers',
      'container_meta_box_callback',
      $post_type,
      'side'
    );

  endif;
}
add_action( 'add_meta_boxes', 'display_container_meta_box' );

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
    add_action('admin_enqueue_scripts', 'ldp_enqueue_script');
    add_action('admin_enqueue_scripts', 'ldp_enqueue_stylesheet');
    add_settings_section(
      'ldp_context',
      'WP-LDP Settings',
      function() {
        echo _e('The generals settings of the WP-LDP plugin.');
      },
      'wpldp'
    );

    add_settings_field(
      'ldp_context',
      'WP-LDP Context',
      'ldp_context_field',
      'wpldp',
      'ldp_context'
    );

    register_setting( 'ldp_context', 'ldp_context' );
}

################################
# Taxonomy
################################
$taxonomyName = 'ldp_container';
// Register Custom Taxonomy
function register_container_taxonomy() {

	$labels = array(
		'name'                       => _x( 'Containers', 'Taxonomy General Name', 'text_domain' ),
		'singular_name'              => _x( 'Container', 'Taxonomy Singular Name', 'text_domain' ),
		'menu_name'                  => __( 'Containers', 'text_domain' ),
		'all_items'                  => __( 'All Items', 'text_domain' ),
		'parent_item'                => __( 'Parent Item', 'text_domain' ),
		'parent_item_colon'          => __( 'Parent Item:', 'text_domain' ),
		'new_item_name'              => __( 'New Item Name', 'text_domain' ),
		'add_new_item'               => __( 'Add New Item', 'text_domain' ),
		'edit_item'                  => __( 'Edit Item', 'text_domain' ),
		'update_item'                => __( 'Update Item', 'text_domain' ),
		'view_item'                  => __( 'View Item', 'text_domain' ),
		'separate_items_with_commas' => __( 'Separate items with commas', 'text_domain' ),
		'add_or_remove_items'        => __( 'Add or remove items', 'text_domain' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'text_domain' ),
		'popular_items'              => __( 'Popular Items', 'text_domain' ),
		'search_items'               => __( 'Search Items', 'text_domain' ),
		'not_found'                  => __( 'Not Found', 'text_domain' ),
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
add_action( 'init', 'register_container_taxonomy', 0 );

/**
 	 * Adds a LDP Model field to our custom LDP containers taxonomy
   * in creation mode
   *
 	 * @param int $term the concrete term
 	 * @return void
	 */
function add_custom_tax_fields_oncreate($term) {
  echo "<div class='form-field form-required term-model-wrap'>";
  echo "<label for='ldp_model'>Model</label>";
  echo "<textarea id='ldp_model' type='text' name='ldp_model' cols='40' rows='20'></textarea>";
  echo "<p class='description'>The LDP-compatible JSON Model for this container</p>";
  echo "</div>";
}
add_action('ldp_container_add_form_fields', 'add_custom_tax_fields_oncreate');

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
  echo "<th scope='row'><label for='ldp_model_editor'>Model editor mode</label></th>";
  echo "<td><div id='ldp_model_editor' style='width: 1000px; height: 400px;'></div></td>";
  echo "<p class='description'>The LDP-compatible JSON Model for this container</p></td>";
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
add_action('ldp_container_edit_form_fields', 'add_custom_tax_fields_onedit');

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
add_action('create_ldp_container', 'save_custom_tax_field');
add_action('edited_ldp_container', 'save_custom_tax_field');

################################
# Settings
################################
function ldp_menu() {
    add_options_page(
        'WP-LDP Settings',
        'WP-LDP Settings',
        'edit_posts',
        'wpldp',
        'wpldp_options_page'
    );
}
function wpldp_options_page() {
    echo '<div class="wrap">';
    echo '<h2>' . __('WP-LDP Settings') . '</h2>';
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

#############################
#       FOOTER SCRIPT
#############################
add_action( 'admin_footer', 'my_action_javascript' ); // Write our JS below here

function my_action_javascript() { ?>
	<script type="text/javascript" >
	jQuery(document).ready(function($) {

	});
	</script> <?php
	}
