<?php header('Content-Type: application/ld+json'); ?>
<?php header('Access-Control-Allow-Origin: *'); ?>
<?php include_once('../../../../wp-load.php'); ?>

<?php
    query_posts( array(
       'post_type' => 'ldp_resource',
       'posts_per_page' => -1 )
     );
$array = [];
?>
{
    "@context": "<?php echo get_option('ldp_context', 'http://lov.okfn.org/dataset/lov/context'); ?>",
    "@graph": [ {
        "@id" : "<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>",
        "@type" : "http://www.w3.org/ns/ldp#BasicContainer"<?php if ( have_posts() ) : ?>,
        "http://www.w3.org/ns/ldp#contains" : [
<?php while (have_posts()) : the_post(); ?>
<?php
                  $values = get_the_terms($post->ID, 'ldp_container');
                  if (empty($values[0])) {
                    $value = reset($values);
                  } else {
                    $value = $values[0];
                  }
                  $termMeta = get_option("ldp_container_$value->term_id");
                  $rdfType = isset($termMeta["ldp_rdf_type"]) ? $termMeta["ldp_rdf_type"] : null;

                  if($rdfType != null){
                      if(array_key_exists($rdfType,$array)){
                        $array[$rdfType]++;
                      }
                      else{
                          $array[$rdfType]=1;
                      }
                  }
                ?>
<?php endwhile; ?>
<?php foreach ($array as $key => $value){
            echo "            {\n";
            echo "                \"@type\" : \"$key\",\n";
            echo "                \"@count\" : \"$value\"\n";
            echo "            }\n";
        }

        ?>

        ]
<?php endif; ?>
  }]
}
