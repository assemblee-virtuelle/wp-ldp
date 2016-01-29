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

            foreach($fields as $field) {
              if(substr($field->name, 0, 4) == "ldp_") {
                  echo('"'.substr($field->name, 4).'": ');
                  echo('' . json_encode(get_post_custom_values($field->name)[0]) . ',');
                  echo "\n        ";
              }
            }
          ?>

        "@id": "<?php the_permalink(); ?>"
        }
<?php endwhile; ?>
    ]
}
