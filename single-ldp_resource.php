<?php header('Content-Type: application/json+ld'); ?>
<?php header('Access-Control-Allow-Origin: *'); ?>
{
    "@context": "http://owl.openinitiative.com/oicontext.jsonld",
    "@graph": [
<?php while (have_posts()) : the_post(); ?>
        {
        <?php foreach(get_post_custom_keys() as $meta) {
            if(substr($meta, 0, 4) == "ldp_") {
                echo('"'.substr($meta, 4).'": ');
                echo('"'.get_post_custom_values($meta)[0].'",');
                echo "\n        ";
            }
        } ?>

        "@id": "<?php the_permalink(); ?>"
        }
<?php endwhile; ?>
    ]
}
