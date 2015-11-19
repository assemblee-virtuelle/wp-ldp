<?php header('Content-Type: application/ld+json'); ?>
<?php header('Access-Control-Allow-Origin: *'); ?>
{
    "@context": "http://owl.openinitiative.com/oicontext.jsonld",
    "@graph": [
<?php while (have_posts()) : the_post(); ?>
        {
          <?php
            $value = get_the_terms($post->ID, 'ldp_container')[0];
            $termMeta = get_option("ldp_container_$value->term_id");
            $modelsDecoded = json_decode($termMeta["ldp_model"]);
            $fields = $modelsDecoded->{$value->slug}->fields;

            foreach($fields as $field) {
              if(substr($field->name, 0, 4) == "ldp_") {
                  echo('"'.substr($field->name, 4).'": ');
                  echo('"'.get_post_custom_values($field->name)[0].'",');
                  echo "\n        ";
              }
            }
          ?>

        "@id": "<?php the_permalink(); ?>"
        }
<?php endwhile; ?>
    ]
}
