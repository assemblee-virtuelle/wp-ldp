<?php
/**
 * Site taxonomy
 *
 * Site taxonomy class and instance.
 *
 * @package WPLDP
 * @version 1.0.0
 * @author  Benoit Alessandroni
 * @license https://www.gnu.org/licenses/gpl-2.0.txt GNU/GPLv2
 * @since  2.0.0
 */

namespace WpLdp;

if ( ! class_exists( '\WpLdp\SiteTaxonomy' ) ) {
	/**
	 * Handles everything related to our site taxonomy.
	 *
	 * @category Class
	 * @package WPLDP
	 * @author    Benoit Alessandroni
	 * @license https://www.gnu.org/licenses/gpl-2.0.txt GNU/GPLv2
	 */
	class SiteTaxonomy {
		/**
		 * Class default constructor.
		 *
		 * @return {SiteTaxonomy} Instance of the SiteTaxonomy class.
		 */
		public function __construct() {
			register_activation_hook( __FILE__, array( $this, 'wpldp_rewrite_flush' ) );
			add_action( 'init', array( $this, 'register_site_taxonomy' ), 0 );

			add_action( 'ldp_site_add_form_fields', array( $this, 'add_custom_tax_fields_oncreate_site' ) );
			add_action( 'ldp_site_edit_form_fields', array( $this, 'add_custom_tax_fields_onedit_site' ) );

			add_action( 'create_ldp_site', array( $this, 'save_custom_tax_field_site' ) );
			add_action( 'edited_ldp_site', array( $this, 'save_custom_tax_field_site' ) );

			add_action( 'rest_api_init', function() {
				register_rest_route( 'ldp/v1', '/sites/', array(
					'methods' => \WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_sites_list' ),
				) );

				register_rest_route( 'ldp/v1', '/sites/', array(
					'methods' => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'add_new_site' ),
				) );
			} );
		}

		/**
		 * Forces flushing the rewrite rules on plugin activation.
		 *
		 * @return void
		 */
		public function wpldp_rewrite_flush() {
			delete_option( 'rewrite_rules' );
			$this->register_container_taxonomy();
			flush_rewrite_rules( true );
		}

		/**
		 * Registers the new LDP site taxonomy.
		 *
		 * @return void
		 */
		public function register_site_taxonomy() {
			$labels = array(
				'name'                       => __( 'Sites', 'wpldp' ),
				'singular_name'              => __( 'Site', 'wpldp' ),
				'menu_name'                  => __( 'Sites', 'wpldp' ),
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
				'slug'                       => 'site',
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

			register_taxonomy( 'ldp_site', 'ldp_resource', $args );
		}

		/**
		 * Adds an URL field to our custom LDP Site taxonomy
		 * in creation mode.
		 *
		 * @return void
		 */
		function add_custom_tax_fields_oncreate_site() {
			// Adding rdf:type field.
			echo "<div class='form-field term-model-wrap'>";
			echo "<label for='ldp_site'>" . __( 'web site', 'wpldp' ). '</label>';
			echo "<input type='url' placeholder='http://' name='ldp_site' id='ldp_site' />";
			echo "<p class='description'>" . __( 'WordPress site that you know and that the WP-LDP plugin is installed', 'wpldp' ). '</p>';
			echo '</div>';
		}

		/**
		 * Adds a Site URL field to our custom LDP Site taxonomy
		 * in edition mode.
		 *
		 * @param {int} $term the concrete term.
		 * @return void
		 */
		function add_custom_tax_fields_onedit_site( $term ) {
			$term_id = $term->term_id;
			$term_meta = get_term_meta( $term_id,'ldp_site_url', true );
			$ldp_site_url = isset( $term_meta ) ? $term_meta : '';

			// Adding rdf:type field.
			echo "<tr class='form-field form-required term-model-wrap'>";
			echo "<th scope='row'><label for='ldp_site_url'>" . __( 'web site', 'wpldp' ). '</label></th>';
			echo "<td><input type='url' placeholder='http://' name='ldp_site_url' id='ldp_site_url' value='$ldp_site_url' />";
			echo "<p class='description'>" . __( 'WordPress site that you know and on which the WP-LDP plugin is installed', 'wpldp' ). '</p></td>';
			echo '</tr>';
		}

		/**
		 * Save the value of the posted site url field for the site custom taxonomy
		 * in the term_meta WP table.
		 *
		 * @param int $term_id The updated term ID.
		 */
		function save_custom_tax_field_site( $term_id ) {
			$term_meta = get_term_meta( $term_id, 'ldp_site_url', true );

			if ( isset( $_POST['ldp_site_url'] ) ) {
				$term_meta = $_POST['ldp_site_url'];
			}

			update_term_meta( $term_id, 'ldp_site_url', $term_meta );
		}

		/**
		 * Gets the list of sites the current site knows.
		 *
		 * @param  \WP_REST_Request $request The current HTTP request object.
		 * @param  \WP_REST_Response $response The current HTTP response object.
		 * @return \WP_REST_Response $response The current HTTP response object.
		 */
		public function get_sites_list( \WP_REST_Request $request, \WP_REST_Response $response = null ) {
			header( 'Content-Type: application/ld+json' );
			header( 'Access-Control-Allow-Origin: *' );

			$terms = get_terms(
				array(
					'taxonomy' => 'ldp_site',
					'hide_empty' => false,
				)
			);

			$ldp_site_urls = array();
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$possible_url = get_term_meta( $term->term_id, 'ldp_site_url', true );
					if ( $possible_url ) {
						$ldp_site_urls[] = rtrim( $possible_url, '/' );
					}
				}
			}

			$outputs = array();
			foreach ( $ldp_site_urls as $ldp_site_url ) {
				$ch = curl_init();
				$build_url = $ldp_site_url . '/schema/';
				curl_setopt( $ch, CURLOPT_URL, $build_url );
				curl_setopt( $ch, CURLOPT_HTTPGET, true );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/ld+json', 'Accept: application/ld+json' ) );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
				$outputs[ $ldp_site_url  . '/schema/' ]['data'] = curl_exec( $ch );
				$outputs[ $ldp_site_url  . '/schema/' ]['code'] = curl_getinfo( $ch )['http_code'];
			}

			$sites = array(
				'@context' => get_option( 'ldp_context', 'http://lov.okfn.org/dataset/lov/context' ),
				'@graph'   => array(
					'@id'      => get_site_url() . '/api/ldp/v1/sites/',
					'@type'    => 'http://www.w3.org/ns/ldp#BasicContainer',
					'http://www.w3.org/ns/ldp#contains' => array(),
				),
			);

			foreach ($outputs as $site_url => $output ) {
				if ( 200 === $output['code'] ) {
					$response = json_decode( $output['data'] );

					if ( ! empty( $response ) ) {
						$current_site = $response->{'@graph'}[0];
						$current_site->{'@id'} = $site_url;

						$sites['@graph']['http://www.w3.org/ns/ldp#contains'][] =
						$sites['@graph']['http://www.w3.org/ns/ldp#contains'][] = $current_site;
					}
				}
			}

			return rest_ensure_response( $sites );
		}

		/**
		 * Adds a new site to the list of sites the current site knows.
		 *
		 * @param  {\WP_REST_Request} $request The current HTTP request object.
		 * @param  {\WP_REST_Response} $response The current HTTP response object.
		 * @return {\WP_REST_Response} $response The current HTTP response object.
		 */
		public function add_new_site( \WP_REST_Request $request, \WP_REST_Response $response = null ) {
			header( 'Content-Type: application/ld+json' );
			header( 'Access-Control-Allow-Origin: *' );

			$headers = $request->get_headers();

			$source_site_url = $headers['referer'][0];

			$term = null;
			$query = get_terms(
				array(
					'taxonomy' => 'ldp_site',
					'meta_query' => array(
						'key' => 'ldp_site_url',
						'value' => $source_site_url,
						'compare' => 'LIKE',
					),
				),
			);

			$term = $query[0];

			if ( ! term_exists( $term, 'ldp_site' ) ) {
				$new_term = create_term( $term );
			}

			return ( ! empty( $new_term ) || ! empty( $term ) ) ? true : false;
		}
	}

	$wpldp_site_taxonomy = new SiteTaxonomy();
} else {
	exit( 'Class SiteTaxonomy already exists' );
}
