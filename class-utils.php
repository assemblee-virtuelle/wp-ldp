<?php
/**
 * Utilities
 *
 * Utility class and file.
 *
 * @package WPLDP
 * @version 1.0.0
 * @author  Benoit Alessandroni
 * @license https://www.gnu.org/licenses/gpl-2.0.txt GNU/GPLv2
 * @since  2.0.0
 */
namespace WpLdp;

if ( ! class_exists( '\WpLdp\Utils' ) ) {
	/**
	 * Utils Class Doc Comment
	 *
	 * @category Class
	 * @package WPLDP
	 * @author    Benoit Alessandroni
	 * @license https://www.gnu.org/licenses/gpl-2.0.txt GNU/GPLv2
	 *
	 */
	class Utils {
		/**
		 * get_field_name - Gets the current field name, with retro-compatibility
		 * with the older notation system.
		 *
		 * @param  {type} $field the current field to process
		 * @return {type}        The field name
		 */
		public static function get_field_name( $field ) {
			$field_name = null;

			if ( isset( $field->name ) ) {
				$field_name = $field->name;
			} elseif ( isset( $field->{'data-property'} ) ) {
				$field_name = $field->{'data-property'};
			} elseif ( isset( $field->{'object-property'} ) ) {
				$field_name = $field->{'object-property'};
			}

			return $field_name;
		}

		/**
		 * get_resource_uri - Gets the URI of the resource passed as parameter.
		 *
		 * @param  {WP_Post} $resource The resource to process
		 * @return {string}  $resource_uri The current resource URI
		 */
		public static function get_resource_uri( $resource ) {
			$resource_uri = null;
			if ('publish' === get_post_status( $resource->ID ) ) {
				$ldp_container = wp_get_post_terms( $resource->ID, 'ldp_container' )[0];
				$resource_uri = get_rest_url() . 'ldp/v1/' . $ldp_container->slug . '/' . $resource->post_name . '/';
			} else {
				$resource_uri = set_url_scheme( get_permalink( $resource_id ) );
				$resource_uri = apply_filters( 'preview_post_link', add_query_arg( 'preview', 'true', $resource_uri ), $resource_id );
			}

			return $resource_uri;
		}

		/**
		 * get_resource_fields_list - Gets the list of fields associated with the current resource.
		 *
		 * @param  {int} $resource_id The ID of the resource to process
		 * @return {array} $fields    The list of available fields
		 */
		public static function get_resource_fields_list( $resource_id ) {
			$value = null;
			$fields = array();
			$values = get_the_terms( $resource_id, 'ldp_container' );
			if ( !empty( $values ) && !is_wp_error( $values ) ) {
				if ( empty( $values[0] ) ) {
					$value = reset( $values );
				} else {
					$value = $values[0];
				}

				$term_meta = get_option( "ldp_container_$value->term_id" );
				$models_decoded = json_decode( $term_meta['ldp_model'] );
				$fields = $models_decoded->{$value->slug}->fields;
			}

			return $fields;
		}
	}

	$utils = new Utils();
}
