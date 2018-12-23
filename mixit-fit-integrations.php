<?php
/*
Plugin Name: Mix-It Fit: Integrations
Plugin URI: https://mixit.fit
Description: Server-Side Integrations for Various Fitness
Version: 0.3
Author: Dan Dulaney
Author URI: https://dandulaney.com
License: GPLv2
License URI: 
*/

/*******
 * Temp: Delete once fixed in core
 */
/*
add_filter('wp_check_filetype_and_ext', function($values, $file, $filename, $mimes) {
	if ( extension_loaded( 'fileinfo' ) ) {
		// with the php-extension, a CSV file is issues type text/plain so we fix that back to 
		// text/csv by trusting the file extension.
		$finfo     = finfo_open( FILEINFO_MIME_TYPE );
		$real_mime = finfo_file( $finfo, $file );
		finfo_close( $finfo );
		if ( $real_mime === 'text/plain' && preg_match( '/\.(csv)$/i', $filename ) ) {
			$values['ext']  = 'csv';
			$values['type'] = 'text/csv';
		}
	} else {
		// without the php-extension, we probably don't have the issue at all, but just to be sure...
		if ( preg_match( '/\.(csv)$/i', $filename ) ) {
			$values['ext']  = 'csv';
			$values['type'] = 'text/csv';
		}
	}
	return $values;
}, PHP_INT_MAX, 4);*/

function mixit_setup_api_functions() {
    require_once( plugin_dir_path( __FILE__ ) . 'integrations/fitbit_api.php');
    require_once( plugin_dir_path( __FILE__ ) . 'integrations/habitica_api.php');
}
add_action( 'plugins_loaded', 'mixit_setup_api_functions' );

/******************************************************
 * Handles Validation of HabitiFit Setup Form (2 in my install)
 * Tries to create an auth token from passed Auth Code
 * Throws GFORM validation error if it cannot create it
 *********************************************************/
add_filter( 'gform_validation_2', 'mixit_gf_validate_fitbit_token' );
function mixit_gf_validate_fitbit_token( $validation_result ) {
 
    $field_id = 5;
    $form  = $validation_result['form'];
    $entry = GFFormsModel::get_current_lead();
    $auth_code  = rgar( $entry, '5' );

    if (!mixit_fitbit_get_token_from_code($auth_code)) {
        // validation failed
        $validation_result['is_valid'] = false;
 
        //finding Field with ID of 1 and marking it as failed validation
        foreach ( $form['fields'] as &$field ) {
 
            //NOTE: replace 1 with the field you would like to validate
            if ( $field->id == $field_id ) {
                $field->failed_validation  = true;
                $field->validation_message = 'Please click the button above to generate a new code!';
                break;
            }
        }
    }
    //Assign modified $form object back to the validation result
    $validation_result['form'] = $form;
 
    return $validation_result;
}

/******************************************************
 * Handles Validation of HabitiFit Setup Form (2 in my install)
 * Tries to create an auth token from passed Auth Code
 * Throws GFORM validation error if it cannot create it
 *********************************************************/
add_filter( 'gform_validation_3', 'mixit_gf_validate_habitica_token' );
function mixit_gf_validate_habitica_token( $validation_result ) {
 
    $form  = $validation_result['form'];
    $entry = GFFormsModel::get_current_lead();
    $habitica_id  = rgar( $entry, '9' );
    $habitica_api  = rgar( $entry, '10' );

    $user_id = get_current_user_id();

    $check = mixit_habitica_retrieve_user($user_id,'yes',$habitica_id,$habitica_api);

    if (!$check) {
        // validation failed
        $validation_result['is_valid'] = false;
 
        //finding Field with ID of 9 and 10 and marking it as failed validation
        foreach ( $form['fields'] as &$field ) {
 
            //NOTE: replace 1 with the field you would like to validate
            if ( $field->id == 9 || $field->id == 10) {
                $field->failed_validation  = true;
                $field->validation_message = 'Invalid User ID or API Code.';
            }
        }
    }
    //Assign modified $form object back to the validation result
    $validation_result['form'] = $form;

    return $validation_result;
}

/******************************************************
 * Deletes Entries from the Fitbit API Form right after submission
 *****************************************************/
add_action( 'gform_after_submission_2', 'mixit_gf_gform_after_submission_2', 10, 2 ); 
function mixit_gf_gform_after_submission_2 ( $entry, $form ) {
    GFAPI::delete_entry( $entry['id'] );
}

/******************************************************
 * Deletes Entries from the Habitica API Form right after submission
 *****************************************************/
add_action( 'gform_after_submission_3', 'mixit_gf_gform_after_submission_3', 10, 2 ); 
function mixit_gf_gform_after_submission_3 ( $entry, $form ) {
    GFAPI::delete_entry( $entry['id'] );
}

/*****************************************************
 *  Form to create Fitbit Integrations (form 5)
 ***************************************************/
add_action( 'gform_after_submission_5', 'mixit_gf_gform_after_submission_5', 10, 2 );
function mixit_gf_gform_after_submission_5($entry, $form) {

    $entry_id = $entry['id'];

    $task_text = rgar($entry,'1');
    $task_type = rgar($entry,'3');
    $difficulty = rgar($entry,'4');
    $habit_type = rgar($entry,'5');
    $frequency = rgar($entry,'6');
    $integration = rgar($entry,'8');
    $start_date = rgar($entry,'17');

    $repeat_raw = rgar($entry,'15');
    $everyX = rgar($entry,'16');


    $task_id = mixit_habitica_create_task($task_text,$task_type,$difficulty,$habit_type,$frequency,$start_date,$repeat_raw,$everyX);

    if ($task_id != 'no') {
        if($integration == 'none') {
            $result = GFAPI::delete_entry( $entry_id );
            
        } else {
            $result = GFAPI::update_entry_field( $entry_id, '12', $task_id );
        }
    } else {
        $result = GFAPI::delete_entry( $entry_id );

        echo '<h2>Something went wrong when creating your task. Please try again.</h2>';
    }
}