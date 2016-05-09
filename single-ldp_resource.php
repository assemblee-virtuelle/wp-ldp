<?php header('Content-Type: application/ld+json'); ?>
<?php header('Access-Control-Allow-Origin: *'); ?>
<?php
  // Getting general information about the container associated with the current resource
  $fields = WpLdpUtils::getResourceFieldsList($post->ID);
  $rdfType = isset($termMeta["ldp_rdf_type"]) ? $termMeta["ldp_rdf_type"] : null;
?>
{
    "@context": "<?php echo get_option('ldp_context', 'http://owl.openinitiative.com/oicontext.jsonld'); ?>",
    "@graph": [
<?php while (have_posts()) : the_post(); ?>
        {
            <?php
            $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
            // Handling special case of editing trhough the wordpress admin backend
            if (!empty($referer) && strstr($referer, 'wp-admin/post.php')) {
              foreach($fields as $field) {
                echo('          "'. $field->name .'": ');
                echo('' . json_encode(get_post_custom_values($field->name)[0]) . ',');
                echo "\n";
              }
            } else {
              $arrayToProcess = [];
              $fieldNotToRender = [];
              // Construct proper values array, if any, based on field endings with number:
              foreach($fields as $field) {
                $endsWithNumber = preg_match_all("/(.*)?(\d+)$/", $field->name, $matches);
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
                foreach($fields as $field) {
                  if ( strstr($field->name, $arrayField) ||
                      $field->name === $arrayField ) {
                    $value = get_post_custom_values($field->name)[0];
                    if (!empty($value) && $value != '""') {
                      $valuesArray[$arrayField][] = json_encode(get_post_custom_values($field->name)[0]);
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
                if (!in_array($field->name, $fieldNotToRender)) {
                  echo('          "'. $field->name .'": ');
                  echo('' . json_encode(get_post_custom_values($field->name)[0]) . ',');
                  echo "\n";
                }
              }
            }

            // Get user to retrieve associated posts !
            $user_login;
            foreach($fields as $field) {
              if ($field->name == 'foaf:nick') {
                $user_login = get_post_custom_values($field->name)[0];
              }
            }

            if ($user_login) {
              $user = get_user_by ( 'login', $user_login);
              if ($user) {
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
                      echo "                    \"url\": \"" . get_permalink ($post->ID) . "\",\n";
                      echo '                    "dc:title":' . json_encode($post->post_title) . ",\n";
                      echo '                    "sioc:blogPost":' . json_encode(substr($post->post_content, 0, 300)) . "\n";
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
          $resourceUri = WpLdpUtils::getResourceUri($post);

          ?>
          "@id": "<?php echo $resourceUri ?>"
        }
<?php endwhile; ?>
    ]
}
