<?php

include_once MAUTIC_PATH . '/includes/mautic-hooks.php';

class MauticSync extends MauticHooks {

    protected static $instance = NULL; // this instance
    //protected $auth; // Auth object that allows access to the Mautic API
    protected static $mautic; // Mautic API client object
    protected $tabs = array(
        'status' => 'Sync Status',
        'matched_sites' => 'Matched Sites',
        'unmatched_sites' => 'Unmatched Sites',
        'matched_users' => 'Matched Users',
        'unmatched_users' => 'Unmatched Users',
        'users_by_site' => 'Users By Site',
    );

    // register stuff when constructing this object instance
    public function __construct() {
        $this->log('in construct');
    }

    // returns an instance of this class if called, instantiating if necessary
    public static function get_instance() {
        NULL === self::$instance and self::$instance = new self();
        return self::$instance;
    }

    // The context is the situation in which this object is created.
    // Options:
    // 1. 'network' - the Network Admin interface - multi-site wide configuration
    // 2. 'site' - the per-Site interface - site-specific configuration
    // As a general rule, there's one context per top-level mautic-*.php file :)

    // the Network Context
    // Do smart stuff when this object is instantiated.
    public function network_init() {
        $this->log('in network_init');
        $this->log('network context');
        // create this object's menu items
        add_action('network_admin_menu', array($this, 'add_network_pages'));
        // also call the admin_init
        add_action('admin_init', array($this, 'admin_init'));
        // do the context independent stuff
        $this->common_init();
    }

    // The Site Context
    // Do smart stuff when this object is instantiated.
    public function site_init() {
        $this->log('in site_init');
        // add our updated links to the site nav links array via the filter
        add_filter('network_edit_site_nav_links', array($this, 'insert_site_nav_link'));
        // do the context independent stuff

        $this->common_init();
    }

    public function common_init() {
        // This will show the stylesheet in wp_head() in the app/index.php file
        wp_enqueue_style('stylesheet', MAUTIC_URL.'app/css/styles.css');
        // registering for use elsewhere
        wp_register_script(
            'jquery-validate',
            'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.15.0/jquery.validate.js',
            array('jquery'), true);
        // register all relevant hooks
        $this->register_hooks();
        // create other necessary objects
        $this->mautic = new MauticClient();
    }

    // White list our options using the Settings API
    public function admin_init() {
        $this->log('in MauticSync->admin_init');
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

    // Add settings menu entry and various other sub pages
    public function add_network_pages() {
        $this->log('in MauticSync->add_network_pages');
        // no op right now....
        add_menu_page(MAUTIC_TITLE, MAUTIC_MENU,
            'manage_options', MAUTIC_SLUG, array($this, 'sync_page'));
    }

    // Print the menu page itself, with tab navigation
    public function sync_page() {
        // above tabs

        // set up actions to undertake on each tab...
        $tab = (!empty($_GET['tab'])) ? esc_attr($_GET['tab']) : 'status';

        // render the tabs
        $this->sync_page_tabs($tab);

        $this->log('in sync_page');

        switch ($tab) {
            case 'status':
                $this->sync_stats_tab();
                break;
            case 'matched_sites':
                $this->matched_sites_tab();
                break;
            case 'unmatched_sites';
                $this->unmatched_sites_tab();
                break;
            case 'matched_users':
                $this->matched_user_tab();
                break;
            case 'unmatched_users':
                $this->unmatched_user_tab();
                break;
        }

        // below tabs
    }

    // Print the menu page itself
    public function sync_stats_tab() {
        $this->log('ajax_page');
        $wp_stats = wp_get_stats();
        $m_stats = $this->mautic->get_stats();
        $people = $this->get_people();
        $groups = $this->get_groups();
        ?>
        <div class="wrap" id="mautic-sync-status">
            <h2>Mautic Synchronisation</h2>
            <p>The purpose of this plugin is to allow the synchronisation
            between WordPress users on this site and Mautic "contacts". </p>
            <p>Also, because this is a WordPress Multisite instance, we can
            have multiple sub-sites (called "networks") to which users can
            belong. We represent these in Mautic with "segments", and this
            plugin allows you to keep them synchronised.</p>
            <table class="sync-table">
                <tr><th colspan=2 class="title">Mautic Sync Statistics</th>
                <tr>
                <tr>
                    <th scope="row">WordPress</th>
                    <td>
                        <p><strong><?php print($wp_stats['num_users']); ?></strong> Users</p>
                        <p><strong><?php print($wp_stats['num_networks']); ?></strong> Networks</p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Mautic</th>
                    <td>
                        <p><strong><?php print($m_stats['num_contacts']); ?></strong> Contacts</p>
                        <p><strong><?php print($m_stats['num_segments']); ?></strong> Segments</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Match Stats</th>
                    <td>
                        <p>Matched <strong><?php print($people['matches']); ?></strong> WP Users to corresponding Mautic Contacts</p>
                        <p>Matched <strong><?php print($groups['matches']); ?></strong> WP Networks to corresonding Mautic Segments</p>
                        <p>Unmatched: <strong><?php print($wp_stats['num_users']-$people['matches']); ?></strong> WP Users, <strong><?php print($wp_stats['num_networks']-$groups['matches']); ?></strong> Networks</p>
                        <p>Unmatched: <strong><?php print($m_stats['num_contacts']-$people['matches']); ?></strong> Mautic Contacts,  <strong><?php print($m_stats['num_segments']-$groups['matches']); ?></strong> Segments</p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    public function matched_sites_tab() {
        echo "<h2>matched_sites</h2>";
    }
    public function unmatched_sites_tab() {
        echo "<h2>unmatched_sites</h2>";
    }
    public function matched_user_tab() {
        echo "<h2>matched_users</h2>";
    }
    public function unmatched_user_tab() {
        echo "<h2>unmatched_users</h2>";
    }

    // render the tabs on the Sync page...
    public function sync_page_tabs($current = 'status') {
        $html = '<h2 class="nav-tab-wrapper">';
        foreach ($this->tabs as $tab => $name) {
            $class = ($tab == $current) ? 'nav-tab-active' : '';
            $html .= '<a class="nav-tab '.$class.'" href="?page='.
                MAUTIC_SLUG.'&tab='.$tab.'">'.$name.'</a>';
        }
        $html .= '</h2>';
        echo $html;
    }

    // compare details from WP and Mautic
    public function get_people() {
        $people = array('matches' => 0);
        // get person related stuff - WP users and Mautic Contacts
        // match them on email address
        //
        // from WP
        $wp_users = get_users();
        $this->log(count($wp_users).' user retrieved.');
        foreach ($wp_users as $user) {
            //$this->log('user: '. print_r($user->data, true));
            $email = strtolower($user->data->user_email);
            $people[$email]['wp'] = $user->data->ID;
            $people[$email]['user'] = $user->data;
        }
        // from Mautic
        $mautic_contacts = $this->mautic->get_contacts()['contacts'];
        $this->log(count($mautic_contacts).' contacts retrieved.');
        if (count($mautic_contacts)) {
            foreach ($mautic_contacts as $contact) {
                //$this->log('contact: '. print_r($contact, true));
                $email = strtolower($contact['fields']['core']['email']['value']);
                $people[$email]['mautic'] = $contact['id'];
                $people[$email]['contact'] = $contact['fields']['core'];
            }
        }
        // print the result
        foreach ($people as $email => $person) {
            if (isset($person['wp']) && isset($person['mautic'])) {
                $this->log('Person match: '.$email .' WP('.$person['wp'].'), Mautic('.$person['mautic'].')');
                $people['matches']++;
            }
        }
        // get group related stuff - WP networks and Mautic Segments
        // match them on name
        return $people;
    }
    // compare details from WP and Mautic
    public function get_groups() {
        $groups = array('matches' => 0);
        // get group related stuff - WP networks/sites and Mautic Segments
        // match them on email address
        //
        // from WP
        $wp_sites = get_sites();
        $this->log(count($wp_sites).' sites retrieved.');
        foreach ($wp_sites as $site) {
            //$this->log('site: '. print_r($site, true));
            $name = strtolower(substr($site->path,1,-1));
            //$this->log('site name: '. $name);
            $groups[$name]['wp'] = $site->blog_id;
            $groups[$name]['site'] = $site;
        }
        // from Mautic
        $mautic_segments = $this->mautic->get_segments()['lists'];
        //$this->log('segments: '.print_r($mautic_segments, true));
        //$this->log(count($mautic_segments).' segments retrieved.');
        foreach ($mautic_segments as $segment) {
            //$this->log('segment: '. print_r($segment, true));
            $name = strtolower($segment['name']);
            $groups[$name]['mautic'] = $segment['id'];
            $groups[$name]['site'] = $segment;
        }
        // print the result
        foreach ($groups as $name => $group) {
            if (isset($group['wp']) && isset($group['mautic'])) {
                $this->log('Group match: '. $name . ' WP('.$group['wp'].'), Mautic('.$group['mautic'].')');
                $groups['matches']++;
            }
        }
        // get group related stuff - WP networks and Mautic Segments
        // match them on name
        return $groups;
    }

    public function insert_site_nav_link() {
        $path =  '../..'.parse_url(MAUTIC_URL, PHP_URL_PATH).'mautic-site.php';
        $links = array(
            'site-info' => array('label' => __('Info'),
                'url' => 'site-info.php', 'cap' => 'manage_sites'),
            'site-users' => array('label' => __('Users'),
                'url' => 'site-users.php', 'cap' => 'manage_sites'),
            'site-mautic-sync' => array('label' => __('Mautic Sync'),
                'url' => $path, 'cap' => 'manage_sites'),
            'site-themes' => array('label' => __('Themes'),
                'url' => 'site-themes.php', 'cap' => 'manage_sites'),
            'site-settings' => array( 'label' => __('Settings'),
                'url' => 'site-settings.php', 'cap' => 'manage_sites')
        );
        return $links;
    }




    // clean up if this plugin is deactivated.
    /*public function deactivate() {
        // nuke everything created by other objects
        $this->auth->deactivate;
    }*/

}
