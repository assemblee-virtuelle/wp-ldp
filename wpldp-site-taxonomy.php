<?php
namespace WpLdp;

/**
 * Class handling everything related to the plugin custom taxonomies
 **/
if (!class_exists('\WpLdp\WpLdpSiteTaxonomy')) {
    class WpLdpSiteTaxonomy {

        public function __construct() {
            register_activation_hook( __FILE__, array($this, 'wpldp_rewrite_flush' ) );
            add_action( 'init', array($this, 'register_site_taxonomy'), 0 );

            add_action( 'ldp_site_add_form_fields', array($this, 'add_custom_tax_fields_oncreate_site'));
            add_action( 'ldp_site_edit_form_fields', array($this, 'add_custom_tax_fields_onedit_site'));

            add_action( 'create_ldp_site', array($this, 'save_custom_tax_field_site'));
            add_action( 'edited_ldp_site', array($this, 'save_custom_tax_field_site'));

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
         * wpldp_rewrite_flush - Force flushing the rewrite rules on plugin activation
         *
         * @return {type}  description
         */
        public function wpldp_rewrite_flush() {
          delete_option('rewrite_rules');
          $this->register_container_taxonomy();
          flush_rewrite_rules( true );
        }

        /**
         * register_site_taxonomy - Registering the new LDP site taxonomy
         *
         * @return {type}  description
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
          * in creation mode
          *
          * @param int $term the concrete term
          * @return void
          */
       function add_custom_tax_fields_oncreate_site() {
           // Adding rdf:type field
           echo "<div class='form-field term-model-wrap'>";
           echo "<label for='ldp_site'>" . __('web site', 'wpldp'). "</label>";
           echo "<input type='url' placeholder='http://' name='ldp_site' id='ldp_site' />";
           echo "<p class='description'>" . __('WordPress site that you know and that the WP-LDP plugin is installed', 'wpldp'). "</p>";
           echo "</div>";
       }

        /**
         * Adds a Site URL field to our custom LDP Site taxonomy
         * in edition mode
         *
         * @param int $term the concrete term
         * @return void
         */
        function add_custom_tax_fields_onedit_site($term) {
            $termId = $term->term_id;
            $termMeta = get_term_meta($termId,"ldp_site_url",true);
            $ldpSiteUrl = isset($termMeta) ? $termMeta : '';

            // Adding rdf:type field
            echo "<tr class='form-field form-required term-model-wrap'>";
            echo "<th scope='row'><label for='ldp_site_url'>" . __('web site', 'wpldp'). "</label></th>";
            echo "<td><input type='url' placeholder='http://' name='ldp_site_url' id='ldp_site_url' value='$ldpSiteUrl' />";
            echo "<p class='description'>" . __('WordPress site that you know and on which the WP-LDP plugin is installed', 'wpldp'). "</p></td>";
            echo "</tr>";
        }

        /**
       * Save the value of the posted site url field for the site custom taxonomy
       * in the term_meta WP table
       *
       * @param int $termID The updated term ID
       */
        function save_custom_tax_field_site($termID) {
            $termMeta = get_term_meta( $termID," ldp_site_url", true );

            if (isset($_POST['ldp_site_url'])) {
                $termMeta = $_POST['ldp_site_url'];
            }

            update_term_meta( $termID, "ldp_site_url", $termMeta );
        }

        /**
        * API method for retrieving the list of sites the current site knows
        */
        public function get_sites_list(  \WP_REST_Request $request, \WP_REST_Response $response = null ) {
            header('Content-Type: application/ld+json');
            header('Access-Control-Allow-Origin: *');

            $terms = get_terms(
                array(
                    'taxonomy' => 'ldp_site',
                    'hide_empty' => false,
                )
            );

            $ldpSiteUrls = array();
            foreach ( $terms as $term ){
                $possibleUrl = get_term_meta( $term->term_id, "ldp_site_url", true );
                if ( $possibleUrl ) {
                    $ldpSiteUrls[] = rtrim( $possibleUrl, '/' );
                }
            }

            $outputs = array();
            foreach ($ldpSiteUrls as $ldpSiteUrl) {
                $ch = curl_init();
                $build_url = $ldpSiteUrl . '/schema/';
                curl_setopt( $ch, CURLOPT_URL, $build_url );
                curl_setopt( $ch, CURLOPT_HTTPGET, true );
                curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type: application/ld+json', 'Accept: application/ld+json') );
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
                $outputs[ $ldpSiteUrl  . '/schema/' ]['data'] = curl_exec($ch);
                $outputs[ $ldpSiteUrl  . '/schema/' ]['code'] = curl_getinfo($ch)['http_code'];
            }

            $sites = array(
                "@context" => get_option('ldp_context', 'http://lov.okfn.org/dataset/lov/context'),
                "@graph"   => array(
                    "@id"      => get_site_url() . '/api/ldp/v1/sites/',
                    "@type"    => "http://www.w3.org/ns/ldp#BasicContainer",
                    "http://www.w3.org/ns/ldp#contains" => array()
                )
            );

            foreach ($outputs as $siteUrl => $output ){
                if ($output['code'] == 200){
                    $response = json_decode( $output['data'] );

                    if ( !empty( $response ) ) {
                        $current_site = $response->{"@graph"}[0];
                        $current_site->{"@id"} = $siteUrl;

                        $sites["@graph"]["http://www.w3.org/ns/ldp#contains"][] =
                        $sites["@graph"]["http://www.w3.org/ns/ldp#contains"][] = $current_site;
                    }
                }
            }

            return rest_ensure_response( $sites );
        }

        /**
        * API method for retrieving the list of sites the current site knows
        */
        public function add_new_site(  \WP_REST_Request $request, \WP_REST_Response $response = null ) {
            header('Content-Type: application/ld+json');
            header('Access-Control-Allow-Origin: *');

            // var_dump( $request->get_headers() );
            $headers = $request->get_headers();

            $source_site_url = $headers['referer'][0];

            $term = null;
            $query = get_terms(
                array(
                    'taxonomy' => 'ldp_site',
                    'meta_query' => array(
                        'key' => 'ldp_site_url',
                        'value' => $source_site_url,
                        'compare' => 'LIKE'
                    )
                )
            );

            $term = $query[0];

            if ( !term_exists( $term, 'ldp_site' ) ) {
                $new_term = create_term( $term );
            }

            return ( !empty( $new_term ) || !empty( $term ) ) ? true : false;
        }

    }
    // Instanciating the settings page object
    $wpLdpSiteTaxonomy = new WpLdpSiteTaxonomy();
} else {
    exit ('Class WpLdpSiteTaxonomy already exists');
}

 ?>
