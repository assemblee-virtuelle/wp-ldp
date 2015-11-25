<?php header('Content-Type: application/ld+json'); ?>
<?php header('Access-Control-Allow-Origin: *'); ?>
{
    "@context": "<?php echo get_option('ldp_context', 'http://owl.openinitiative.com/oicontext.jsonld'); ?>",
    "@graph": [ {
        "@id" : "",
        "@type" : "http://www.w3.org/ns/ldp#BasicContainer",
        "http://www.w3.org/ns/ldp#contains" : [
          <?php
          $output = 'objects'; // or names
          $terms = get_terms(array('ldp_container'), array('hide_empty'=>false));
          foreach( $terms as $term ) :
             ?>{
                "@id": "<?php echo site_url(); ?>/ldp_resource/<?php echo $term->slug;?>"
            }
        <?php endforeach; ?>
]}
    ]
}
