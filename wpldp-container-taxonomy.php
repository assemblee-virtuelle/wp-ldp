<?php
namespace WpLdp;

/**
 * Class handling everything related to the plugin custom taxonomies
 **/
if (!class_exists('\WpLdp\WpLdpContainerTaxonomy')) {
  class WpLdpContainerTaxonomy {
        /**
         * __construct - Default constructor
         *
         * @return {WpLdpTaxonomy}  instance of the object
         */
        public function __construct() {
          register_activation_hook( __FILE__, array($this, 'wpldp_rewrite_flush' ) );
          add_action( 'init', array($this, 'register_container_taxonomy'), 0 );

          add_action( 'ldp_container_add_form_fields', array($this, 'add_custom_tax_fields_oncreate'));
          add_action( 'ldp_container_edit_form_fields', array($this, 'add_custom_tax_fields_onedit'));
          add_action( 'create_ldp_container', array($this, 'save_custom_tax_field'));
          add_action( 'edited_ldp_container', array($this, 'save_custom_tax_field'));

          add_action( 'rest_api_init', function() {
              register_rest_route( 'ldp/v1', '/(?P<ldp_container>((?!sites|schema)([a-zA-Z0-9-]+)))/', array(
                  'methods' => \WP_REST_Server::READABLE,
                  'callback' => array( $this, 'get_resources_from_container' ),
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
         * register_container_taxonomy - Registering the ldp container taxonomy
         *
         * @return {type}  description
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
            'slug'                       => 'ldp',
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
           * in creation mode
           *
           * @param int $term the concrete term
           * @return void
           */
        function add_custom_tax_fields_oncreate($term) {
          // Adding rdf:type field
          echo "<div class='form-field term-model-wrap'>";
          echo "<label for='ldp_rdf_type'>" . __('Rdf:type, if any', 'wpldp'). "</label>";
          echo "<input type='text' id='ldp_rdf_type' type='text' name='ldp_rdf_type' />";
          echo "<p class='description'>" . __('Rdf:type associated with this container', 'wpldp'). "</p>";
          echo "</div>";

          // Adding container included fields field
          echo "<div class='form-field term-model-wrap'>";
          echo "<label for='ldp_included_fields_list'>" . __('Included fields', 'wpldp'). "</label>";
          echo "<input type='text' id='ldp_included_fields_list' type='text' name='ldp_included_fields_list' />";
          echo "<p class='description'>" . __('The fields from the model whose values you would like to include from the associated resources in the container, separated by commas', 'wpldp'). "</p>";
          echo "</div>";

          // Adding the JSON model field
          echo "<div class='form-field form-required term-model-wrap'>";
          echo "<label for='ldp_model'>" . __('Model', 'wpldp'). "</label>";
          echo "<textarea id='ldp_model' type='text' name='ldp_model' cols='40' rows='20'></textarea>";
          echo "<p class='description'>" . __('The LDP-compatible JSON Model for this container', 'wpldp'). "</p>";
          echo "</div>";
        }

        /**
           * Adds a LDP Model field to our custom LDP containers taxonomy
           * in edition mode
           *
           * @param int $term the concrete term
           * @return void
           */
        function add_custom_tax_fields_onedit($term) {
          $termId = $term->term_id;
          $termMeta = get_option("ldp_container_$termId");
          $ldpModel = !empty($termMeta['ldp_model']) ? stripslashes_deep($termMeta['ldp_model']) : "";
          $ldpRdfType = isset($termMeta['ldp_rdf_type']) ? $termMeta['ldp_rdf_type'] : '';
          $ldpIncludedFieldsList = isset($termMeta['ldp_included_fields_list']) ? $termMeta['ldp_included_fields_list'] : '';

          // Adding rdf:type field
          echo "<tr class='form-field form-required term-model-wrap'>";
          echo "<th scope='row'><label for='ldp_rdf_type'>" . __('Rdf:type, if any', 'wpldp'). "</label></th>";
          echo "<td><input type='text' name='ldp_rdf_type' id='ldp_rdf_type' value='$ldpRdfType' />";
          echo "<p class='description'>" . __('Rdf:type associated with this container', 'wpldp'). "</p></td>";
          echo "</tr>";

          // Adding container included fields field
          echo "<tr class='form-field form-required term-model-wrap'>";
          echo "<th scope='row'><label for='ldp_included_fields_list'>" . __('Included fields', 'wpldp'). "</label></th>";
          echo "<td><input type='text' name='ldp_included_fields_list' id='ldp_included_fields_list' value='$ldpIncludedFieldsList' />";
          echo "<p class='description'>" . __('The fields from the model whose values you would like to include from the associated resources in the container, separated by commas', 'wpldp'). "</p></td>";
          echo "</tr>";

          // Adding the JSON model field
          echo "<tr class='form-field form-required term-model-wrap'>";
          echo "<th scope='row'><label for='ldp_model_editor'>" . __('Model editor mode', 'wpldp'). "</label></th>";
          echo "<td><div id='ldp_model_editor' style='width: 1000px; height: 400px;'></div>";
          echo "<p class='description'>" . __('The LDP-compatible JSON Model for this container', 'wpldp'). "</p></td>";
          echo "</tr>";
          echo "<input type='hidden' id='ldp_model' name='ldp_model' value='$ldpModel'/>";

          echo "</tr>";

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

                  var json = ' . json_encode(json_decode($ldpModel)) . ';
                  editor.set(json);
                  editor.expandAll();
                </script>';
        }

        /**
           * Save the value of the posted custom field for the custom taxonomy
           * in the options WP table
           *
           * @param type
           * @return void
           */
        function save_custom_tax_field($termID) {
          $termMeta = get_option("ldp_container_$termID");
          if (!is_array($termMeta)) {
            $termMeta = array();
          }

          if (isset($_POST['ldp_included_fields_list'])) {
            $termMeta['ldp_included_fields_list'] = $_POST['ldp_included_fields_list'];
          }

          if (isset($_POST['ldp_rdf_type'])) {
            $termMeta['ldp_rdf_type'] = $_POST['ldp_rdf_type'];
          }

          if (isset($_POST['ldp_model'])) {
            $termMeta['ldp_model'] = stripslashes_deep($_POST['ldp_model']);
          }

          update_option("ldp_container_$termID", $termMeta, false);
        }

        /**
        * API method for retrieving the list of resources associated with the current taxonomy
        */
        public function get_resources_from_container( \WP_REST_Request $request, \WP_REST_Response $response = null ) {
            header('Content-Type: application/ld+json');
            header('Access-Control-Allow-Origin: *');

            $params = $request->get_params();
            $ldp_container = $params['ldp_container'];

            $query = new \WP_Query(
                array(
                    'tax_query' => array(
                        array(
                          'taxonomy' => 'ldp_container',
                          'terms' => $ldp_container,
                          'field' => 'slug'
                        )
                    ),
                   'post_type' => 'ldp_resource',
                   'posts_per_page' => -1
               )
            );

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

            $count = 0;
            foreach ($posts as $post ) {
                  $values = get_the_terms($post->ID, 'ldp_container');
                  if (empty($values[0])) {
                    $value = reset($values);
                  } else {
                    $value = $values[0];
                  }

                  $termMeta = get_option("ldp_container_$value->term_id");
                  $ldpIncludedFieldsList = isset($termMeta['ldp_included_fields_list']) ? $termMeta['ldp_included_fields_list'] : null;
                  $modelsDecoded = json_decode($termMeta['ldp_model']);

                  $includedFieldsList = !empty($ldpIncludedFieldsList) ? array_map('trim', explode(',', $ldpIncludedFieldsList)) : null;
                  $fields = $modelsDecoded->{$value->slug}->fields;
                  $current_entry = array();
                  foreach ($fields as $field) {
                    $fieldName = WpLdpUtils::getFieldName( $field );
                    if ( (!empty($includedFieldsList) && in_array( $fieldName, $includedFieldsList ) )
                          && !empty(get_post_custom_values( $fieldName, $post->ID )[0])) {
                        $current_entry[ $fieldName ] = get_post_custom_values( $fieldName, $post->ID )[0];
                    }
                  }

                  $rdfType = isset($termMeta["ldp_rdf_type"]) ? $termMeta["ldp_rdf_type"] : null;
                  if ( !empty( $rdfType ) ) {
                      $current_entry['@type'] = $rdfType;
                      $current_entry['@id'] = site_url('/') . wpLdpApi::LDP_API_URL . $value->slug . '/' . $post->post_name;
                  }
                  $result['@graph'][0]['http://www.w3.org/ns/ldp#contains'][] = $current_entry;
            }

            return rest_ensure_response( $result );
        }
    }

    // Instanciating the settings page object
    $wpLdpContainerTaxonomy = new WpLdpContainerTaxonomy();
} else {
    exit ('Class WpLdpContainerTaxonomy already exists');
}

 ?>
