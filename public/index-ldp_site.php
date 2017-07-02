<?php
// Include WordPress
define('WP_USE_THEMES', true);
include_once('../../../../wp-load.php');
$url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
$urlExplode = explode('site/',$url);
$slug = rtrim($urlExplode[1],'/');
$ldpSiteUrls = array();
if ( $slug ) {
    $term = get_term_by( 'slug', $slug, 'ldp_site' );
    $ldpSiteUrls[] = get_term_meta( $term->term_id, "ldp_site_url", true );

} else {
    $terms = get_terms(array(
      'taxonomy' => 'ldp_site',
      'hide_empty' => false,
    ));

    foreach ( $terms as $term ){
        $possibleUrl = get_term_meta( $term->term_id, "ldp_site_url", true );
        if ( $possibleUrl ) {
            $ldpSiteUrls[] =$possibleUrl;
        }
    }

}

$outputs = array();
foreach ($ldpSiteUrls as $ldpSiteUrl){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $ldpSiteUrl.'/ldp/');
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/ld+json', 'Accept: application/ld+json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $outputs[$ldpSiteUrl.'/ldp/']['data'] = curl_exec($ch);
    $outputs[$ldpSiteUrl.'/ldp/']['code'] = curl_getinfo($ch)['http_code'];
}


get_header();
?>
  <div id="wrapper">
      <?php
      foreach ($outputs as $output){
          if($output['code'] == 200){
              $outputJson = json_decode($output['data']);
              $test= $outputJson->{'@graph'};
              foreach ($outputJson->{'@graph'} as $content){
                  echo 'site : '.$content->{'@id'}."<br>";
                  echo "<br>";
                  if ( isset( $content->{'http://www.w3.org/ns/ldp#contains'} ) ) {
                      foreach ($content->{'http://www.w3.org/ns/ldp#contains'} as $data){
                          echo '    id :'.$data->{'@id'}."<br>";
                          echo '    type :'.$data->{'@type'}."<br>";
                          echo '    count :'.$data->{'@count'}."<br>";
                          echo "<br>";
                      }
                  }
                  echo "<br>";
              }
          }
      }
      ?>
  </div>
<?php
      get_footer();
?>
