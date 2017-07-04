<?php
namespace WpLdp;

/**
 * Class handling everything related to settings page and available options inside them
 **/
if (!class_exists('\WpLdp\WpLdpApi')) {
    class WpLdpApi {
        /**
         * __construct - Class default constructor
         *
         * @return {WpLdpSettings}  Instance of the WpLdpSettings Class
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
        * API method for retrieving the general schema this site LPD API
        */
        public function get_api_definition( \WP_REST_Request $request, \WP_REST_Response $response = null ) {
            $query = new \WP_Query( array(
               'post_type' => 'ldp_resource',
               'posts_per_page' => -1 )
             );
            $array = [];

            $posts = $query->get_posts();

            $result = '
            {
                "@context": "' . get_option('ldp_context', 'http://lov.okfn.org/dataset/lov/context') . '",
                "@graph": [ {
                    "@id" : "http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '",
                    "@type" : "http://www.w3.org/ns/ldp#BasicContainer",
                    "http://www.w3.org/ns/ldp#contains" : [';

            foreach ($posts as $post ) {
                $values = get_the_terms($post->ID, 'ldp_container');
                if (empty($values[0])) {
                  $value = reset($values);
                } else {
                  $value = $values[0];
                }
                $termMeta = get_option("ldp_container_$value->term_id");
                $rdfType = isset($termMeta["ldp_rdf_type"]) ? $termMeta["ldp_rdf_type"] : null;

                if($rdfType != null){
                    if(array_key_exists($rdfType,$array)){
                      $array[$rdfType]['value']++;
                    }
                    else{
                        $array[$rdfType]['value']=1;
                        $array[$rdfType]['id']=explode(':',$rdfType)[1];
                    }
                }
            }

            $i = 0;
            foreach ( $array as $key => $value ) {
                $result .= "            {\n";
                $result .= "                \"@id\" : \"http://" .$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'].$value['id']."\",\n";
                $result .= "                \"@type\" : \"$key\",\n";
                $result .= "                \"@count\" : ".$value['value']."\n";
                if ( $i +1 == sizeof($array) ) {
                    $result .= "            }\n";
                } else {
                    $result .= "            },\n";
                }
                $i++;
            }
            $result .=   "]";
            $result .=   "}]";
            $result .=   "}";

            return rest_ensure_response( json_decode( $result ) );
        }

        /**
        * API method for retrieving the details of the current ldp resource
        */
        public function get_resource( \WP_REST_Request $request, \WP_REST_Response $response = null ) {
            header('Content-Type: application/ld+json');
            header('Access-Control-Allow-Origin: *');

            $params = $request->get_params();
            $ldp_container = $params['ldp_container'];

            $ldp_resource_slug = $params['ldp_resource'];
            $query = new \WP_Query(
                array(
                    'name' => $ldp_resource_slug,
                    'post_type' => 'ldp_resource'
                )
            );

            $post = $query->get_posts()[0];
            // Getting general information about the container associated with the current resource
            $fields = \WpLdp\WpLdpUtils::getResourceFieldsList($post->ID);
            $terms =  wp_get_post_terms( $post->ID, 'ldp_container' );
            if ( !empty( $terms ) && is_array( $terms ) ) {
              $termId = $terms[0]->term_id;
              $termMeta = get_option("ldp_container_$termId");
              $rdfType = isset($termMeta["ldp_rdf_type"]) ? $termMeta["ldp_rdf_type"] : null;
            }

            $result = '
            {
                "@context": "' . get_option('ldp_context', 'http://lov.okfn.org/dataset/lov/context') . '",
                "@graph": [ {';


            $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
            // Handling special case of editing trhough the wordpress admin backend
            if (!empty($referer) && strstr($referer, 'wp-admin/post.php')) {
              $custom_fields_keys = get_post_custom_keys();
              foreach($fields as $field) {
                $field_name = \WpLdp\WpLdpUtils::getFieldName( $field );
                if ( isset( $field_name ) ) {
                  if ( !isset($field->multiple) || !$field->multiple ) {
                    $field_value = get_post_custom_values( $field_name, $post->ID )[0];
                    $result .= '          "'. $field_name .'": ';
                    $result .= '' . ( !empty( $field_value ) ? json_encode( $field_value ) : '""' ) . ',';
                    $result .=  "\n";
                  } else {
                    $result .= '          "' . $field_name . '": [';
                    $arrayToProcess = array();
                    foreach ($custom_fields_keys as $custom_field_name) {
                      if (substr($custom_field_name, 0, strlen($field_name)) === $field_name) {
                        $arrayToProcess[] = $custom_field_name;
                      }
                    }

                    $count = 1;
                    foreach ($arrayToProcess as $custom_field_name) {
                      $field_value = get_post_custom_values( $custom_field_name, $post->ID )[0];
                      if ($count < count($arrayToProcess)) {
                        $result .= '{"@id":' . ( !empty( $field_value ) ? json_encode( $field_value ) : '""' ) . ',';
                        $result .= '"name":"' . $custom_field_name . '"},';
                        $result .=  "\n";
                      } else {
                        $result .= '{"@id":' . ( !empty( $field_value ) ? json_encode( $field_value ) : '""' ) . ',';
                        $result .= '"name":"' . $custom_field_name . '"}';
                      }
                      $count++;
                    }
                    $result .= '],';
                    $result .= "\n";
                  }
                }
              }
            } else {
              $arrayToProcess = [];
              $fieldNotToRender = [];
              // Construct proper values array, if any, based on field endings with number:
              $custom_fields_keys = get_post_custom_keys( $post->ID );
              foreach ($custom_fields_keys as $field) {
                // $field_name = \WpLdp\WpLdpUtils::getFieldName( $field );
                $endsWithNumber = preg_match_all("/(.*)?(\d+)$/", $field, $matches);
                if (!empty($matches)) {
                  if ($endsWithNumber > 0) {
                    $fieldName = $matches[1][0];
                    if (!in_array($fieldName, $arrayToProcess)) {
                      $arrayToProcess[] = $fieldName;
                    }

                    // Generate proper array to exclude those fields from general rendering
                    $excludedField = $matches[0][0];
                    if (!in_array($excludedField, $fieldNotToRender)) {
                      $fieldNotToRender[] = $excludedField;
                    }

                    if (!in_array($fieldName, $fieldNotToRender)) {
                      $fieldNotToRender[] = $fieldName;
                    }
                  }
                }
              }
              // Example of arrayToProcess ['ldp_foaf:knows', 'ldp_foaf:currentProject']

              foreach($arrayToProcess as $arrayField) {
                foreach ($custom_fields_keys as $field) {
                  if ( isset($field) &&
                      strstr($field, $arrayField) ||
                      $field === $arrayField ) {
                    $value = get_post_custom_values($field, $post->ID )[0];
                    if (!empty($value) && $value != '""') {
                      $valuesArray[$arrayField][] = json_encode(get_post_custom_values($field, $post->ID )[0]);
                    }
                  }
                }
              }

              if (!empty($valuesArray)) {
                foreach ($valuesArray as $fieldName => $values) {
                  $result .= "          \"" . $fieldName . "\": [\n";
                  $count = 0;
                  foreach($values as $value) {
                    if (!empty($value) && $value != '""') {
                      $count++;
                      $result .=  "               {\n";
                      $result .= "                    \"@id\": " . $value . "\n";

                      if ($count < count($values)) {
                        $result .=  "               },\n";
                      } else {
                        $result .=  "               }\n";
                      }
                    }
                  }
                  $result .=  "          ],\n";
                }
              }

              foreach($fields as $field) {
                $field_name = \WpLdp\WpLdpUtils::getFieldName( $field );
                if ( isset( $field_name ) && !in_array($field_name, $fieldNotToRender)) {
                  $result .= '          "'. $field_name .'": ';
                  $result .= '' . json_encode(get_post_custom_values($field_name, $post->ID )[0]) . ',';
                  $result .=  "\n";
                }
              }
            }

            // Get user to retrieve associated posts !
            $user_login = null;
            foreach($fields as $field) {
              $field_name = \WpLdp\WpLdpUtils::getFieldName( $field );
              if (isset($field_name) && $field_name == 'foaf:nick') {
                $user_login = get_post_custom_values( $field_name, $post->ID )[0];
              }
            }

            if ( !empty( $user_login ) ) {
              $user = get_user_by ( 'login', $user_login);
              if ( $user ) {
                $loop = new WP_Query( array(
                    'post_type' => 'post',
                    'posts_per_page' => 12,
                    'orderby'=> 'menu_order',
                    'author' => $user->data->ID,
                    'post_status' => 'any',
                    'paged'=>$paged
                ));

                if ($loop->have_posts ()) {
                  $result .= "          \"posts\": [\n";
                  $count = 1;
                  foreach( $loop as $post ) {
                      $result .= "               {\n";
                      $result .= "                    \"@id\": \"" . get_permalink ($post->ID) . "\",\n";
                      $result .= '                    "dc:title":' . json_encode($post->post_title) . ",\n";
                      $post_content = ( !empty( $post->post_content ) && $post->post_content !== false) ? json_encode( substr($post->post_content, 0, 150) ) : "";
                      if ( !empty( $post->post_content ) ) {
                          $result .= '                    "sioc:blogPost":' . $post_content . "\n";
                      }
                      if ($count < $loop->post_count) {
                          $result .= "               },\n";
                      } else {
                          $result .= "               }\n";
                      }
                      $count++;
                  }
                  $result .= "          ],\n";
                }
              }
            }

            if ( !empty($rdfType) ) {
                $result .= "\"@type\" : \"$rdfType\",\n";
            }

            $resourceUri = \WpLdp\WpLdpUtils::getResourceUri( $post->ID );
            $result .= '"@id": "' . rtrim( get_rest_url(), '/' ) . $request->get_route() . '/"';
            $result .= '}]}';

            return rest_ensure_response( json_decode( $result ) );
        }

        /**
        * API method for overriding the API namespace base slug
        */
        public function define_api_slug( $slug ) {
            return 'api';
        }
    }
    // Instanciating the settings page object
    $wpLdpApi = new WpLdpApi();
} else {
    exit ('Class WpLdpApi already exists');
}
