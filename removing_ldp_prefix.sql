#To get rid of the ldp_ prefix if you need it.
UPDATE `wp_postmeta` SET `meta_key` = replace( `meta_key` , 'ldp_', '' );
