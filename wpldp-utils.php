<?php
namespace WpLdp;

if (!class_exists('\WpLdp\WpLdpUtils')) {
    class WpLdpUtils {
        public function __construct() {
        }

        /**
        * getFieldName - Get the current field name, with retro-compatibility
        * with the older notation system
        *
        * @param  {type} $field the current field to process
        * @return {type}        The field name
        */
        public static function getFieldName( $field ) {
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

        public static function getResourceUri( $resource ) {
            $resourceUri = null;
            if ('publish' === get_post_status( $resource->ID ) ) {
                $ldp_container = wp_get_post_terms( $resource->ID, 'ldp_container' )[0];
                $resourceUri = get_rest_url() . 'ldp/v1/' . $ldp_container->slug . '/' . $resource->post_name . '/';
            } else {
                $resourceUri = set_url_scheme( get_permalink( $resourceId ) );
                $resourceUri = apply_filters( 'preview_post_link', add_query_arg( 'preview', 'true', $resourceUri ), $resourceId );
            }

            return $resourceUri;
        }

        public static function getResourceFieldsList( $resourceId ) {
            $value = null;
            $fields = array();
            $values = get_the_terms( $resourceId, 'ldp_container' );
            if ( !empty( $values ) && !is_wp_error( $values ) ) {
                if ( empty( $values[0] ) ) {
                    $value = reset( $values );
                } else {
                    $value = $values[0];
                }

                $termMeta = get_option( "ldp_container_$value->term_id" );
                $modelsDecoded = json_decode( $termMeta["ldp_model"] );
                $fields = $modelsDecoded->{$value->slug}->fields;
            }

            return $fields;
        }
    }

    $wpLdpUtils = new WpLdpUtils();
}


?>
