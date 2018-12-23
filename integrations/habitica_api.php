<?php

/************************************
 * Habitica Specific Functions
 *
 **********************************/

$stored_habitica_user_meta= array(
    '_habitica_id',
    '_habitica_api',
    '_habitica_user_data'
);

/*******************************************************
 * Displays Individual Stats for Habitica for current user
 *********************************************************/
function mixit_habitica_display_individual_user() {
    $user_id = get_current_user_id();
    if($user_id == 0) {
        return '<p>You must be logged in to view this</p>';
    }

    $user_data = get_user_meta($user_id, '_habitica_user_data', true);

    if(empty($user_data)) {
        return '<p>Habitica doesn\'t appear to be linked to your account.</p>';
    } else {

        $to_return = '<h3>Habitica Data: '.$user_data['username'].'</h3><table>
        <tr><td>Level</td><td>'.$user_data["lvl"].' ( '.$user_data["exp"].'/'.$user_data["xp_to_next"].' xp to lvl up)</td></tr>
        <tr><td>Health</td><td>'.$user_data["hp"].' / '.$user_data["max_health"].'hp</td></tr>
        <tr><td>MP</td><td>'.$user_data["mp"].' / '.$user_data["max_mp"].'mp</td></tr>
        <tr><td>Gold </td><td>'.round($user_data["gp"],3).' gp</td></tr></table>';
        //mixit_habitica_create_task();
        return $to_return;
    }

}
add_shortcode( 'mixit_habitica_display_mine', 'mixit_habitica_display_individual_user' );


/*************************************************
 * Bulk updates user stats for all Habitica registered users
 * Run via Cron
 ************************************************/
function mixit_habitica_bulk_update_values() {

    $args = array(
        'meta_key'     => '_habitica_api',
        'meta_value'   => '',
        'meta_compare' => '!=',
    );
    $habitica_users = get_users($args);

    foreach($habitica_users as $habitica_user) {
        
        mixit_habitica_retrieve_user($habitica_user->ID,'no','id','api');

    }

}


/***********************************************************************
 * Updates User Stats From Habitica per user
 * Takes $user_id as a parameter, first_run to use if checking validation
 * Returns false if it fails for any reason
 ***********************************************************************/
function mixit_habitica_retrieve_user($user_id, $first_run = 'no',$habitica_id=null,$habitica_api_key=null) {

    if ($first_run == 'no') {
        $habitica_id = get_user_meta($user_id,'_habitica_id',true);
        $habitica_api_key = get_user_meta($user_id,'_habitica_api',true);
    }

    if (empty($habitica_id) || empty($habitica_api_key)) {
        return false;
    } 

    $post_url = 'https://habitica.com/api/v3/user';

    $request  = new WP_Http();

    $headers = array(
        'x-api-user' => $habitica_id,
        'x-api-key' => $habitica_api_key,
    );
    
    $response = wp_remote_get( $post_url, array('headers' =>$headers));
    $response_code = wp_remote_retrieve_response_code( $response );
    
    if ($response_code == '200') {

        $body = json_decode(wp_remote_retrieve_body($response));
        $habitica_user_data = array(
            'username'=>$body->data->auth->local->username,
            'hp'=> $body->data->stats->hp,
            'mp'=> $body->data->stats->mp,
            'exp' => $body->data->stats->exp,
            'lvl' => $body->data->stats->lvl,
            'gp' => $body->data->stats->gp,
            'xp_to_next' => $body->data->stats->toNextLevel,
            'max_health' => $body->data->stats->maxHealth,
            'max_mp'=> $body->data->stats->maxMP
        );

        update_user_meta( $user_id, '_habitica_user_data', $habitica_user_data);

        return true;

    } else {
        return false;
    }
    

    return true;

}

function mixit_habitica_create_task($task_text,$task_type = 'daily',$difficulty = 1,$habit_type='both',$frequency = 'daily',$start_date,$repeat_raw,$everyX = 1) {

    $user_id = get_current_user_id();
    $habitica_id = get_user_meta($user_id,'_habitica_id',true);
    $habitica_api_key = get_user_meta($user_id,'_habitica_api',true);

    if (empty($habitica_id) || empty($habitica_api_key)) {
        return false;
    } 

    $user_timezone = get_user_meta($user_id,'_user_timezone',true);


    $post_url = 'https://habitica.com/api/v3/tasks/user';

    $date_now = new DateTime("now", new DateTimeZone("$user_timezone") );
    $date_now_disp = $date_now->format('m/d/Y');

    $task_notes = 'Created through Mixit.fit on '.$date_now_disp.'. Depending on your Mix-It settings, this task may be auto-marked when done.';

    $request  = new WP_Http();

    $headers = array(
        'x-api-user' => $habitica_id,
        'x-api-key' => $habitica_api_key,
    );

    $formatted_date = new DateTime("$start_date",new DateTimeZone("$user_timezone"));
    $formatted_date ->setTimezone(new DateTimeZone('Etc/UTC'));
    $start_date = $formatted_date->format('Y-m-d\TH:i:s\Z');

    $body = array(
        'text' => $task_text.': Mix-It',
        'type' => $task_type, //Allowed values: "habit", "daily", "todo", "reward"
        'notes' => $task_notes,
        'priority' => $difficulty, //Allowed values: "0.1", "1", "1.5", "2"
        'startDate' => $start_date,
    );
    if($task_type == 'daily') {
        $body['frequency']=$frequency;
        if ($frequency == 'weekly') {

            switch($repeat_raw) {

                case 'every':
                    $repeating = true;
                    break;
                case 'weekends':
                    $repeating = '{"m":false,"t":false,"w":false,"th":false,"f":false}';
                    break;
                case 'weekdays':
                    $repeating = '{"su":false,"s":false}';
                    break;
                case 'mwf':
                    $repeating = '{"su":false,"t":false,"th":false,"s":false}';
                    break;    
                case 'tr':
                    $repeating = '{"su":false,"m":false,"w":false,"f":false,"s":false}';
                    break;
                case 'sun':
                    $repeating = '{"m":false,"t":false,"w":false,"th":false,"f":false,"s":false}';
                    break;                
                case 'mon':
                    $repeating = '{"su":false,"t":false,"w":false,"th":false,"f":false,"s":false}';
                    break;
                case 'tues':
                    $repeating = '{"su":false,"m":false,"w":false,"th":false,"f":false,"s":false}';
                    break;
                case 'wed':
                    $repeating = '{"su":false,"m":false,"t":false,"th":false,"f":false,"s":false}';
                    break;
                case 'thurs':
                    $repeating = '{"su":false,"m":false,"t":false,"w":false,"f":false,"s":false}';
                    break;    
                case 'fri':
                    $repeating = '{"su":false,"m":false,"t":false,"w":false,"th":false,"s":false}';
                    break;
                case 'sat':
                    $repeating = '{"su":false,"m":false,"t":false,"w":false,"th":false,"f":false,"s":true}';
                    break;
                default:
                    $repeating = true;
            }

            //var_dump($repeating);

            $body['repeat'] = $repeating;

        } 
        elseif ($frequency == 'daily') {

            $body['everyX'] = $everyX;
        }
    } 
    elseif ($task_type == 'habit') {
        if($habit_type == 'up') {
            $body['down'] = false;
        } 
        elseif ($habit_type == 'down') {
            $body['up'] = false;
        }   
    }
    
    $response = $request->post( $post_url, array('headers' =>$headers, 'body' => $body ) );

    $body = json_decode(wp_remote_retrieve_body($response));
    

    if ($body->success) {
    
        $task_id = $body->data->id;
    
        return $task_id;
    } 
    else {
        return 'no';
    }
}


function  mixit_habitica_vote_task($user_id,$task_id,$direction = 'up') {

    $habitica_id = get_user_meta($user_id,'_habitica_id',true);
    $habitica_api_key = get_user_meta($user_id,'_habitica_api',true);

    $request  = new WP_Http();

    $headers = array(
        'x-api-user' => $habitica_id,
        'x-api-key' => $habitica_api_key,
    );
    $post_url = "https://habitica.com/api/v3/tasks/$task_id/score/$direction";
    $response = $request->post( $post_url, array('headers' =>$headers, 'body' => array() ) );

    $response_code = wp_remote_retrieve_response_code( $response );
    
    if ($response_code == '200') {
        return true;
    } else {
        return false;
    }
}

function  mixit_habitica_site_cron($user_id) {

    $habitica_id = get_user_meta($user_id,'_habitica_id',true);
    $habitica_api_key = get_user_meta($user_id,'_habitica_api',true);

    $request  = new WP_Http();

    $headers = array(
        'x-api-user' => $habitica_id,
        'x-api-key' => $habitica_api_key,
    );
    $post_url = "https://habitica.com/api/v3/cron";
    $response = $request->post( $post_url, array('headers' =>$headers, 'body' => array() ) );
    $response_code = wp_remote_retrieve_response_code( $response );
    if ($response_code == '200') {
        return true;
    } else {
        return false;
    }
}

function  mixit_habitica_delete_task($user_id,$task_id) {

    $habitica_id = get_user_meta($user_id,'_habitica_id',true);
    $habitica_api_key = get_user_meta($user_id,'_habitica_api',true);
    $request  = new WP_Http();
    $headers = array(
        'x-api-user' => $habitica_id,
        'x-api-key' => $habitica_api_key,
    );
    $post_url = "https://habitica.com/api/v3/tasks/$task_id";
    $response = $request->request( $post_url, array('method'=>'DELETE','headers' =>$headers, 'body' => array() ) );
    $response_code = wp_remote_retrieve_response_code( $response );
    if ($response_code == '200') {
        return true;
    } else {
        return false;
    }
}


function mixit_habitica_fitbit_daily_completion_check() {
    $form_id = 5; //Habitica Integration ID
    $search_criteria = array(
        'status'        => 'active',
        'field_filters' => array(
            array(
                'key'   => '8',
                'value' => 'fitbit'
            ),
            array(
                'key'   => '3',
                'value' => 'daily'
            ),
            array(
                'key'   => '19',
                'value' => 'no'
            )
        )
    );
    $total_count = 0;    
    $paging = array( 'offset' => 0, 'page_size' => 400 );
    $entries = GFAPI::get_entries( $form_id ,$search_criteria,array(),$paging,$total_count);
    
    if ($total_count >= 250) {
        error_log("Approaching a limit: Optimize since we have ".$total_count);
    }
    
    foreach($entries as $entry) {

        $to_check = rgar($entry,9);
        $type_comp = rgar($entry,10);
        $num_to_check = rgar($entry,11);
        $user_id = rgar( $entry, 'created_by' );
        $entry_id = rgar($entry,'id');
        $task_id = rgar($entry,12);

        switch ($to_check) {
            case 'daily-steps':
                $check_meta = get_user_meta($user_id, '_fitbit_steps_today', true);
                break;
            case 'calories-in':
                $check_meta = get_user_meta($user_id, '_fitbit_calories_in_today', true);
                break;
            case 'calories-out':
                $check_meta = get_user_meta($user_id, '_fitbit_calories_out_today', true);
                break;
            case 'daily-distance':
                $check_meta = get_user_meta($user_id, '_fitbit_distance_today', true);
                break;
            default:
                continue;
        }

        if ($type_comp == 'more' && $check_meta > $num_to_check) {


            if(mixit_habitica_vote_task($user_id,$task_id,'up')) {
                //Mark task out of rotation til tomorrow
                GFAPI::update_entry_field( $entry_id, 19, 'yes' );
            }

        } 
        elseif ($type_comp == 'less' && $check_meta < $num_to_check) {

            if(mixit_habitica_vote_task($user_id,$task_id,'up')) {
                //Mark task out of rotation til tomorrow
                GFAPI::update_entry_field( $entry_id, 19, 'yes' );
            }

        }

    }

}


/********************************************************
 *  Function to check for completion of Fitbit Todo Goals
 *  Deletes entry once complete
 */
function mixit_habitica_fitbit_todo_completion_check() {
    $form_id = 5; //Habitica Integration ID
    $search_criteria = array(
        'status'        => 'active',
        'field_filters' => array(
            array(
                'key'   => '8',
                'value' => 'fitbit'
            ),
            array(
                'key'   => '3',
                'value' => 'todo'
            )
        )
    );
    $total_count = 0;    
    $paging = array( 'offset' => 0, 'page_size' => 400 );
    $entries = GFAPI::get_entries( $form_id ,$search_criteria,array(),$paging,$total_count);
    
    if ($total_count >= 250) {
        error_log("Approaching a limit: Optimize since we have ".$total_count);
    }
    
    foreach($entries as $entry) {

        $to_check = rgar($entry,9);
        $type_comp = rgar($entry,10);
        $num_to_check = rgar($entry,11);
        $user_id = rgar( $entry, 'created_by' );
        $entry_id = rgar($entry,'id');
        $task_id = rgar($entry,12);

        switch ($to_check) {
            case 'weight':
                $check_meta = get_user_meta($user_id, '_fitbit_weight_today', true);
                break;
            case 'lifetime-distance':
                $check_meta = get_user_meta($user_id, '_fitbit_distance_today', true);
                break;
            default:
                continue;
        }

        if ($type_comp == 'more' && $check_meta > $num_to_check) {


            if(mixit_habitica_vote_task($user_id,$task_id,'up')) {
                //Mark task out of rotation til tomorrow
                $result = GFAPI::delete_entry( $entry_id );
            }

        } 
        elseif ($type_comp == 'less' && $check_meta < $num_to_check) {

            if(mixit_habitica_vote_task($user_id,$task_id,'up')) {
                //Mark task out of rotation til tomorrow        
                $result = GFAPI::delete_entry( $entry_id );

            }

        }

    }

}


/********************************************************************
 *  Function to reset all "previously marked" dailies to available again. 
 *  Happens before 2am local time to the user
 ********************************************************************/
function mixit_habitica_reset_daily_check() {
    
    $all_timezones = get_user_meta_values('_user_timezone');

    $user_ids_to_reset = array();

    foreach ($all_timezones as $tz) {
        $date_now = new DateTime("now", new DateTimeZone("$tz") );
        $date_now_disp = $date_now->format('H');
        //Reset any user tasks that have been previously closed, if it's between midnight and 2 am local time for the user
        if ($date_now_disp < 2) {

            $users = get_users(array(
                'meta_key'     => '_user_timezone',
                'meta_value'   => "$tz",
                'fields'=>'ID'
            ));

            foreach($users as $user) {
                $user_ids_to_reset[] = $user;
            }

        }

    }

    foreach ($user_ids_to_reset as $user_id) {
        mixit_habitica_reset_daily_by_user($user_id);
    }
}

/**********************************************************
 *  Function to reset all daily tasks to available by user ID
 ***********************************************************/
function mixit_habitica_reset_daily_by_user($user_id) {

    $form_id = 5; //Habitica Integration ID
    $search_criteria = array(
        'created_by'        => $user_id,
        'field_filters' => array(
            array(
                'key'   => '19',
                'value' => 'yes'
            )
        )
    );
    $total_count = 0;    
    $paging = array( 'offset' => 0, 'page_size' => 400 );
    $entries = GFAPI::get_entries( $form_id ,$search_criteria,array(),$paging,$total_count);

    var_dump($entries);

    foreach ($entries as $entry) {
        $entry_id = rgar($entry,'id');
        GFAPI::update_entry_field( $entry_id, 19, 'no' );
    }
}

/**********************************************************
 * Gets all unique values for a particular user meta field key
 *********************************************************/
function get_user_meta_values($key) {
    global $wpdb;
	$result = $wpdb->get_col( 
		$wpdb->prepare( "
			SELECT DISTINCT pm.meta_value FROM {$wpdb->usermeta} pm
			LEFT JOIN {$wpdb->users} p ON p.ID = pm.user_id
			WHERE pm.meta_key = '%s' 
			ORDER BY pm.meta_value", 
			$key
		) 
	);

	return $result;
}