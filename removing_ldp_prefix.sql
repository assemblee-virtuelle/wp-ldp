#To get rid of the ldp_ prefix if you need it.
UPDATE `wp_postmeta`
  SET `meta_key` = replace( `meta_key` , 'ldp_', '' );
UPDATE `wp_options`
  SET `option_value` = replace( `option_value` , 'ldp_', '' );
UPDATE `wp_options`
  SET `option_value` = replace( `option_value` , 'model', 'ldp_model' )
WHERE `option_name` LIKE 'ldp_container%';
