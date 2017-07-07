<?php
namespace WpLdp;

/**
 * Class handling everything related to settings page and available options inside them
 **/
if (!class_exists('\WpLdp\WpLdpApi')) {
    class WpLdpApi {
        const LDP_API_URL = 'api/ldp/v1/';

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
            header('Content-Type: application/ld+json');
            header('Access-Control-Allow-Origin: *');
            
            $query = new \WP_Query( array(
               'post_type' => 'ldp_resource',
               'posts_per_page' => -1 )
             );
            $array = [];

            $posts = $query->get_posts();

            $result = array(
                "@context" => get_option('ldp_context', 'http://lov.okfn.org/dataset/lov/context'),
                "@graph"   => array(
                    array(
                        "@id" => rtrim( get_rest_url(), '/' ) . $request->get_route() . '/',
                        "@type" => "http://www.w3.org/ns/ldp#BasicContainer",
                        "http://www.w3.org/ns/ldp#contains" => array()
                    )
                )
            );

            foreach ($posts as $post ) {
                $values = get_the_terms($post->ID, 'ldp_container');
                if ( !empty( $values ) ) {
                    if ( empty($values[0])) {
                      $value = reset($values);
                    } else {
                      $value = $values[0];
                    }
                    $termMeta = get_option("ldp_container_$value->term_id");
                    $rdfType = isset($termMeta["ldp_rdf_type"]) ? $termMeta["ldp_rdf_type"] : null;

                    if ( $rdfType != null ){
                        if (array_key_exists($rdfType,$array)){
                          $array[$rdfType]['value']++;
                        }
                        else{
                            $array[$rdfType]['value']=1;
                            $array[$rdfType]['id']= strtolower( explode(':',$rdfType)[1] );
                        }
                    }
                }
            }

            foreach ( $array as $key => $value ) {
                $current_container_entry = array();
                $current_container_entry["@id"] = site_url('/') . wpLdpApi::LDP_API_URL . $value['id'];
                $current_container_entry["@type"] = "http://www.w3.org/ns/ldp#BasicContainer";
                $current_container_entry["@count"] = $value['value'];
                $result["@graph"][0]["http://www.w3.org/ns/ldp#contains"][] = $current_container_entry;
            }

            return rest_ensure_response( $result );
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

            $result = array(
                "@context" => get_option('ldp_context', 'http://lov.okfn.org/dataset/lov/context'),
                "@graph"   => array(
                    array(
                    )
                )
            );

            $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
            // Handling special case of editing trhough the wordpress admin backend
            $custom_fields_keys = get_post_custom_keys( $post->ID );
            foreach($fields as $field) {
                $field_name = \WpLdp\WpLdpUtils::getFieldName( $field );
                if ( isset( $field_name ) ) {
                  if ( !isset($field->multiple) || !$field->multiple ) {
                    $field_value = get_post_custom_values( $field_name, $post->ID )[0];
                    $result["@graph"][0][$field_name] = !empty( $field_value ) ? $field_value : null;
                  } else {
                        $result["@graph"][0][$field_name] = array();
                        $field_values = get_post_custom_values( $field_name, $post->ID )[0];

                        if ( !empty ( $field_values ) ) {
                            $field_values = unserialize( $field_values );
                            foreach ($field_values as $value) {
                                $multiple_field_entry = array(
                                    '@id'  => !empty( $value ) ? $value : null,
                                );

                                $result["@graph"][0][$field_name][] = $multiple_field_entry;
                            }
                        }
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
                    $result["@graph"][0]['posts'] = array( array( ) );
                    foreach( $loop as $post ) {
                        $current_post_entry = array();
                        $current_post_entry["@id"] = get_permalink($post->ID);
                        $current_post_entry["dc:title"] = $post->post_title;

                        $post_content = ( !empty( $post->post_content ) && $post->post_content !== false) ? substr($post->post_content, 0, 150) : null;
                        if ( !empty( $post->post_content ) ) {
                            $current_post_entry["sioc:blogPost"] = $post_content;
                        }
                        $result["@graph"][0]['posts'][] = $current_post_entry;
                    }
                }
              }
            }

            if ( !empty($rdfType) ) {
                $result["@graph"][0]['@type'] = $rdfType;
            }

            $result["@graph"][0]['@id'] = rtrim( get_rest_url(), '/' ) . $request->get_route() . '/';
            return rest_ensure_response( $result );
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
