<?php
/**
 * Class handling everything related to settings page and available options inside them
 **/
if (!class_exists('WpLdpSettings')) {
    class WpLdpSettings {
      /**
       * Default Constructor
       **/
       public function __construct() {

         add_action( 'admin_menu', array($this, 'ldp_menu'));
         add_action( 'admin_init', array($this, 'backend_hooking'));
       }

       ################################
       # Settings
       ################################
       function ldp_menu() {
           add_options_page(
               __('WP-LDP Settings', 'wpldp'),
               __('WP-LDP Settings', 'wpldp'),
               'edit_posts',
               'wpldp',
               array($this, 'wpldp_options_page')
           );
       }

       function wpldp_options_page() {
           echo '<div class="wrap">';
           echo '<h2>' . __('WP-LDP Settings', 'wpldp') . '</h2>';
           echo '<form method="post" action="options.php">';
             settings_fields('ldp_context');
             do_settings_sections('wpldp');
             submit_button();
           echo '</form>';
           echo '</div>';
       }

       function ldp_context_field() {
           echo "<input type='text' size='150' name='ldp_context' value='" . get_option('ldp_context', 'http://owl.openinitiative.com/oicontext.jsonld') . "' />";
       }

       function ldp_container_init_field() {
           $optionValue = !empty(get_option('ldp_container_init', false)) ? true : false;
           // var_dump($optionValue);
           // die();
           echo "<input type='checkbox' name='ldp_container_init' value='ldp_container_init' " . checked(1, get_option('ldp_container_init'), false) . " />";
       }

       function backend_hooking() {
           add_settings_section(
             'ldp_context',
             __('WP-LDP Settings', 'wpldp'),
             function() {
               echo __('The generals settings of the WP-LDP plugin.', 'wpldp');
             },
             'wpldp'
           );

           add_settings_field(
             'ldp_context',
             __('WP-LDP Context', 'wpldp'),
             array($this, 'ldp_context_field'),
             'wpldp',
             'ldp_context'
           );

           add_settings_field(
             'ldp_container_init',
             __('Do you want to initialize PAIR containers ?', 'wpldp'),
             array($this, 'ldp_container_init_field'),
             'wpldp',
             'ldp_context'
           );

           register_setting( 'ldp_context', 'ldp_context' );
           register_setting( 'ldp_container_init', 'ldp_context' );
       }
    }
} else {
    exit ('Class WpLdpSettings already exists');
}

// Instanciating the settings page object
$wpLdpSettings = new WpLdpSettings();

 ?>
