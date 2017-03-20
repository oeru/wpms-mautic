<?php 

class MauticAuth {

    protected $settings = array(
        'mautic_url' => '', // set on admin screen
        'mautic_public_key' => '', // set on admin screen
        'mautic_secret_key' => '', // set on admin screen
        'mautic_auth_info' => false, // set to true with valid settings entered
        // post authentication
        'mautic_version' => 'OAuth2', // default option
        'mautic_access_token' => '', // set by authentication
        'mautic_access_token_secret' => '', // set by authentication
        'mautic_access_token_expires' => '', // set by authentication
        'mautic_refresh_token' => '', // set by authentication
    );

    

}
