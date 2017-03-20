<?php

// see https://codex.wordpress.org/Creating_Options_Pages
// and http://tutorialzine.com/2012/11/option-panel-wordpress-plugin-settings-api/

include MAUTIC_PATH . '/vendor/autoload.php';

class MauticSync {

    protected $settings = array(
        'mautic_url' => '', // set on admin screen
        'mautic_user' => '', // set on admin screen
        'mautic_password' => '', // set on admin screen
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
        $settings = $this->get_settings();
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
                            value="<?php echo $options['mautic_user']; ?>" style="width: 30em;" /><br/>
                            <span class="description">Username of user access to the Mautic API.</span>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Mautic Password</th>
                        <td><input type="text" id="mautic-password" name="mautic-password"
                            value="<?php echo $options['mautic_password']; ?>" style="width: 30em;" /><br/>
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

    // load saved options, if any
    public function get_settings() {
        foreach ($this->settings as $option => $default) {
            $this->settings[$option] = ($saved = get_option($option)) ? $saved : $default;
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
            foreach ($this->settings as $option => $value) {
                $this->logger("updating $option to $value");
                update_option($option, $value);
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

        $this->logger("saving settings");

        // generate the response
        header( "Content-Type: application/json" );
        $this->response(array('success'=> $this->save_settings()));
        // IMPORTANT: don't forget to "exit"
        exit;
    }

    // testing authentication against Mautic URL
    public function authenticate() {
        $this->get_settings();
        //$this->logger('testing auth with these details: '.print_r($this->settings, true));

        $settings = array(
            'baseUrl' => $this->settings['mautic_url'],       // Base URL of the Mautic instance
            'User' => $this->settings['mautic_user'],       // login username of Mautic user with API access
            'Password' => $this->settings['mautic_password'],       // Mautic user password
        );

        if (1) {  // test authentication
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
