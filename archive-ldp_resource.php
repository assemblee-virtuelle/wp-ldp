<?php
    $category = $wp_query->get_queried_object();

    if ( isset($_SERVER['HTTP_ACCEPT'])
         && strstr($_SERVER['HTTP_ACCEPT'], 'text/html' ) ) {
        header("Location: " . site_url() . "/wp-ldp/front#" . get_rest_url() . "ldp/v1/" . $category->slug . '/' );
    } else {
        header('Content-Type: application/ld+json');
        header('Access-Control-Allow-Origin: *');
        header("Location: " . get_rest_url() . "ldp/v1/" . $category->slug . '/' );
    }
?>
