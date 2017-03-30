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
            $this->logger('successfully tested auth');
            $this->auth = $auth;
        } else {
            $this->logger('unsuccessful auth test');
            //throw new Exception('Mautic authentication details invalid!');
        }
    }

}
