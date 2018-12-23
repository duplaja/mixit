<?php

/************************************
 * Fitbit Specific Functions
 *
 **********************************/

$stored_fitbit_user_meta= array(
    '_fitbit_access_token',
    '_fitbit_refresh_token',
    '_fitbit_user_id',
    '_fitbit_scope',
    '_fitbit_steps_today',
    '_fitbit_distance_today',
    '_fitbit_weight_today',
    '_fitbit_measurements',
    '_fitbit_calories_out_today',
    '_fitbit_calories_in_today'
);

function mixit_fitbit_return_units($unit,$type) {

    if ($unit == 'en_US') {

        if ($type == 'weight') {
            return 'lbs';
        } 
        elseif ($type == 'distance') {
            return 'miles';
        }
    } 
    elseif ($unit == 'en_GB') {

        if ($type == 'weight') {
            return 'kg';
        } 
        elseif ($type == 'distance') {
            return 'km';
        }
    }
    return '';

}

function mixit_fibit_display_individual_user() {

    $user_id = get_current_user_id();
    $measurements = get_user_meta($user_id,'_fitbit_measurements',true);
    $scope = get_user_meta($user_id,'_fitbit_scope',true);
    if ($user_id == 0) {
        return '<p>You must be logged in to see this information.</p>';
    }
    elseif(empty($measurements) || empty($scope)) {
        return '<p>Your Fitbit Account does not appear to be linked.</p>';
    }

    $stored_fitbit_meta_activity = array( '_fitbit_steps_today','_fitbit_distance_today','_fitbit_calories_out_today');
    $stored_fitbit_meta_weight = array('_fitbit_weight_today');
    $stored_fitbit_meta_nutrition = array('_fitbit_calories_in_today');

    $to_dispay = array();

    $scope_array = explode(' ',$scope);


    if(in_array('activity',$scope_array)) {
        foreach ($stored_fitbit_meta_activity as $meta_key) {
            $value = get_user_meta($user_id,$meta_key,true);
            $title = str_replace('_fitbit_','',$meta_key);
            $title = str_replace('_today','',$title);
            $title = ucwords(str_replace('_',' ',$title));

            $to_display[$title]=$value;
        }
    }
    if(in_array('nutrition',$scope_array)) {
        foreach ($stored_fitbit_meta_nutrition as $meta_key) {
            $value = get_user_meta($user_id,$meta_key,true);
            $title = str_replace('_fitbit_','',$meta_key);
            $title = str_replace('_today','',$title);
            $title = ucwords(str_replace('_',' ',$title));
            $to_display[$title]=$value;
        }
    }
    if(in_array('weight',$scope_array)) {
        foreach ($stored_fitbit_meta_weight as $meta_key) {
            $value = get_user_meta($user_id,$meta_key,true);
            $title = str_replace('_fitbit_','',$meta_key);
            $title = str_replace('_today','',$title);
            $title = ucwords(str_replace('_',' ',$title));
            $to_display[$title]=$value;
        }
    }

    if (empty($to_display)) { return '<p>No Fitbit Data Found</p>'; }
    
    $to_return = '<h3>Daily Fitbit Data</h3><table>';

    foreach($to_display as $key=>$value) {
        $to_return .= "<tr><td>$key</td><td>";
        if ($key == 'Distance') {
            $to_return.=round($value,2);
        } else {
            $to_return .= $value;
        }
        if ($key == 'Weight' || $key == 'Distance') {
            $to_return .= ' '.mixit_fitbit_return_units($measurements,strtolower($key));
        }
        $to_return.= '</tr>';
    }

    $to_return .='</table>';

    return $to_return;
    
} 
add_shortcode( 'mixit_fitbit_display_mine', 'mixit_fibit_display_individual_user' );

/***************************
 * Updates all Fitbit data on a per-user basis of a particular type
 **************************/
function mixit_fitbit_update_user_data_type($user_id, $type) {

    if(empty($user_id) || $user_id == 0 || empty($type)) { 
        return false; 
    }

    $access_token = get_user_meta($user_id,'_fitbit_access_token',true);
    $measurements = get_user_meta($user_id,'_fitbit_measurements',true);

    if($type == 'activity') {

        $url_array = array(
            '_fitbit_steps_today'=>'https://api.fitbit.com/1/user/-/activities/steps/date/today/1d.json',
            '_fitbit_distance_today'=>'https://api.fitbit.com/1/user/-/activities/distance/date/today/1d.json',
            '_fitbit_calories_out_today'=>'https://api.fitbit.com/1/user/-/activities/calories/date/today/1d.json',

        );

    } 
    elseif($type == 'weight') {
        $url_array = array(
            '_fitbit_weight_today' =>'https://api.fitbit.com/1/user/-/body/weight/date/today/1d.json',
        );
    } elseif ($type == 'nutrition') {
        $url_array = array(
            '_fitbit_calories_in_today' => 'https://api.fitbit.com/1/user/-/foods/log/caloriesIn/date/today/1d.json',
        );
    }
    else {
        return false;
    }


    foreach($url_array as $key => $post_url) {

        $headers = array(
            'Authorization' => 'Bearer ' . $access_token,
            'Accept-Language' => $measurements,
        );
    
        $response = wp_remote_get( $post_url, array('headers'=>$headers) );
        $response_code = wp_remote_retrieve_response_code( $response );
    
        if ($response_code == 401) {
            if(!mixit_fitbit_renew_token($user_id)) {
                return false;
            } else {
    
                $access_token = get_user_meta($user_id,'_fitbit_access_token',true);
                $headers = array(
                    'Authorization' => 'Bearer ' . $access_token,
                );
            
                $response = wp_remote_get( $post_url, array('headers'=>$headers) );
                $response_code = wp_remote_retrieve_response_code( $response );
            }
        } 
        if($response_code == 200) {
            $body = json_decode(wp_remote_retrieve_body($response));

            if($key == '_fitbit_steps_today') {
                $meta_val = $body->{'activities-steps'}[0]->value;                
            }
            elseif($key == '_fitbit_distance_today') {
                $meta_val = $body->{'activities-distance'}[0]->value;                
            }
            elseif($key == '_fitbit_calories_out_today') {
                $meta_val = $body->{'activities-calories'}[0]->value;                
            }
            elseif($key == '_fitbit_calories_in_today') {
                $meta_val = $body->{'foods-log-caloriesIn'}[0]->value;                
            }
            elseif ($key == '_fitbit_weight_today') {
                $meta_val = $body->{'body-weight'}[0]->value;
            }
             else {
                $meta_val = '';
            }
            if (!empty($meta_val) || is_numeric($meta_val)) {
                    update_user_meta( $user_id, $key, $meta_val);
            }
            
        } else {
        
            return false;
        
        }
    }
  
    return true;  
    
}


 /**************************************************************
 *  Updates All Fitbit User Meta by Scope for a Single User
 *  Accepts a user ID
 **************************************************************/
function mixit_fitbit_update_user_values($user_id) {

    $fitbit_scope = get_user_meta($user_id,'_fitbit_scope',true);


    if(!empty($fitbit_scope)){
        $scope_array = explode(' ',$fitbit_scope);

        foreach ($scope_array as $type) {

            mixit_fitbit_update_user_data_type($user_id, $type);

        }
    }
    
}

/*************************************************
 * Bulk updates Steps, Weight, Distance, Calories for all Fitbit registered users
 * Run via Cron Every 8 minutes
 ************************************************/
function mixit_fitbit_bulk_update_values() {

    $args = array(
        'meta_key'     => '_fitbit_refresh_token',
        'meta_value'   => '',
        'meta_compare' => '!=',
    );
    $fitbit_users = get_users($args);

    foreach($fitbit_users as $fitbit_user) {
        
        mixit_fitbit_update_user_values($fitbit_user->ID);

    }

}

/**************************************************************************
 *  Uses User Meta Stored Renewal Token to Refresh Access Token
 *  Accepts valid user as parameter 
 *  Returns true on success, false if no refresh token or fails to get renewal
 **************************************************************************/
function mixit_fitbit_renew_token($user_id) {

    $refresh_token = get_user_meta($user_id,'_fitbit_refresh_token',true);

    if(empty($refresh_token) || empty($user_id) || $user_id == 0) {
        return false;
    }

    $client_id = get_option('mixit_fitbit_client_id');
    $client_secret = get_option('mixit_fitbit_client_secret');
    
    $post_url = 'https://api.fitbit.com/oauth2/token';
    $body = array(
        'refresh_token' => $refresh_token,
        'grant_type'  => 'refresh_token',
    );

    $request  = new WP_Http();

    $headers = array(
        'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret )
    );
    
    $response = $request->post( $post_url, array('headers' =>$headers, 'body' => $body ) );
    $response_code = wp_remote_retrieve_response_code( $response );

    if($response_code == 200) {
        $body = json_decode(wp_remote_retrieve_body($response));

        $access_token = $body->access_token;
        $refresh_token = $body->refresh_token;
        $fitbit_user_id = $body->user_id;
        $fitbit_scope = $body->scope;

        if (!empty($access_token)&& !empty($refresh_token) && !empty($fitbit_user_id) && !empty($fitbit_scope) && $user_id != 0) {

            update_user_meta( $user_id, '_fitbit_access_token', $access_token);
            update_user_meta( $user_id, '_fitbit_refresh_token', $refresh_token);
            update_user_meta( $user_id, '_fitbit_user_id', $fitbit_user_id);
            update_user_meta( $user_id, '_fitbit_scope', $fitbit_scope);
            return true;
        }

    }

    return false;

}

/********************************************************
 *  Uses passed Fitbit Auth Code to generate an access token and renewal token, for first time.
 *  Stores all in User Meta
 *  Returns true on success, false on failure
 **********************************************************/
function mixit_fitbit_get_token_from_code($auth_code) {

    $client_id = get_option('mixit_fitbit_client_id');
    $client_secret = get_option('mixit_fitbit_client_secret');

    $post_url = 'https://api.fitbit.com/oauth2/token';
    $body = array(
        'code' => $auth_code,
        'grant_type'  => 'authorization_code',
        'client_id'    => $client_id,
    );
 
    $request  = new WP_Http();

    $headers = array(
        'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret )
    );
    
    $response = $request->post( $post_url, array('headers' =>$headers, 'body' => $body ) );
    $response_code = wp_remote_retrieve_response_code( $response );

    if($response_code == 200) {
        $body = json_decode(wp_remote_retrieve_body($response));
        $access_token = $body->access_token;
        $refresh_token = $body->refresh_token;
        $fitbit_user_id = $body->user_id;
        $fitbit_scope = $body->scope;

        $user_id = get_current_user_id();

        if (!empty($access_token)&& !empty($refresh_token) && !empty($fitbit_user_id) && !empty($fitbit_scope) && $user_id != 0) {

            update_user_meta( $user_id, '_fitbit_access_token', $access_token);
            update_user_meta( $user_id, '_fitbit_refresh_token', $refresh_token);
            update_user_meta( $user_id, '_fitbit_user_id', $fitbit_user_id);
            update_user_meta( $user_id, '_fitbit_scope', $fitbit_scope);
            return true;
        }

    } 

    return false;
}