<?php header('Content-Type: application/ld+json'); ?>
<?php header('Access-Control-Allow-Origin: *'); ?>
{
    "@context": "<?php echo get_option('ldp_context', 'http://owl.openinitiative.com/oicontext.jsonld'); ?>",
    "@graph": [
<?php while (have_posts()) : the_post(); ?>
        {
          <?php
            $values = get_the_terms($post->ID, 'ldp_container');
            if (empty($values[0])) {
              $value = reset($values);
            } else {
              $value = $values[0];
            }

            $termMeta = get_option("ldp_container_$value->term_id");
            $modelsDecoded = json_decode($termMeta["ldp_model"]);
            $fields = $modelsDecoded->{$value->slug}->fields;

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

            foreach($fields as $field) {
              if (substr($field->name, 0, 4) == "ldp_") {
                if (!in_array($field->name, $fieldNotToRender)) {
                  echo('          "'.substr($field->name, 4).'": ');
                  echo('' . json_encode(get_post_custom_values($field->name)[0]) . ',');
                  echo "\n";
                }
              }
            }

            if (!empty($valuesArray)) {
              foreach ($valuesArray as $fieldName => $values) {
                echo("          \"" . substr($fieldName, 4) . "\": [\n");
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
          ?>
          "@id": "<?php the_permalink(); ?>"
        }
<?php endwhile; ?>
    ]
}
