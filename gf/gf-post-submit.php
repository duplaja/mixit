<?php


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

/*****************************************************
 *  Form to delete Habitica integrations (form 7)
 ***************************************************/
add_action( 'gform_after_submission_7', 'mixit_gf_gform_after_submission_7', 10, 2 );
function mixit_gf_gform_after_submission_7($entry, $form) {

    $id_to_delete = rgar($entry,'1');
    $user_id = get_current_user_id();

    $entry_to_delete =  GFAPI::get_entry( $id_to_delete );

    $id_of_to_delete = rgar($entry_to_delete,'created_by');

    if($id_of_to_delete == $user_id) {

        $delete_task = rgar($entry,'3');
        if ($delete_task == 'Yes') {
            $task_id = rgar($entry_to_delete,12);
            mixit_habitica_delete_task($user_id,$task_id);
        } 

        GFAPI::delete_entry( $entry['id'] );
        GFAPI::delete_entry( $id_to_delete );
    }

}