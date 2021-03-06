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

    protected $auth; // an auth object

    // register stuff when constructing this object instance
    public function __construct() {
        $this->log('in MauticAuth->__construct');
        // create this object
        add_action('init', array($this, 'init'));
        // Admin sub-menu
        //$this->log('_GET: '.print_r($_GET,true));
        //$this->log('_POST: '.print_r($_POST,true));
        // make sure we initialise the auth if either of these
        // cases is true - the first is when someone goes to the page
        // the second is if they submit the Ajax Submit...
        if ((isset($_GET['page']) && $_GET['page'] == MAUTIC_ADMIN_SLUG) || (isset( $_POST['action']) && $_POST['action'] == 'mautic_submit')) {
            $this->log('on '.MAUTIC_ADMIN_SLUG);
            add_action('admin_init', array($this, 'auth_init'));
        } else {
            $this->log('not on '.MAUTIC_ADMIN_SLUG);
        }
        //$this->log('finished adding auth_init');
    }

    // do smart stuff when this object is instantiated.
    public function init() {
        // This will show the stylesheet in wp_head() in the app/index.php file
        wp_enqueue_style('stylesheet', MAUTIC_URL.'app/css/styles.css');
        // registering for use elsewhere
        wp_register_script(
            'jquery-validate',
            'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.15.0/jquery.validate.js',
            array('jquery'), true);
        // create this object's menu items
        add_action('network_admin_menu', array($this, 'add_pages'));
        // Deactivation plugin
        //register_deactivation_hook(MAUTIC_FILE, array($this, 'deactivate'));
    }

    // Add settings menu entry and various other sub pages
    public function add_pages() {
        $this->log('in add_pages');
        add_submenu_page(MAUTIC_SLUG, MAUTIC_ADMIN_TITLE,
            MAUTIC_ADMIN_MENU, 'manage_options', MAUTIC_ADMIN_SLUG,
            array($this, 'ajax_auth_page'));
    }

    // White list our options using the Settings API
    public function auth_init() {
        $this->log('in MauticAuth->auth_init');

        $this->log('on auth page: '.MAUTIC_ADMIN_SLUG);
        // this is a dependence of the auth-ajax.js script
        wp_enqueue_script( 'jquery-validate');
        wp_enqueue_script( 'mautic-auth-script', MAUTIC_URL.'app/js/auth-ajax.js', array(
            'jquery',
            'jquery-form',
            'jquery-validate'
        ));
        // declare the URL to the AJAX request handler (wp-admin/admin-ajax.php)
        wp_localize_script( 'mautic-auth-script', 'mautic_auth', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'submit_nonce' => wp_create_nonce( 'mautic-submit-nonse'),
            'auth_nonce' => wp_create_nonce( 'mautic-auth-nonse'),
        ));
        // note, have to use wp_ajax_[action] - see
        // https://codex.wordpress.org/AJAX_in_Plugins
        add_action( 'wp_ajax_mautic_submit', array($this, 'ajax_submit'));
        $this->log('added mautic_submit action');
    }

    // Print the menu page itself
    public function ajax_auth_page() {
        $this->log('on auth page: '. $GET['page']);
        // load the relevant scripts.
        //$this->auth_init();
        $settings = $this->get_settings();
        if ($this->has_auth_details()) {
            $this->log('has valid auth details');
        } else {
            $this->log('need to get valid auth details');
        }
        $nonce_submit = wp_create_nonce('mautic-submit');
        //$this->log('creating form');
        ?>
        <div class="wrap" id="mautic-auth">
            <h2>Mautic Synchronisation Settings</h2>
            <!-- <form method="post" action="options.php"> -->
            <form method="post" action="" id="mautic-auth-form">
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
                    <!--<input type="hidden" id="mautic-submit-nonce" value="<?php echo $nonce_submit; ?>" />-->
                </p>
                <p id="mautic-userstatus" style="color: red">&nbsp;</p>
            </form>
        </div>
        <?php
        $this->log('rendered auth form');
    }

    // called when the ajax form is successfully submitted
    public function ajax_submit() {
        $this->log('in ajax_submit: '.print_r($_POST, true));
        // check if the submitted nonce matches the generated nonce created in the auth_init functionality
        if ( ! wp_verify_nonce( $_POST['nonce-submit'], 'mautic-submit-nonse') ) {
            die ("Busted - someone's trying something funny in submit!");
        }

        $this->log("saving settings");

        // generate the response
        header( "Content-Type: application/json" );
        $this->response(array('success'=> $this->save_settings()));
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
            $url = sanitize_text_field($_POST['url']);
            if (!strpos($url, MAUTIC_API_ENDPOINT)) {
                $url = $url.'/'.MAUTIC_API_ENDPOINT;
            }
            $this->settings['mautic_url'] = $url;
            $this->settings['mautic_user'] = sanitize_text_field($_POST['user']);
            $this->settings['mautic_password'] = sanitize_text_field($_POST['password']);
            //$this->log("data array: ".print_r($this->settings, true));
            // save values
            foreach ($this->settings as $setting => $value) {
            //    $this->log("updating $setting to $value");
                update_option($setting, $value);
            }
            if ($this->test_auth()) {
                $this->log('successful auth!');
                $this->response(array(
                    'success' => true,
                    'message' => 'Credentials successfully tested and saved.'));
            } else {
                $this->log('auth failed!');
                //wp_send_json_error('Authentication failed! One or both of your credentials are wrong, end point is incorrect, or it is not available.');
                $this->response(array(
                    //'error' => true,
                    'success' => true,
                    'message' => 'Settings saved, but authentication failed! One or both of your credentials are wrong, or the API is incorrect or not available.'));
            }
        }
        return true;
    }

    // test authentication
    public function test_auth() {
        $settings = $this->get_auth_details();

        $this->log('settings we are using to Auth: '.print_r($settings, TRUE));
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
    }

    // clean up if this plugin is deactivated.
    public function deactivate() {
        // clean up our options, specified in $this->settings
        foreach ($this->settings as $setting => $value) {
            delete_option($setting);
        }
    }

    // return the Auth details in a form that can be passed to the
    // Mautic API Authentication object
    public function get_auth_details() {
        if (!$this->has_auth_details()) {
            $this->get_settings();
        }
        if ($this->settings['mautic_url'] != '' &&
            $this->settings['mautic_user'] != '' &&
            $this->settings['mautic_password'] != '') {
            $settings = array(
                'userName' => $this->settings['mautic_user'], // username of Mautic user with API access
                'password' => $this->settings['mautic_password'], // Mautic user password
                'apiUrl' => $this->settings['mautic_url'], // Mautic API URL
                'AuthMethod' => 'BasicAuth' // the auth method
            );
            return $settings;
        }
        return false;
    }

    // for convenience in working with Mautic's API, get the URL by itself.
    public function get_url() {
        if (!$this->has_auth_details()) {
            $this->get_settings();
        }
        return $this->settings['mautic_url'];
    }

    // for convenience, return the base Mautic URL
    public function get_baseurl() {
        $parts = parse_url($this->get_url());
        $this->log('url: '.print_r($parts, true));
        return $parts['scheme'].'://'.$parts['host'];
    }

    // for convenience, return the auth object
    public function get_auth() {
        // is this a valid AuthInterface object?
        if ($this->auth instanceof AuthInterface) {
            $this->log('checking whether this is an AuthInterface');
            return $this->auth;
        }
        $this->log('NOT an instance of AuthInterface');
        // otherwise it's not set yet.
        return false;
    }

    // are the auth details set and held by the object
    public function has_auth_details() {
        if ($this->settings['mautic_url'] != '' &&
            $this->settings['mautic_user'] != '' &&
            $this->settings['mautic_password'] != '') {
            return true;
        }
        return false;
    }
}
