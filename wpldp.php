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

// Entry point of the plugin
add_action('init', 'create_ldp_type');
add_action('edit_form_after_title', 'myprefix_edit_form_after_title');
add_action('save_post', 'test_save');
add_action('admin_init', 'backend_hooking');
add_action('admin_menu', 'ldp_menu');

add_filter( 'template_include', 'include_template_function');

// Create the Project types
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
            'rewrite' => yes,
    #            'menu_icon'             => 'dashicons-admin-home',
            'supports'              => array('title'),
                'has_archive'           => true,
    ));
    flush_rewrite_rules();
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
    if ( is_taxonomy('ldp_container') ) {
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
        $models = get_option('ldp_models', '{"people": {"fields": [{"title": "What\'s your name?", name: "ldp_name"}, {"title": "Who are you?", "name": "ldp_description"}]}}');
        echo '<div id="ldpform"></div>';
        echo '<script>';
        echo "var store = new MyStore({container: '$container', context: 'http://owl.openinitiative.com/oicontext.jsonld', template:\"{{{form 'people'}}}\", models: $models});";
        echo "store.render('#ldpform');";
        echo '</script>';
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
    wp_enqueue_script('ldpjs', 'https://raw.githubusercontent.com/Open-Initiative/LDP-framework/master/mystore.js', array('jquery'));
}

function backend_hooking() {
    add_action('admin_enqueue_scripts', 'ldp_enqueue_script');
    add_settings_section('ldp_section', 'LDP Models settings', function() {echo '';}, 'wpldp');
    add_settings_field('ldp_models', 'LDP Models', 'ldp_models_field', 'wpldp', 'ldp_section');
    register_setting( 'wpldp', 'ldp_models' );
}

################################
# Settings
################################
function ldp_menu() {
    add_options_page(
        'LDP Models',
        'LDP Models',
        'edit_posts',
        'wpldp',
        'wpldp_options_page'
    );
}

function wpldp_options_page() {
    echo '<div class="wrap">';
    echo '<h2>LDP Models</h2>';
    echo '<form method="post" action="options.php">';
    settings_fields('wpldp');
    do_settings_sections('wpldp');
    submit_button();
    echo '</form>';
    echo '</div>';
}

function ldp_models_field() {
    $setting = esc_attr(get_option('ldp_models', '{"people": {"fields": [{"title": "What\'s your name?", name: "ldp_name"}, {"title": "Who are you?", "name": "ldp_description"}]}}'));
    echo "<textarea type='text' name='ldp_models'>$setting</textarea>";
}





// Register Custom Taxonomy
function create_container_taxo() {

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
		'slug'                       => 'ldp_resource',
		'with_front'                 => true,
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
		'rewrite'                    => $rewrite,
	);
	// register_taxonomy( 'ldp_container', array( 'ldp_resource' ), $args );

}
add_action( 'init', 'create_container_taxo', 0 );




add_action( 'admin_footer', 'my_action_javascript' ); // Write our JS below here

function my_action_javascript() { ?>
	<script type="text/javascript" >
	jQuery(document).ready(function($) {

	});
	</script> <?php
	}


################################
# Taxonomies
################################
#add_filter('post_link', 'get_post_iri', 1, 3);
#add_filter('post_type_link', 'get_post_iri', 1, 3);

#function get_post_iri($url, $post, $leavename) {
##    if ( $post->post_type == 'ldp_resource' ) ;
#    echo($url);
#    die();
#}
#    if (strpos($permalink, '%brand%') === FALSE) return $permalink;
#        // Get post
#        $post = get_post($post_id);
#        if (!$post) return $permalink;

#        // Get taxonomy terms
#        $terms = wp_get_object_terms($post->ID, 'brand');
#        if (!is_wp_error($terms) && !empty($terms) && is_object($terms[0]))
#            $taxonomy_slug = $terms[0]->slug;
#        else $taxonomy_slug = 'no-brand';

#    return str_replace('%brand%', $taxonomy_slug, $permalink);
#}

#add_action('init', 'my_rewrite');
#function my_rewrite() {
#    global $wp_rewrite;
#    $wp_rewrite->add_permastruct('typename', 'typename/%year%/%postname%/', true, 1);
#    add_rewrite_rule('typename/([0-9]{4})/(.+)/?$', 'index.php?typename=$matches[2]', 'top');
#    $wp_rewrite->flush_rules(); // !!!
#}

#$wp_rewrite->flush_rules(); // !!!

#register_taxonomy( 'container', 'ldp_resource', array( 'hierarchical' => true, 'label' => 'Container', 'query_var' => true, 'rewrite' => array( 'slug' => 'container' ) ) );

#add_action( 'init', 'register_my_taxonomies', 0 );

#function register_my_taxonomies() {
#    register_taxonomy(
#        'containers',
#        array( 'people', 'todos' ),
#        array(
#            'public' => true,
#            'labels' => array(
#                'name' => __( 'Containers' ),
#                'singular_name' => __( 'Container' )
#            ),
#        )
#    );
#    register_taxonomy_for_object_type( 'containers', 'ldp_resource' );
#}
