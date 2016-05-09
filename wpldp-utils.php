<?php

if (!class_exists('WpLdpUtils')) {
    class WpLdpUtils {
      public function __construct() {
      }

      public static function getResourceUri( $resource ) {
        $resourceUri = null;
        if ('publish' === get_post_status( $resource->ID )) {
          $resourceUri = get_permalink();
        } else {
          $resourceUri = set_url_scheme( get_permalink( $resource->ID ) );
          $resourceUri = apply_filters( 'preview_post_link', add_query_arg( 'preview', 'true', $resourceUri ), $resource );
        }

        return $resourceUri;
      }

      public static function getResourceFieldsList( $resourceId ) {
        $value = null;
        $fields = array();
        $values = get_the_terms( $resourceId, 'ldp_container' );
        if (!empty($values)) {
          if (empty($values[0])) {
            $value = reset($values);
          } else {
            $value = $values[0];
          }

          $termMeta = get_option( "ldp_container_$value->term_id" );
          $modelsDecoded = json_decode($termMeta["ldp_model"]);
          $fields = $modelsDecoded->{$value->slug}->fields;
        }

        return $fields;
      }
    }
}

$wpLdpUtils = new WpLdpUtils();

?>
