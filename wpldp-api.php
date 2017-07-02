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
                register_rest_route( 'ldp/v1', '/sites/', array(
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => array( $this, 'get_sites_list' ),
                ) );
            });
        }

        public function define_api_slug( $slug ) {
            return 'api';
        }

        public function get_sites_list(  \WP_REST_Request $request, \WP_REST_Response $response = null ) {
            $terms = get_terms( array(
              'taxonomy' => 'ldp_site',
              'hide_empty' => false,
            ) );

            foreach ( $terms as $term ){
                $possibleUrl = get_term_meta( $term->term_id, "ldp_site_url", true );
                if ( $possibleUrl ) {
                    $ldpSiteUrls[] =$possibleUrl;
                }
            }

            $outputs = array();
            foreach ($ldpSiteUrls as $ldpSiteUrl){
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $ldpSiteUrl.'/ldp/');
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/ld+json', 'Accept: application/ld+json'));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $outputs[$ldpSiteUrl.'/ldp/']['data'] = curl_exec($ch);
                $outputs[$ldpSiteUrl.'/ldp/']['code'] = curl_getinfo($ch)['http_code'];
            }

            $sites = array();
            foreach ($outputs as $output){
                if($output['code'] == 200){
                    $sites[] = json_decode( $output['data'] );
                }
            }

            return rest_ensure_response( $sites );
        }
    }
    // Instanciating the settings page object
    $wpLdpApi = new WpLdpApi();
} else {
    exit ('Class WpLdpApi already exists');
}
