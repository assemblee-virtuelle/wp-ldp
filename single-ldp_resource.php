<?php
    $ldp_container = get_the_terms( $post, 'ldp_container' );

    if ( isset($_SERVER['HTTP_ACCEPT'])
               && strstr($_SERVER['HTTP_ACCEPT'], 'text/html' ) ) {
        header("Location: " . site_url() . "/wp-ldp/front#" . get_rest_url() . "ldp/v1/" . $ldp_container[0]->slug . '/' . $post->post_name . '/' );
    } else {
        header('Content-Type: application/ld+json');
        header('Access-Control-Allow-Origin: *');
        header("Location: " . get_rest_url() . "ldp/v1/" . $ldp_container[0]->slug . '/' . $post->post_name . '/' );
    }
?>
