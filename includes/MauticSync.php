<?php

// see https://codex.wordpress.org/Creating_Options_Pages
// and http://tutorialzine.com/2012/11/option-panel-wordpress-plugin-settings-api/

class MauticSync {

    protected $option_name = 'mautic-settings-group';

    protected $data = array(
        'mautic_api_url' => 'Your Mautic API URL (without /api)',
        'mautic_api_public_key' => 'Mautic API Public Key',
        'mautic_api_secret_key' => 'Mautic API Secret Key'
    );

    public function __construct() {
        add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'MAUTIC_create_menu' );

        // create this object
        //add_action('init', array($this, 'init'));

        // These hooks will handle AJAX interactions. We need to handle
        // ajax requests from both logged in users and anonymous ones:
        add_action('wp_ajax_nopriv_tz_ajax', array($this, 'ajax'));
        add_action('wp_ajax_tz_ajax', array($this, 'ajax'));

        // Listen for the activate event
        register_activation_hook(MAUTIC_FILE, array($this, 'activate'));

        // Deactivation plugin
        register_deactivation_hook(MAUTIC_FILE, array($this, 'deactivate'));
    }

    public function activate() {
        update_option($this->option_name, $this->data);
    }

    public function deactivate() {
        delete_option($this->option_name);
    }

    public function init() {
    	// This will show the stylesheet in wp_head() in the app/index.php file
        wp_enqueue_style('stylesheet', plugins_url('MauticSync/app/assets/css/styles.css'));

		// This will show the scripts in the footer
        //wp_deregister_script('jquery');
        //wp_enqueue_script('jquery', 'http://code.jquery.com/jquery-1.8.2.min.js', array(), false, true);
        wp_enqueue_script('script', plugins_url('MauticSync/app/assets/js/script.js'), array('jquery'), false, true);

        require MAUTIC_FILE . '/app/index.php';
        exit;
    }

    // White list our options using the Settings API
    public function admin_init() {
        register_setting('MAUTIC_sync_options', $this->option_name, array($this, 'validate'));
        echo "testing!\n";
    }

    // Add entry in the settings menu
    public function add_page() {
        add_options_page('Mautic Synchronisation Settings', 'Mautic Settings', 'manage_options', 'MAUTIC_options', array($this, 'options_do_page'));
    }

    // Print the menu page itself
    public function options_do_page() {
        $options = get_option($this->option_name);
        ?>
        <div class="wrap">
            <h2>Mautic Synchronisation Settings</h2>
            <form method="post" action="options.php">
                <?php settings_fields('MAUTIC_sync_options'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Mautic API address (URL)</th>
                        <td><input type="text" name="mautic_api_url" value="<?php
                            echo esc_attr( get_option('mautic_api_url') ); ?>" /></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Mautic API Public Key</th>
                        <td><input type="text" name="mautic_api_public_key" value="<?php
                            echo esc_attr( get_option('mautic_api_public_key') ); ?>" /></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Mautic API Secret Key</th>
                        <td><input type="text" name="mautic_api_secret_key" value="<?php
                            echo esc_attr( get_option('mautic_api_secret_key') ); ?>" /></td>
                    </tr>

                </table>
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
                </p>
            </form>
        </div>
        <?php
    }

    // Make sure the options are valid!
    public function validate($input) {

        $valid = array();
        $valid['mautic_api_url'] = sanitize_text_field($input['mautic_api_url']);
        $valid['mautic_api_public_key'] = sanitize_text_field($input['mautic_api_public_key']);
        $valid['mautic_api_secret_key'] = sanitize_text_field($input['mautic_api_secret_key']);

        if (strlen($valid['mautic_api_url']) == 0) {
            add_settings_error(
                    'MAUTIC_url', 					// setting title
                    'MAUTIC_texterror',			// error ID
                    'Please enter a valid Mautic API URL',		// error message
                    'error'							// type of message
            );
			# Set it to the default value
			$valid['mautic_api_url'] = $this->data['mautic_api_url'];
        }

        return $valid;
    }

    // This method is called when an
    // AJAX request is made to the plugin
    public function ajax() {
        $id = -1;
        $data = '';
        $verb = '';

        $response = array();

        if (isset($_POST['verb'])) {
            $verb = $_POST['verb'];
        }

        if (isset($_POST['id'])) {
            $id = (int) $_POST['id'];
        }

        if (isset($_POST['data'])) {
            $data = wp_strip_all_tags($_POST['data']);
        }

        $post = null;

        switch ($verb) {
            case 'save':
            break;

            case 'delete':
            break;
        }

        // Print the response as json and exit
        header("Content-type: application/json");
        die(json_encode($response));
    }
}
