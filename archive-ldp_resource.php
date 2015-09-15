<?php header('Content-Type: application/ld+json'); ?>
<?php header('Access-Control-Allow-Origin: *'); ?>
{
    "@context": "http://owl.openinitiative.com/oicontext.jsonld",
    "@graph": [ {
        "@id" : "",
        "@type" : "http://www.w3.org/ns/ldp#BasicContainer",
        "http://www.w3.org/ns/ldp#contains" : [
            <?php while (have_posts()) : the_post(); ?>{
                "@id": "<?php the_permalink(); ?>"
            }<?php if($wp_query->current_post + 1 < $wp_query->post_count) echo(","); ?> 
        <?php endwhile; ?>
]}
    ]
}

