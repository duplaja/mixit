<?php

/**********************************
 * Handles Dynamic Population of various Gravity Forms Fields
 **************************************/

 /**********************************************
  * Pre-populates dropdown in Form 7 (delete / view integrations) with active integrations
  * for the current user
  ***********************************************/

add_filter( 'gform_pre_render_7', 'populate_posts' );
add_filter( 'gform_pre_validation_7', 'populate_posts' );
add_filter( 'gform_pre_submission_filter_7', 'populate_posts' );
add_filter( 'gform_admin_pre_render_7', 'populate_posts' );
function populate_posts( $form ) {
 
    $user_id = get_current_user_id();

    $form_id = 5; //Habitica Integration ID
    $search_criteria = array(
        'created_by'        => $user_id
    );
    $total_count = 0;    
    $paging = array( 'offset' => 0, 'page_size' => 200 );
    $entries = GFAPI::get_entries( $form_id ,$search_criteria,array(),$paging,$total_count);

 
    foreach ( $form['fields'] as &$field ) {
 
        if ( $field->type != 'select' || strpos( $field->cssClass, 'populate-active-habitica' ) === false ) {
            continue;
        }
 

        $choices = array();

        if (empty($entries)) {

            $field->placeholder = 'No Active Integrations';
            $field->choices = $choices;
            continue;
        }
     
        foreach($entries as $entry) {
            $integration_type = ucwords(rgar($entry,8));
            $name = rgar($entry,1);
            $done_today = ucfirst(rgar($entry,19));
            $id = rgar($entry,'id');

            $choices[] = array('text'=>"$integration_type: $name (Completed Today: $done_today)",'value'=>$id);

        }

        $field->placeholder = 'Select An Integration';
        $field->choices = $choices;
 
    }
 
    return $form;
}