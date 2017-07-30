<?php
/**
 * API
 *
 * The LDP API itself
 *
 * @package WPLDP
 * @version 1.0.0
 * @author  Benoit Alessandroni
 * @license https://www.gnu.org/licenses/gpl-2.0.txt GNU/GPLv2
 * @since  2.0.0
 */

namespace WpLdp;

if ( ! class_exists( '\WpLdp\Api' ) ) {
	/**
	 * Api Handles everything related to our custom LDP API.
	 *
	 * @category Class
	 * @package WPLDP
	 * @author  Benoit Alessandroni
	 * @license https://www.gnu.org/licenses/gpl-2.0.txt GNU/GPLv2
	 */
	class Api {

		/**
		 * The base url of the API.
		 */
		const LDP_API_URL = 'api/ldp/v1/';

		/**
		 * __construct - Class default constructor
		 *
		 * @return {Api}  Instance of the Api Class
		 */
		public function __construct() {
			add_filter( 'rest_url_prefix', array( $this, 'define_api_slug' ) );
			add_action( 'rest_api_init', function() {
				register_rest_route( 'ldp/v1', '/schema/', array(
					'methods' => \WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_api_definition' ),
				), true );

				register_rest_route( 'ldp/v1', '/(?P<ldp_container>[a-zA-Z0-9-]+)/(?P<ldp_resource>[a-zA-Z0-9-]+)', array(
					'methods' => \WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_resource' ),
				) );
			});
		}


		/**
		 * get_api_definition Gets the general schema this site LPD API.
		 *
		 * @param  {\WP_REST_Request} $request The current HTTP request object.
		 * @param  {\WP_REST_Response} $response The current HTTP response object.
		 * @return {\WP_REST_Response} $response The current HTTP response object.
		 */
		public function get_api_definition( \WP_REST_Request $request, \WP_REST_Response $response = null ) {
			header( 'Content-Type: application/ld+json' );
			header( 'Access-Control-Allow-Origin: *' );

			$query = new \WP_Query(
				array(
					'post_type' => 'ldp_resource',
					'posts_per_page' => -1,
				)
			);
			$array = [];

			$posts = $query->get_posts();

			$result = array(
				'@context' => get_option( 'ldp_context', 'http://lov.okfn.org/dataset/lov/context' ),
				'@graph'   => array(
					array(
						'@id' => rtrim( get_rest_url(), '/' ) . $request->get_route() . '/',
						'@type' => 'http://www.w3.org/ns/ldp#BasicContainer',
						'http://www.w3.org/ns/ldp#contains' => array(),
					),
				)
			);

			foreach ( $posts as $post ) {
				$values = get_the_terms( $post->ID, 'ldp_container' );
				if ( ! empty( $values ) ) {
					if ( empty( $values[0] ) ) {
						$value = reset( $values );
					} else {
						$value = $values[0];
					}
					$term_meta = get_option( "ldp_container_$value->term_id" );
					$rdf_type = isset( $term_meta['ldp_rdf_type'] ) ? $term_meta['ldp_rdf_type'] : null;

					if ( null !== $rdf_type ) {
						if ( array_key_exists( $rdf_type,$array ) ) {
							$array[ $rdf_type ]['value']++;
						}
						else {
							$array[ $rdf_type ]['value'] = 1;
							$array[ $rdf_type ]['id'] = strtolower( explode( ':', $rdf_type )[1] );
						}
					}
				}
			}

			foreach ( $array as $key => $value ) {
				$current_container_entry = array();
				$current_container_entry['@id'] = site_url( '/' ) . \WpLdp\Api::LDP_API_URL . $value['id'] . '/';
				$current_container_entry['@type'] = 'http://www.w3.org/ns/ldp#BasicContainer';
				$current_container_entry['@count'] = $value['value'];
				$result['@graph'][0]['http://www.w3.org/ns/ldp#contains'][] = $current_container_entry;
			}

			return rest_ensure_response( $result );
		}


		/**
		 * get_api_definition Gets the details of the current ldp resource.
		 *
		 * @param  {\WP_REST_Request} $request The current HTTP request object.
		 * @param  {\WP_REST_Response} $response The current HTTP response object.
		 * @return {\WP_REST_Response} $response The current HTTP response object.
		 */
		public function get_resource( \WP_REST_Request $request, \WP_REST_Response $response = null ) {
			$params = $request->get_params();
			$ldp_container = $params['ldp_container'];
			$ldp_resource_slug = $params['ldp_resource'];

			$headers = $request->get_headers();
			if ( isset( $headers['accept'] )
			&& strstr( $headers['accept'][0], 'text/html' ) !== false ) {
				header( 'Location: ' . site_url('/') . Wpldp::FRONT_PAGE_URL . '#' . get_rest_url() . 'ldp/v1/' . $ldp_container . '/' . $ldp_resource_slug . '/' );
				exit;
			}

			header( 'Content-Type: application/ld+json' );
			header( 'Access-Control-Allow-Origin: *' );

			$query = new \WP_Query(
				array(
					'name' => $ldp_resource_slug,
					'post_type' => 'ldp_resource',
				)
			);

			$post = $query->get_posts();

			if ( ! empty( $post ) && is_array( $post ) ) {
				$post = $post[0];
			}
			else {
				return null;
			}

			// Getting general information about the container associated with the current resource.
			$fields = \WpLdp\Utils::get_resource_fields_list( $post->ID );
			$terms =  wp_get_post_terms( $post->ID, 'ldp_container' );
			if ( ! empty( $terms ) && is_array( $terms ) ) {
				$term_id = $terms[0]->term_id;
				$term_meta = get_option( "ldp_container_$term_id" );
				$rdf_type = isset( $term_meta['ldp_rdf_type'] ) ? $term_meta['ldp_rdf_type'] : null;
			}

			$result = array(
				'@context' => get_option( 'ldp_context', 'http://lov.okfn.org/dataset/lov/context' ),
				'@graph'   => array( array() ),
			);

			$referer = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : null;
			// Handling special case of editing trhough the WordPress admin backend.
			$custom_fields_keys = get_post_custom_keys( $post->ID );
			foreach ( $fields as $field ) {
				$field_name = \WpLdp\Utils::get_field_name( $field );
				if ( isset( $field_name ) ) {
					if ( ! isset( $field->multiple ) || ! $field->multiple ) {
						$field_value = get_post_custom_values( $field_name, $post->ID )[0];
						$result['@graph'][0][ $field_name ] = ! empty( $field_value ) ? $field_value : null;
					} else {
						$result['@graph'][0][ $field_name ] = array();
						$field_values = get_post_custom_values( $field_name, $post->ID )[0];

						if ( ! empty ( $field_values ) ) {
							$field_values = unserialize( $field_values );
							foreach ( $field_values as $value ) {
								$multiple_field_entry = array(
									'@id'  => ! empty( $value ) ? $value : null,
								);

								$result['@graph'][0][ $field_name ][] = $multiple_field_entry;
							}
						}
					}
				}
			}

			// Get user to retrieve associated posts !
			$user_login = null;
			foreach ( $fields as $field ) {
				$field_name = \WpLdp\Utils::get_field_name( $field );
				if ( isset( $field_name ) && 'foaf:nick' === $field_name ) {
					$user_login = get_post_custom_values( $field_name, $post->ID )[0];
				}
			}

			if ( ! empty( $user_login ) ) {
				$user = get_user_by ( 'login', $user_login );
				if ( $user ) {
					$loop = new \WP_Query( array(
						'post_type' => 'post',
						'posts_per_page' => 12,
						'orderby' => 'menu_order',
						'author' => $user->data->ID,
						'post_status' => 'any',
					) );

					$posts = $loop->get_posts();
					if ( ! empty( $posts ) ) {
						$result['@graph'][0]['posts'] = array( array() );
						foreach ( $posts as $post ) {
							$current_post_entry = array();
							$current_post_entry['@id'] = get_permalink( $post->ID );
							$current_post_entry['dc:title'] = $post->post_title;

							$post_content = ( ! empty( $post->post_content ) && false !== $post->post_content ) ? substr( $post->post_content, 0, 150 ) : null;
							if ( ! empty( $post->post_content ) ) {
								$current_post_entry['sioc:blogPost'] = $post_content;
							}
							$result['@graph'][0]['posts'][] = $current_post_entry;
						}
					}
				}
			}

			if ( ! empty( $rdf_type ) ) {
				$result['@graph'][0]['@type'] = $rdf_type;
			}

			$result['@graph'][0]['@id'] = rtrim( get_rest_url(), '/' ) . $request->get_route() . '/';
			return rest_ensure_response( $result );
		}

		/**
		 * define_api_slug Filters the current site API slug.
		 *
		 * @param  {string} $slug The current site API slug.
		 * @return {string} $slug The current site API slug.
		 */
		public function define_api_slug( $slug ) {
			return 'api';
		}
	}

	$wpldp_api = new Api();
} else {
	exit( 'Class Api already exists' );
} 
