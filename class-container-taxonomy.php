<?php
/**
 * Container
 *
 * The container taxonomy class and instance.
 *
 * @package WPLDP
 * @version 1.0.0
 * @author  Benoit Alessandroni
 * @license https://www.gnu.org/licenses/gpl-2.0.txt GNU/GPLv2
 * @since  2.0.0
 */

namespace WpLdp;

if ( ! class_exists( '\WpLdp\ContainerTaxonomy' ) ) {
	/**
	 * Handles everything related to the container taxonomy.
	 *
	 * @category Class
	 * @package WPLDP
	 * @author  Benoit Alessandroni
	 * @license https://www.gnu.org/licenses/gpl-2.0.txt GNU/GPLv2
	 */
	class ContainerTaxonomy {
		/**
		 * Default constructor.
		 *
		 * @return {WpLdpTaxonomy}  instance of the object.
		 */
		public function __construct() {
			register_activation_hook( __FILE__, array( $this, 'wpldp_rewrite_flush' ) );
			add_action( 'init', array( $this, 'register_container_taxonomy' ), 0 );

			add_action( 'ldp_container_add_form_fields', array( $this, 'add_custom_tax_fields_oncreate' ) );
			add_action( 'ldp_container_edit_form_fields', array( $this, 'add_custom_tax_fields_onedit' ) );
			add_action( 'create_ldp_container', array( $this, 'save_custom_tax_field' ) );
			add_action( 'edited_ldp_container', array( $this, 'save_custom_tax_field' ) );

			add_action( 'rest_api_init', function() {
				register_rest_route( 'ldp/v1', '/(?P<ldp_container>((?!sites|schema)([a-zA-Z0-9-]+)))/', array(
					'methods' => \WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_resources_from_container' ),
				) );

				register_rest_route( 'ldp/v1', '/search/(?P<ldp_container>((?!sites|schema)([a-zA-Z0-9-]+)))/', array(
					'methods' => \WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_search_results' ),
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
		 * Registers the ldp container taxonomy.
		 *
		 * @return void
		 */
		public function register_container_taxonomy() {
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
				'slug'                       => rtrim( \WpLdp\Api::LDP_API_URL, '/' ),
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

			register_taxonomy( 'ldp_container', 'ldp_resource', $args );
		}

		/**
		 * Adds a LDP Model field to our custom LDP containers taxonomy
		 * in creation mode.
		 *
		 * @param {int} $term the concrete term.
		 * @return void
		 */
		function add_custom_tax_fields_oncreate( $term ) {
			// Adding rdf:type field.
			echo "<div class='form-field term-model-wrap'>";
			echo "<label for='ldp_rdf_type'>" . __( 'Rdf:type, if any', 'wpldp' ) . '</label>';
			echo "<input type='text' id='ldp_rdf_type' type='text' name='ldp_rdf_type' />";
			echo "<p class='description'>" . __( 'Rdf:type associated with this container', 'wpldp' ) . '</p>';
			echo '</div>';

			// Adding container included fields field.
			echo "<div class='form-field term-model-wrap'>";
			echo "<label for='ldp_included_fields_list'>" . __( 'Included fields', 'wpldp' ) . '</label>';
			echo "<input type='text' id='ldp_included_fields_list' type='text' name='ldp_included_fields_list' />";
			echo "<p class='description'>" . __( 'The fields from the model whose values you would like to include from the associated resources in the container, separated by commas', 'wpldp' ) . '</p>';
			echo '</div>';

			// Adding the JSON model field.
			echo "<div class='form-field form-required term-model-wrap'>";
			echo "<label for='ldp_model'>" . __( 'Model', 'wpldp' ) . '</label>';
			echo "<textarea id='ldp_model' type='text' name='ldp_model' cols='40' rows='20'></textarea>";
			echo "<p class='description'>" . __( 'The LDP-compatible JSON Model for this container', 'wpldp' ) . '</p>';
			echo '</div>';
		}

		/**
		 * Adds a LDP Model field to our custom LDP containers taxonomy
		 * in edition mode.
		 *
		 * @param int $term the concrete term.
		 * @return void
		 */
		function add_custom_tax_fields_onedit( $term ) {
			$term_id = $term->term_id;
			$term_meta = get_option( "ldp_container_$term_id" );
			$ldp_model = ! empty( $term_meta['ldp_model'] ) ? stripslashes_deep( $term_meta['ldp_model'] ) : '';
			$ldp_rdf_type = isset( $term_meta['ldp_rdf_type'] ) ? $term_meta['ldp_rdf_type'] : '';
			$ldp_included_fields_list = isset( $term_meta['ldp_included_fields_list'] ) ? $term_meta['ldp_included_fields_list'] : '';

			// Adding rdf:type field.
			echo "<tr class='form-field form-required term-model-wrap'>";
			echo "<th scope='row'><label for='ldp_rdf_type'>" . __( 'Rdf:type, if any', 'wpldp' ) . '</label></th>';
			echo "<td><input type='text' name='ldp_rdf_type' id='ldp_rdf_type' value='$ldp_rdf_type' />";
			echo "<p class='description'>" . __( 'Rdf:type associated with this container', 'wpldp' ) . '</p></td>';
			echo '</tr>';

			// Adding container included fields field.
			echo "<tr class='form-field form-required term-model-wrap'>";
			echo "<th scope='row'><label for='ldp_included_fields_list'>" . __( 'Included fields', 'wpldp' ) . '</label></th>';
			echo "<td><input type='text' name='ldp_included_fields_list' id='ldp_included_fields_list' value='$ldp_included_fields_list' />";
			echo "<p class='description'>" . __( 'The fields from the model whose values you would like to include from the associated resources in the container, separated by commas', 'wpldp' ) . '</p></td>';
			echo '</tr>';

			// Adding the JSON model field.
			echo "<tr class='form-field form-required term-model-wrap'>";
			echo "<th scope='row'><label for='ldp_model_editor'>" . __( 'Model editor mode', 'wpldp' ) . '</label></th>';
			echo "<td><div id='ldp_model_editor' style='width: 1000px; height: 400px;'></div>";
			echo "<p class='description'>" . __( 'The LDP-compatible JSON Model for this container', 'wpldp' ) . '</p></td>';
			echo '</tr>';
			echo "<input type='hidden' id='ldp_model' name='ldp_model' value='$ldp_model'/>";

			echo '</tr>';

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

			var json = ' . json_encode( json_decode( $ldp_model ) ) . ';
			editor.set(json);
			editor.expandAll();
			</script>';
		}

		/**
		 * Save the value of the posted custom field for the custom taxonomy
		 * in the options WP table.
		 *
		 * @param {int} $term_id The current term ID.
		 * @return void
		 */
		function save_custom_tax_field( $term_id ) {
			$term_meta = get_option( "ldp_container_$term_id" );
			if ( ! is_array( $term_meta ) ) {
				$term_meta = array();
			}

			if ( isset( $_POST['ldp_included_fields_list'] ) ) {
				$term_meta['ldp_included_fields_list'] = $_POST['ldp_included_fields_list'];
			}

			if ( isset( $_POST['ldp_rdf_type'] ) ) {
				$term_meta['ldp_rdf_type'] = $_POST['ldp_rdf_type'];
			}

			if ( isset( $_POST['ldp_model'] ) ) {
				$term_meta['ldp_model'] = stripslashes_deep( $_POST['ldp_model'] );
			}

			update_option( "ldp_container_$term_id", $term_meta, false );
		}


		/**
		 * Gets the list of resources associated with the current taxonomy.
		 *
		 * @param  \WP_REST_Request  $request The current HTTP request object.
		 * @param  \WP_REST_Response $response The current HTTP response object.
		 * @return \WP_REST_Response $response The current HTTP response object.
		 */
		public function get_resources_from_container( \WP_REST_Request $request, \WP_REST_Response $response = null ) {
			$params = $request->get_params();
			$ldp_container = $params['ldp_container'];

			$headers = $request->get_headers();
			if ( isset( $headers['accept'] )
				 && false !== strstr( $headers['accept'][0], 'text/html' ) ) {
				header( 'Location: ' . site_url( '/' ) . Wpldp::FRONT_PAGE_URL . '#' . get_rest_url() . 'ldp/v1/' . $ldp_container . '/' );
				exit;
			}

			header( 'Content-Type: application/ld+json' );
			header( 'Access-Control-Allow-Origin: *' );

			$query = new \WP_Query(
				array(
					'tax_query' => array(
						array(
							'taxonomy' => 'ldp_container',
							'terms' => $ldp_container,
							'field' => 'slug',
						),
					),
					'post_type' => 'ldp_resource',
					'posts_per_page' => -1,
				)
			);

			$posts = $query->get_posts();
			$result = array(
				'@context' => get_option( 'ldp_context', 'http://lov.okfn.org/dataset/lov/context' ),
				'@graph'   => array(
					array(
						'@id' => rtrim( get_rest_url(), '/' ) . $request->get_route() . '/',
						'@type' => 'http://www.w3.org/ns/ldp#BasicContainer',
						'http://www.w3.org/ns/ldp#contains' => array(),
					)
				)
			);

			$result = $this->format_posts_rendering( $result, $posts );

			return rest_ensure_response( $result );
		}

		/**
		 * Gets the list of resources associated with the current taxonomy.
		 *
		 * @param  \WP_REST_Request  $request The current HTTP request object.
		 * @param  \WP_REST_Response $response The current HTTP response object.
		 * @return \WP_REST_Response $response The current HTTP response object.
		 */
		public function get_search_results( \WP_REST_Request $request, \WP_REST_Response $response = null ) {
			header( 'Content-Type: application/ld+json' );
			header( 'Access-Control-Allow-Origin: *' );

			$params = $request->get_params();
			$ldp_container = $params['ldp_container'];
			$meta_name = $params['meta_name'];
			$meta_value = $params['meta_value'];

			$query = new \WP_Query(
				array(
					'tax_query' => array(
						array(
							'taxonomy' => 'ldp_container',
							'terms' => $ldp_container,
							'field' => 'slug',
						),
					),
					'post_type' => 'ldp_resource',
					'posts_per_page' => -1,
					'meta_query' => array(
						array(
							'key' => $meta_name,
							'value' => $meta_value,
							'compare' => 'LIKE',
						),
					)
				)
			);

			$posts = $query->get_posts();

			$result = array(
				'@context' => get_option( 'ldp_context', 'http://lov.okfn.org/dataset/lov/context' ),
				'@graph'   => array(
					array(
						'@id' => rtrim( get_rest_url(), '/' ) . $request->get_route() . '/',
						'@type' => 'http://www.w3.org/ns/ldp#BasicContainer',
						'http://www.w3.org/ns/ldp#contains' => array(),
					)
				)
			);

			$result = $this->format_posts_rendering( $result, $posts );

			return rest_ensure_response( $result );
		}

		/**
		 * Formats post rendering for use in the resource JSON-LD expression.
		 *
		 * @param {array} $result The current resource expression.
		 * @param {array} $posts The current user posts.
		 * @return {array} $result The new completed results.
		 */
		private function format_posts_rendering( $result, $posts ) {
			$count = 0;
			foreach ( $posts as $post ) {
				$values = get_the_terms( $post->ID, 'ldp_container' );
				if ( empty( $values[0] ) ) {
					$value = reset( $values );
				} else {
					$value = $values[0];
				}

				$term_meta = get_option( "ldp_container_$value->term_id" );
				$ldp_included_fields_list = isset( $term_meta['ldp_included_fields_list'] ) ? $term_meta['ldp_included_fields_list'] : null;
				$models_decoded = json_decode( $term_meta['ldp_model'] );

				$included_fields_list = ! empty( $ldp_included_fields_list ) ? array_map( 'trim', explode( ',', $ldp_included_fields_list ) ) : null;
				$fields = $models_decoded->{$value->slug}->fields;
				$current_entry = array();
				foreach ( $fields as $field ) {
					$field_name = Utils::get_field_name( $field );
					if ( ( ! empty( $included_fields_list ) && in_array( $field_name, $included_fields_list, true ) )
							&& ! empty( get_post_custom_values( $field_name, $post->ID )[0] ) ) {
						$current_entry[ $field_name ] = get_post_custom_values( $field_name, $post->ID )[0];
					}
				}

				$rdf_type = isset( $term_meta['ldp_rdf_type'] ) ? $term_meta['ldp_rdf_type'] : null;
				if ( ! empty( $rdf_type ) ) {
					$current_entry['@type'] = $rdf_type;
					$current_entry['@id'] = site_url( '/' ) . \WpLdp\Api::LDP_API_URL . $value->slug . '/' . $post->post_name;
				}
				$result['@graph'][0]['http://www.w3.org/ns/ldp#contains'][] = $current_entry;
			}

			return $result;
		}
	}

	$wpldp_container_taxonomy = new ContainerTaxonomy();
} else {
	exit( 'Class ContainerTaxonomy already exists' );
}
