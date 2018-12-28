<?php

/************************************************************
 *  Handles Auto-Deletion of Entries on Form submission 
 *  for the forms we don't need to keep info for (most of them)
 **************************************************************/


/******************************************************
 * Deletes Entries from the Registration Form right after submission
 *****************************************************/
add_action( 'gform_after_submission_1', 'mixit_gf_gform_after_submission_1', 10, 2 ); 
function mixit_gf_gform_after_submission_1 ( $entry, $form ) {
    GFAPI::delete_entry( $entry['id'] );
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

/******************************************************
 * Deletes Entries from the Update Timezone Form right after submission
 *****************************************************/
add_action( 'gform_after_submission_6', 'mixit_gf_gform_after_submission_6', 10, 2 ); 
function mixit_gf_gform_after_submission_6 ( $entry, $form ) {
    GFAPI::delete_entry( $entry['id'] );
}