<?php
/*
Plugin Name: Mix-It Fit: Integrations
Plugin URI: https://mixit.fit
Description: Server-Side Integrations for Various Fitness
Version: 0.5
Author: Dan Dulaney
Author URI: https://dandulaney.com
License: GPLv2
License URI: 
*/

/**********************************************
 * Load up the integrations functions
 **********************************************************/
function mixit_setup_api_functions() {

    if(class_exists( 'GFForms' )) {

        //Functions specific to GF: dynamic population
        require_once( plugin_dir_path( __FILE__ ) . 'gf/gf-dynamic-population.php');

        //Functions specific to GF: custom validation
        require_once( plugin_dir_path( __FILE__ ) . 'gf/gf-custom-validations.php');

        //Functions specific to GF: post submit handling
        require_once( plugin_dir_path( __FILE__ ) . 'gf/gf-post-submit.php');

        //Functions specific to GF: Auto-delete entries
        require_once( plugin_dir_path( __FILE__ ) . 'gf/gf-autodelete-entries.php');

        //Functions specific to Fitbit
        require_once( plugin_dir_path( __FILE__ ) . 'integrations/fitbit_api.php');

        //Functions specitic to Habitica
        require_once( plugin_dir_path( __FILE__ ) . 'integrations/habitica_api.php');
    }
}
add_action( 'plugins_loaded', 'mixit_setup_api_functions' );