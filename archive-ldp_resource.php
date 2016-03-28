<?php header('Content-Type: application/ld+json'); ?>
<?php header('Access-Control-Allow-Origin: *'); ?>
{
    "@context": "<?php echo get_option('ldp_context', 'http://owl.openinitiative.com/oicontext.jsonld'); ?>",
    "@graph": [ {
        "@id" : "<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>",
        "@type" : "http://www.w3.org/ns/ldp#BasicContainer",
        "http://www.w3.org/ns/ldp#contains" : [
            <?php while (have_posts()) : the_post(); ?>{
                "@id": "<?php the_permalink(); ?>"
                <?php
                  $values = get_the_terms($post->ID, 'ldp_container');
                  if (empty($values[0])) {
                    $value = reset($values);
                  } else {
                    $value = $values[0];
                  }

                  $termMeta = get_option("ldp_container_$value->term_id");
                  $ldpIncludedFieldsList = $termMeta['ldp_included_fields_list'];
                  $modelsDecoded = json_decode($termMeta['ldp_model']);

                  $includedFieldsList = array_map('trim', explode(',', $ldpIncludedFieldsList));
                  $fields = $modelsDecoded->{$value->slug}->fields;
                  foreach ($fields as $field) {
                    if (in_array($field->name, $includedFieldsList)
                          && !empty(get_post_custom_values($field->name)[0])) {
                      echo('"'.substr($field->name, 4).'": ');
                      echo(json_encode(get_post_custom_values($field->name)[0]) . ",\n");
                    }
                  }

                  $rdfType = isset($termMeta["ldp_rdf_type"]) ? $termMeta["ldp_rdf_type"] : null;
                  if (!empty($rdfType)) echo "\"@type\" : \"$rdfType\",\n";
                ?>
            }<?php if($wp_query->current_post + 1 < $wp_query->post_count) echo(","); ?>
        <?php endwhile; ?>
]}
    ]
}
