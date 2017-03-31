<?php

include MAUTIC_PATH . '/vendor/autoload.php';
include_once MAUTIC_PATH . '/includes/functions.php';
include_once MAUTIC_PATH . '/includes/mautic-base.php';

use Mautic\MauticApi;

class MauticClient extends MauticBase {

    static $auth; // need to be able to access API endpoint

    public function __construct($auth) {
        // make sure auth is valid
        if ($auth->test_auth()) {
            $this->log('successfully tested auth');
            // accept this valid auth
            $this->auth = $auth;
        } else {
            $this->log('unsuccessful auth test');
            //throw new Exception('Mautic authentication details invalid!');
            // get rid of the bung Auth object
            $this->auth = NULL;
        }
    }

/*    // test authentication
    public function test_auth($auth) {
        $settings = $auth->get_auth_details();

        $this->log('settings we are sending to MauticAuth: '.print_r($settings, TRUE));
        // see https://github.com/mautic/api-library
        session_start();  // initiate a session

        $initAuth = new ApiAuth();
        $auth = $initAuth->newAuth($settings, $settings['AuthMethod']);

        $this->log('result auth: '.print_r($auth, true));
        // Get a Contact context
        $api = new MauticApi();
        $contactApi = $api->newApi('contacts', $auth, $settings['apiUrl']);
        // Get Contact list
        $results = $contactApi->getList();
        //$this->log("contacts json: ".print_r($results, true));
        if ($results['error']) {
            $this->log("We're in error: ".print_r($results['error'], true));
            return false;
        }
        if ($results['total']) {
            $this->log('success: total contacts = '.$results['total']);
            $this->auth = $auth;
        }
        return true;
    }*/


    public function get_stats() {
        $stats = array(
            'num_contacts' => 23,
            'num_segments' => 6
        );



        return $stats;
    }

}
