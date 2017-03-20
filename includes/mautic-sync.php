<?php

// see https://codex.wordpress.org/Creating_Options_Pages
// and http://tutorialzine.com/2012/11/option-panel-wordpress-plugin-settings-api/

include MAUTIC_PATH . '/vendor/autoload.php';
use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;

class MauticSync {

    protected $settings = array(
        'mautic_url' => '', // set on admin screen
        'mautic_public_key' => '', // set on admin screen
        'mautic_secret_key' => '', // set on admin screen
        'mautic_auth_info' => false, // set to true with valid settings entered
        'mautic_callback_url' => '', // the same URL as specified in the API credentials
        'mautic_version' => 'OAuth2', // default option
        // post authentication
        'mautic_access_token' => '', // set by authentication
        'mautic_access_token_secret' => '', // set by authentication
        'mautic_access_token_expires' => '', // set by authentication
        'mautic_refresh_token' => '', // set by authentication
    );

    // register stuff when constructing this object instance
    public function __construct() {
        // create this object
        add_action('init', array($this, 'init'));
        // Admin sub-menu
        add_action('admin_init', array($this, 'admin_init'));
        //add_action(is_multisite() ? 'network_admin_menu' : 'admin_menu', 'add_page');
        add_action('admin_menu',  array($this, 'add_page'));
        // Deactivation plugin
        register_deactivation_hook(MAUTIC_FILE, array($this, 'deactivate'));
    }


    // clean up if this plugin is deactivated.
    public function deactivate() {
        // clean up our options, specified in $this->settings
        foreach ($this->settings as $option => $value) {
            delete_option($option);
        }
    }

    // do smart stuff when this object is instantiated.
    public function init() {
        // This will show the stylesheet in wp_head() in the app/index.php file
        wp_enqueue_style('stylesheet', MAUTIC_URL.'app/assets/css/styles.css');
        // registering for use elsewhere
        wp_register_script('jquery-validate', 
            'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.15.0/jquery.validate.js',
            array('jquery'), true);
        // if we're on the Mautic page, add our CSS...
        if (preg_match('~\/' . preg_quote(MAUTIC_URL) . '\/?$~', $_SERVER['REQUEST_URI'])) {
    		    // This will show the scripts in the footer
            wp_enqueue_script('script', MAUTIC_URL.'app/assets/js/script.js', array('jquery'), false, true);
            require MAUTIC_PATH . 'app/index.php';
            exit;
        }
    }

    // White list our options using the Settings API
    public function admin_init() {
        wp_enqueue_script( 'jquery-validate');
        // embed the javascript file that makes the AJAX request
        wp_enqueue_script( 'mautic-ajax-request', MAUTIC_URL.'app/assets/js/ajax.js', array(
            'jquery',
            'jquery-form'
        ));
        // declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
        wp_localize_script( 'mautic-ajax-request', 'mautic_sync_ajax', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'submit_nonce' => wp_create_nonce( 'mautic-submit-nonse'),
            'auth_nonce' => wp_create_nonce( 'mautic-auth-nonse'),
        ));
        // if both logged in and not logged in users can send this AJAX request,
        // add both of these actions, otherwise add only the appropriate one
        //add_action( 'wp_ajax_nopriv_mautic_submit', 'ajax_submit' );
        add_action( 'wp_ajax_mautic_submit', array($this, 'ajax_submit'));
        add_action( 'wp_ajax_mautic_auth', array($this, 'ajax_auth'));
    }

    // Add entry in the settings menu
    public function add_page() {
        add_options_page('Mautic Synchronisation Settings', 'Mautic Settings',
            'manage_options', 'mautic_options', array($this, 'ajax_options_page'));
    }

    // Print the menu page itself
    public function ajax_options_page() {
        $options = $this->get_options();
        $nonce_submit= wp_create_nonce('mautic-submit');
        //$this->logger('creating form');
        ?>
        <div class="wrap" id="mautic_sync_ajax">
            <h2>Mautic Synchronisation Settings</h2>
            <!-- <form method="post" action="options.php"> -->
            <form method="post" action="" id="mautic-sync-form">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Mautic API Address (URL)</th>
                        <td><input type="text" id="mautic-url" name="mautic-url"
                            value="<?php echo $options['mautic_url']; ?>" style="width: 30em;" /><br/>
                            <span class="description">A valid web address for your Mautic instance including schema (http:// or https://) and path, e.g. /api.</span>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Mautic API Public Key</th>
                        <td><input type="text" id="mautic-public-key" name="mautic-public-key"
                            value="<?php echo $options['mautic_public_key']; ?>" style="width: 30em;" /><br/>
                            <span class="description">Should be a string of numbers and letters <?php echo MAUTIC_PUB_KEY_SIZE ?> characters long.</span>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Mautic API Secret Key</th>
                        <td><input type="text" id="mautic-secret-key" name="mautic-secret-key"
                            value="<?php echo $options['mautic_secret_key']; ?>" style="width: 30em;" /><br/>
                            <span class="description">A string of numbers and letters <?php echo MAUTIC_PRIV_KEY_SIZE ?> characters long.</span>
                        </td>
                    </tr>
 
                    <tr valign="top">
                        <th scope="row">Your App Callback URL</th>
                        <td><input type="text" id="mautic-callback-url" name="mautic-callback-url"
                            value="<?php echo $options['mautic_callback_url']; ?>" style="width: 30em;" /><br/>
                            <span class="description">Your app's URL, visible to the Mautic API server, including schema (http:// or https://) and path.</span>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" id="mautic-submit" class="button-primary" value="Save Changes" />
                    <input type="button" id="mautic-auth" class="button-secondary" value="Authenticate" />
                    <input type="hidden" id="mautic-submit-nonce" value="<?php echo $nonce_submit; ?>" />
                    <input type="hidden" id="mautic-auth-info" value="<?php echo $options['mautic_auth_info']; ?>" />
                </p>
                <p id="mautic-userstatus" style="color: red">&nbsp;</p>
            </form>
        </div>
        <?php
    }

    // load saved options, if any
    public function get_options() {
        foreach ($this->settings as $option => $default) {
            $this->settings[$option] = ($saved = get_option($option)) ? $saved : $default;
        }
        return $this->settings;
    }

    // the ajax form should only ever return valid options
    public function save_options() {
        if ($_POST && isset($_POST['url']) && isset($_POST['public_key']) && isset($_POST['secret_key'])) {
            // grab saved values from Ajax form
            $this->settings['mautic_url'] = sanitize_text_field($_POST['url']);
            $this->settings['mautic_public_key'] = sanitize_text_field($_POST['public_key']);
            $this->settings['mautic_secret_key'] = sanitize_text_field($_POST['secret_key']);
            $this->settings['mautic_callback_url'] = sanitize_text_field($_POST['callback_url']);
            // if these values are validated, then we can set auth_info to true
            $this->settings['mautic_auth_info'] = true;
            $this->logger("data array: ".print_r($this->settings, true));
            // save values
            foreach ($this->settings as $option => $value) {
                $this->logger("updating $option to $value");
                update_option($option, $value);
                /*if (!update_option($option, $value)) {
                    return false;
                }*/
            }
            return true;
        }
        return false;
    }

    // called when the ajax form is successfully submitted
    public function ajax_submit() {
        $this->logger('in ajax_submit: '.print_r($_POST, true));
        // check if the submitted nonce matches the generated nonce created in the auth_init functionality
        if ( ! wp_verify_nonce( $_POST['nonce-submit'], 'mautic-submit-nonse') ) { 
            die ("Busted in submit!"); }
        
        $this->logger("saving options");

        // generate the response
        header( "Content-Type: application/json" );
        $this->response(array('success'=> $this->save_options()));
        // IMPORTANT: don't forget to "exit"
        exit;
    }

    // testing authentication against Mautic URL 
    public function authenticate() {
        $this->get_options();  
        //$this->logger('testing auth with these details: '.print_r($this->settings, true));
        
        session_name("oauthtester");
        session_start();
        
        $settings = array(
            'baseUrl' => $this->settings['mautic_url'],       // Base URL of the Mautic instance
            'version' => $this->settings['mautic_version'], // Version of the OAuth can be OAuth2 or OAuth1a. OAuth2 is the default value.
            'clientKey' => $this->settings['mautic_public_key'],       // Client/Consumer key from Mautic
            'clientSecret' => $this->settings['mautic_secret_key'],       // Client/Consumer secret key from Mautic
            'callback' => $this->settings['mautic_callback_url']        // Redirect URI/Callback URI for this script
        );

        if (isset($this->settings['mautic_token']) && isset($this->settings['mautic_token_secret'])) {
            $settings['accessToken'] = $this->settings['mautic_token'];
            $settings['accessTokenSecret'] = $this->settings['mautic_token_secret'];
        }

        // Initiate the auth object
        $auth = ApiAuth::initiate($settings);

        //$this->logger('$auth (1) '. print_r($auth, true));
      
        if (isset($_SESSION['accessTokenData'])) { //todo read from more permanent
            $auth->setAccessTokenDetails(json_decode($_SESSION['accessTokenData'], true));
        }
        
        $this->logger('$auth (2) '. print_r($auth, true));

        if ($auth->validateAccessToken()){
            //echo '222<br>';
            $this->logger('in validateAccessToken');
            $accessTokenData = $auth->getAccessTokenData();
            $_SESSION['accessTokenData'] = json_encode($accessTokenData); //todo save more permanently

            if ($auth->accessTokenUpdated()) {
                //echo '333<br>';
                $accessTokenData = $auth->getAccessTokenData();

                //store access token data however you want

            }

            // testing stuff
            $leadApi = MauticApi::getContext("leads", $auth, $baseUrl .'/api/');
            $leads = $leadApi->getList();
            $this->logger( '$leads = ' . print_r($leads, true));

            return true;
        }          
 
        return false;
    }

    // for testing authentication with the provided details
    public function ajax_auth() {
        $this->logger('in ajax_auth: '.print_r($_POST, true));
        // check if the submitted nonce matches the generated nonce created in the auth_init functionality
        if ( ! wp_verify_nonce( $_POST['nonce-auth'], 'mautic-auth-nonse') ) {
            die ("Busted in auth!"); }

        $this->logger("testing authentication");

        // generate the response
        header( "Content-Type: application/json" );
        $this->response(array('success'=> $this->authenticate()));
        // IMPORTANT: don't forget to "exit"
        exit;

    }

    // construct JSON responses to AJAX queries 
    private function response($a) {
        echo json_encode($a);
        die();
    }
  
    // log things to the web server log
    private function logger($message) {
        if (MAUTIC_DEBUG) {
            error_log($message);
        }
    }
}
