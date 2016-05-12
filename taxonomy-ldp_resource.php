<?php header('Content-Type: application/ld+json'); ?>
<?php header('Access-Control-Allow-Origin: *'); ?>
<?php
    $categoryId = $wp_query->get_queried_object_id();
    query_posts( array(
        'tax_query' => array(
            array(
              'taxonomy' => 'ldp_container',
              'terms' => $categoryId
            )
        ),
       'post_type' => 'ldp_resource',
       'posts_per_page' => -1 )
     );
?>
{
    "@context": "<?php echo get_option('ldp_context', 'http://owl.openinitiative.com/oicontext.jsonld'); ?>",
    "@graph": [ {
        "@id" : "<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>",
        "@type" : "http://www.w3.org/ns/ldp#BasicContainer"<?php if ( have_posts() ) : ?>,
        "http://www.w3.org/ns/ldp#contains" : [
            <?php while (have_posts()) : the_post(); ?>{
<?php
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
                  foreach ($fields as $field) {
                    if ((!empty($includedFieldsList) && in_array($field->name, $includedFieldsList))
                          && !empty(get_post_custom_values($field->name)[0])) {
                      echo('                "' . $field->name . '": ');
                      echo(json_encode(get_post_custom_values($field->name)[0]) . ",\n");
                    }
                  }

                  $rdfType = isset($termMeta["ldp_rdf_type"]) ? $termMeta["ldp_rdf_type"] : null;
                  if (!empty($rdfType)) echo "                \"@type\" : \"$rdfType\",\n";
                ?>
                "@id": "<?php the_permalink(); ?>"
            }<?php if($wp_query->current_post + 1 < $wp_query->post_count) { echo(",\n"); } else { echo("\n"); } ?>
        <?php endwhile; ?>
        ]
<?php endif; ?>
  }]
}
