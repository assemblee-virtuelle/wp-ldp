<?php if ( isset($_SERVER['HTTP_ACCEPT'])
        && ( strstr($_SERVER['HTTP_ACCEPT'], 'application/json' )
            || strstr($_SERVER['HTTP_ACCEPT'], "application/ld+json" ) ) ) { ?>
<?php header('Content-Type: application/ld+json'); ?>
<?php header('Access-Control-Allow-Origin: *'); ?>
<?php
  // Getting general information about the container associated with the current resource
  $fields = \WpLdp\WpLdpUtils::getResourceFieldsList($post->ID);
  $terms =  wp_get_post_terms( $post->ID, 'ldp_container' );
  if ( !empty( $terms ) && is_array( $terms ) ) {
    $termId = $terms[0]->term_id;
    $termMeta = get_option("ldp_container_$termId");
    $rdfType = isset($termMeta["ldp_rdf_type"]) ? $termMeta["ldp_rdf_type"] : null;
  }
?>
{
    "@context": "<?php echo get_option('ldp_context', 'http://lov.okfn.org/dataset/lov/context'); ?>",
    "@graph": [
<?php while (have_posts()) : the_post(); ?>
        {
            <?php
            $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
            // Handling special case of editing trhough the wordpress admin backend
            if (!empty($referer) && strstr($referer, 'wp-admin/post.php')) {
              $custom_fields_keys = get_post_custom_keys();
              foreach($fields as $field) {
                $field_name = \WpLdp\WpLdpUtils::getFieldName( $field );
                if ( isset( $field_name ) ) {
                  if ( !isset($field->multiple) || !$field->multiple ) {
                    $field_value = get_post_custom_values($field_name)[0];
                    echo('          "'. $field_name .'": ');
                    echo('' . ( !empty( $field_value ) ? json_encode( $field_value ) : '""' ) . ',');
                    echo "\n";
                  } else {
                    echo('          "' . $field_name . '": [');
                    $arrayToProcess = array();
                    foreach ($custom_fields_keys as $custom_field_name) {
                      if (substr($custom_field_name, 0, strlen($field_name)) === $field_name) {
                        $arrayToProcess[] = $custom_field_name;
                      }
                    }

                    $count = 1;
                    foreach ($arrayToProcess as $custom_field_name) {
                      $field_value = get_post_custom_values($custom_field_name)[0];
                      if ($count < count($arrayToProcess)) {
                        echo('{"@id":' . ( !empty( $field_value ) ? json_encode( $field_value ) : '""' ) . ',');
                        echo('"name":"' . $custom_field_name . '"},');
                        echo "\n";
                      } else {
                        echo('{"@id":' . ( !empty( $field_value ) ? json_encode( $field_value ) : '""' ) . ',');
                        echo('"name":"' . $custom_field_name . '"}');
                      }
                      $count++;
                    }
                    echo('],');
                    echo "\n";
                  }
                }
              }
            } else {
              $arrayToProcess = [];
              $fieldNotToRender = [];
              // Construct proper values array, if any, based on field endings with number:
              $custom_fields_keys = get_post_custom_keys();
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
                    $value = get_post_custom_values($field)[0];
                    if (!empty($value) && $value != '""') {
                      $valuesArray[$arrayField][] = json_encode(get_post_custom_values($field)[0]);
                    }
                  }
                }
              }

              if (!empty($valuesArray)) {
                foreach ($valuesArray as $fieldName => $values) {
                  echo("          \"" . $fieldName . "\": [\n");
                  $count = 0;
                  foreach($values as $value) {
                    if (!empty($value) && $value != '""') {
                      $count++;
                      echo "               {\n";
                      echo("                    \"@id\": " . $value . "\n");

                      if ($count < count($values)) {
                        echo "               },\n";
                      } else {
                        echo "               }\n";
                      }
                    }
                  }
                  echo "          ],\n";
                }
              }

              foreach($fields as $field) {
                $field_name = \WpLdp\WpLdpUtils::getFieldName( $field );
                if ( isset( $field_name ) && !in_array($field_name, $fieldNotToRender)) {
                  echo('          "'. $field_name .'": ');
                  echo('' . json_encode(get_post_custom_values($field_name)[0]) . ',');
                  echo "\n";
                }
              }
            }

            // Get user to retrieve associated posts !
            $user_login = null;
            foreach($fields as $field) {
              $field_name = \WpLdp\WpLdpUtils::getFieldName( $field );
              if (isset($field_name) && $field_name == 'foaf:nick') {
                $user_login = get_post_custom_values($field_name)[0];
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
                  echo "          \"posts\": [\n";
                  $count = 1;
                  while ($loop->have_posts ()) :
                      $loop->next_post ();
                      $post = $loop->post;
                      echo "               {\n";
                      echo "                    \"@id\": \"" . get_permalink ($post->ID) . "\",\n";
                      echo '                    "dc:title":' . json_encode($post->post_title) . ",\n";
                      $post_content = ( !empty( $post->post_content ) && $post->post_content !== false) ? json_encode( substr($post->post_content, 0, 150) ) : "";
                      if ( !empty( $post->post_content ) ) echo '                    "sioc:blogPost":' . $post_content . "\n";
                      if ($count < $loop->post_count) {
                        echo "               },\n";
                      } else {
                        echo "               }\n";
                      }
                      $count++;
                  endwhile;
                  echo "          ],\n";
                  wp_reset_postdata();
                }
              }
            }
          ?>
          <?php if (!empty($rdfType)) echo "\"@type\" : \"$rdfType\",\n"; ?>
          <?php
          $resourceUri = \WpLdp\WpLdpUtils::getResourceUri($post);

          ?>
          "@id": "<?php echo $resourceUri ?>"
        }
<?php endwhile; ?>
    ]
}
<?php } elseif ( isset($_SERVER['HTTP_ACCEPT']) && strstr($_SERVER['HTTP_ACCEPT'], 'text/html' ) ) {
  header("Location: " . site_url() . "/wp-ldp/front#" . site_url() . $_SERVER['REQUEST_URI']);
}
?>
