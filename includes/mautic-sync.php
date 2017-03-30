<?php

include_once MAUTIC_PATH . '/includes/functions.php';
include_once MAUTIC_PATH . '/includes/mautic-base.php';
include_once MAUTIC_PATH . '/includes/mautic-auth.php';
include_once MAUTIC_PATH . '/includes/mautic-client.php';

class MauticSync extends MauticBase {

    protected static $instance = NULL; // this instance
    protected $auth; // Auth object that allows access to the Mautic API
    protected $mautic; // Mautic API client object

    // register stuff when constructing this object instance
    public function __construct() {
        $this->logger('in construct');
    }

    // returns an instance of this class if called, instantiating if necessary
    public static function get_instance() {
        NULL === self::$instance and self::$instance = new self;
        return self::$instance;
    }

    // do smart stuff when this object is instantiated.
    public function init() {
        $this->logger('in init');
        // also call the admin_init
        add_action('admin_init', array($this, 'admin_init'));
        // Deactivation plugin
        register_deactivation_hook(MAUTIC_FILE, array($this, 'deactivate'));        // This will show the stylesheet in wp_head() in the app/index.php file
        wp_enqueue_style('stylesheet', MAUTIC_URL.'app/css/styles.css');
        // registering for use elsewhere
        wp_register_script(
            'jquery-validate',
            'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.15.0/jquery.validate.js',
            array('jquery'), true);
        // if we're on the Mautic page, add our CSS...
        if (preg_match('~\/' . preg_quote(MAUTIC_URL) . '\/?$~', $_SERVER['REQUEST_URI'])) {
    		    // This will show the scripts in the footer
            wp_enqueue_script('script', MAUTIC_URL.'app/js/script.js', array('jquery'), false, true);
            require MAUTIC_PATH . 'app/index.php';
            exit;
        }

        //$this->logger('in MauticSync init...');
        // create this object's menu items
        add_action('network_admin_menu', array($this, 'add_pages'));

        // create other necessary objects
        $this->auth = new MauticAuth();
        $this->mautic = new MauticClient($this->auth);
    }

    // Add settings menu entry and various other sub pages
    public function add_pages() {
        $this->logger('in MauticSync->add_pages');
        // no op right now....
        add_menu_page('Mautic Synchronisation', 'Mautic Sync',
            'manage_options', 'mautic_sync', array($this, 'ajax_page'));
    }

    // White list our options using the Settings API
    public function admin_init() {
        $this->logger('in MauticSync->admin_init');
        wp_enqueue_script( 'jquery-validate');
        // embed the javascript file that makes the AJAX request
        wp_enqueue_script( 'mautic-ajax-request', MAUTIC_URL.'app/js/ajax.js', array(
            'jquery',
            'jquery-form'
        ));
        // declare the URL to the file that handles the AJAX request
        // (wp-admin/admin-ajax.php)
        wp_localize_script( 'mautic-ajax-request', 'mautic_sync_ajax', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'submit_nonce' => wp_create_nonce( 'mautic-submit-nonse'),
            'auth_nonce' => wp_create_nonce( 'mautic-auth-nonse'),
        ));
    }

    // Print the menu page itself
    public function ajax_page() {
        $this->logger('ajax_page');
        $wp_stats = wp_get_stats();
        $m_stats = $this->auth->get_stats();
        ?>
        <div class="wrap" id="mautic_sync_ajax">
            <h2>Mautic Synchronisation</h2>
            <p>The purpose of this plugin is to allow the synchronisation
            between WordPress users on this site and Mautic "contacts". </p>
            <p>Also, because this is a WordPress Multisite instance, we can
            have multiple sub-sites (called "networks") to which users can
            belong. We represent these in Mautic with "segments", and this
            plugin allows you to keep them synchronised.</p>
            <table class="sync-table">
                <tr valign="top">
                    <th scope="row">WordPress Statistics</th>
                    <td><p>This instance has</p>
                        <ul>
                            <li><?php print($wp_stats['num_users']); ?> Users</li>
                            <li><?php print($wp_stats['num_networks']); ?> Networks</li>
                        </ul>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Mautic Statistics</th>
                    <td><p>This instance has</p>
                        <ul>
                            <li><?php print($m_stats['num_contacts']); ?> Contacts</li>
                            <li><?php print($m_stats['num_segments']); ?> Segments</li>
                        </ul>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Sync Stats</th>
                    <td>
                        <p>Matching WP Users->Mautic Contacts</p>
                        <p>Matching WP Networks->Mautic Segments</p>
                        <p>Unmatched WP Users, Networks</p>
                        <p>Unmatched Mautic Contacts, Segments</p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    // clean up if this plugin is deactivated.
    public function deactivate() {
        // nuke everything created by other objects
        $this->auth->deactivate;
    }
}
