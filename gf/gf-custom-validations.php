<?php

/************************
 * Custom validations for GF Forms
 *******************************/

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
