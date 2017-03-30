<?php

// see https://codex.wordpress.org/Creating_Options_Pages
// and http://tutorialzine.com/2012/11/option-panel-wordpress-plugin-settings-api/

include MAUTIC_PATH . '/vendor/autoload.php';
include_once MAUTIC_PATH . '/includes/functions.php';
include_once MAUTIC_PATH . '/includes/mautic-base.php';
use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;

class MauticAuth extends MauticBase {

    protected $settings = array(
        'mautic_url' => '', // set on admin screen
        'mautic_user' => '', // set on admin screen
        'mautic_password' => '', // set on admin screen
    );

    // register stuff when constructing this object instance
    public function __construct() {
        $this->logger('in MauticAuth->__construct');
        // create this object
        add_action('init', array($this, 'init'));
        // Admin sub-menu
        add_action('admin_init', array($this, 'admin_init'));
        // Deactivation plugin
        register_deactivation_hook(MAUTIC_FILE, array($this, 'deactivate'));
    }

    // do smart stuff when this object is instantiated.
    public function init() {
        $this->logger('in MauticAuth->init');
        // create this object's menu items
        add_action('network_admin_menu',
            array($this, 'add_pages'));
    }

    // Add settings menu entry and various other sub pages
    public function add_pages() {
        $this->logger('in MauticAuth->add_pages');
        add_submenu_page('mautic_sync', 'Mautic Synchronisation Settings',
            'Mautic Settings', 'manage_options', 'mautic_settings',
            array($this, 'ajax_auth_page'));
    }

    // White list our options using the Settings API
    public function admin_init() {
        // declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
        wp_localize_script( 'mautic-ajax-request', 'mautic_sync_ajax', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'submit_nonce' => wp_create_nonce( 'mautic-submit-nonse'),
            'auth_nonce' => wp_create_nonce( 'mautic-auth-nonse'),
        ));
        add_action( 'wp_ajax_mautic_submit', array($this, 'ajax_submit'));
        //add_action( 'wp_ajax_mautic_auth', array($this, 'ajax_auth'));
    }

    // Print the menu page itself
    public function ajax_auth_page() {
        $settings = $this->get_settings();
        if ($this->has_auth_details()) {
            $this->logger('has valid auth details');
        } else {
            $this->logger('need to get valid auth details');
        }
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
                            value="<?php echo $settings['mautic_url']; ?>" style="width: 30em;" /><br/>
                            <span class="description">A valid web address for your Mautic instance including schema (http:// or https://). The path "/api" will added automatically.</span>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Mautic User</th>
                        <td><input type="text" id="mautic-user" name="mautic-user"
                            value="<?php echo $settings['mautic_user']; ?>" style="width: 30em;" /><br/>
                            <span class="description">Username of a user who can access the Mautic API.</span>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Mautic Password</th>
                        <td><input type="text" id="mautic-password" name="mautic-password"
                            value="<?php echo $settings['mautic_password']; ?>" style="width: 30em;" /><br/>
                            <span class="description">Password for the above user: numbers, letters (mixed capitalisation), and special characters.</span>
                        </td>
                    </tr>

                </table>
                <p class="submit">
                    <input type="submit" id="mautic-submit" class="button-primary" value="Save Changes" />
                    <input type="button" id="mautic-auth" class="button-secondary" value="Authenticate" />
                    <input type="hidden" id="mautic-submit-nonce" value="<?php echo $nonce_submit; ?>" />
                </p>
                <p id="mautic-userstatus" style="color: red">&nbsp;</p>
            </form>
        </div>
        <?php
    }

    // called when the ajax form is successfully submitted
    public function ajax_submit() {
        $this->logger('in ajax_submit: '.print_r($_POST, true));
        // check if the submitted nonce matches the generated nonce created in the auth_init functionality
        if ( ! wp_verify_nonce( $_POST['nonce-submit'], 'mautic-submit-nonse') ) {
            die ("Busted - someone's trying something funny in submit!"); }

        $this->logger("saving settings");

        // generate the response
        header( "Content-Type: application/json" );
        response(array('success'=> $this->save_settings()));
        // IMPORTANT: don't forget to "exit"
        exit;
    }

    // load saved options, if any
    public function get_settings() {
        foreach ($this->settings as $setting => $default) {
            $this->settings[$setting] = ($saved = get_option($setting)) ? $saved : $default;
        }
        return $this->settings;
    }

    // the ajax form should only ever return valid options
    public function save_settings() {
        if ($_POST && isset($_POST['url']) && isset($_POST['user']) && isset($_POST['password'])) {
            // grab saved values from Ajax form
            $this->settings['mautic_url'] = sanitize_text_field($_POST['url']);
            $this->settings['mautic_user'] = sanitize_text_field($_POST['user']);
            $this->settings['mautic_password'] = sanitize_text_field($_POST['password']);
            $this->logger("data array: ".print_r($this->settings, true));
            // save values
            foreach ($this->settings as $setting => $value) {
                $this->logger("updating $setting to $value");
                update_option($setting, $value);
            }
            return true;
        }
        return false;
    }

    // test authentication
    public function test_auth() {
        if (!$this->has_auth_details()){
            $this->get_settings();
        }
        $this->logger('testing auth with these details: '.print_r($this->settings, true));
        $settings = array(
            'userName' => $this->settings['mautic_user'], // username of Mautic user with API access
            'password' => $this->settings['mautic_password'] // Mautic user password
        );

        // see https://github.com/mautic/api-library
        session_start();  // initiate a session

        $initAuth = new ApiAuth();
        $auth = $initAuth->newAuth($settings, 'BasicAuth', $this->settings['mautic_url']);

        $this->logger('result auth: '.print_r($auth, true));
    }

    // testing authentication against Mautic URL
    public function authenticate() {
        if (!$this->has_auth_details()){
            $this->get_settings();
        }
        $this->logger('testing auth with these details: '.print_r($this->settings, true));

        $settings = array(
            'userName' => $this->settings['mautic_user'], // username of Mautic user with API access
            'password' => $this->settings['mautic_password'] // Mautic user password
        );

        // see https://github.com/mautic/api-library
        session_start();  // initiate a session

        $initAuth = new ApiAuth();
        $auth = $initAuth->newAuth($settings, 'BasicAuth', $this->settings['mautic_url']);

        $this->logger('result auth: '.print_r($auth, true));

        $api = new MauticApi();

        $contactApi = $api->newApi('contacts', $auth, $this->settings['mautic_url']);

        $this->logger('contact API: '. print_r($contactApi));

        $response = $contactApi->get(53); //get the first contact...

        $this->logger('response: '. print_r($response));

        $contact = $response[$contactApi->itemName()];

        $this->logger('first contact: '. print_r($contact));

        if (!isset($response['error'])) {  // test authentication
            $this->logger("no error code set...");
            return true;
        } else {
            $this->logger($response['error']['code'] . ": " .$response['error']['message']);
        }
        return false;
    }

    // clean up if this plugin is deactivated.
    public function deactivate() {
        // clean up our options, specified in $this->settings
        foreach ($this->settings as $setting => $value) {
            delete_option($setting);
        }
    }

/*    // for testing authentication with the provided details
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
*/

    // are the auth details set and held by the object
    private function has_auth_details() {
        if ($this->settings['mautic_url'] != '' &&
            $this->settings['mautic_user'] != '' &&
            $this->settings['mautic_password'] != '') {
            return true;
        }
        return false;
    }

    public function get_stats() {
        $stats = array(
            'num_contacts' => 23,
            'num_segments' => 6
        );
        return $stats;
    }
}
