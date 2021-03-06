<?php

include_once MAUTIC_PATH . '/includes/mautic-hooks.php';
include_once MAUTIC_PATH . '/includes/country_picker.php';

class MauticSync extends MauticHooks {

    protected static $instance = NULL; // this instance
    //protected $auth; // Auth object that allows access to the Mautic API
    protected $mautic; // Mautic API client object
    protected $tabs = array(
        'status' => 'Sync Status',
        /*'matched_sites' => 'Matched Sites',
        'unmatched_sites' => 'Unmatched Sites',
        'matched_users' => 'Matched Users',
        'unmatched_users' => 'Unmatched Users',
        'users_by_site' => 'Users By Site', */
    );
    protected $site_map = array(
       /* 'lida101' => array(
            'start_long' => 'Wed 14 March 2018',
            'start_short' => '20180314',
        ),
        'lida102' => array(
            'start_long' => 'Wed 4 April 2018',
            'start_short' => '20180404',
        )*/
        /*'lida103' => array(
            'start_long' => 'Wed 9 May 2018',
            'start_short' => '20180509',
        )*/
        /*'lida104' => array(
            'start_long' => 'Wed 13 June 2018',
            'start_short' => '20180612',
        )*/
        'ipm101' => array(
            'start_long' => 'Wed 19 September 2018',
            'start_short' => '20180919',
        ),
        'ipm102' => array(
            'start_long' => 'Wed 17 October 2018',
            'start_short' => '20181017',
        ),
        'ipm103' => array(
            'start_long' => 'Wed 14 November 2018',
            'start_short' => '20181114',
        ),
        'ipm104' => array(
            'start_long' => 'Wed 5 December 2018',
            'start_short' => '20181205',
        ),
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
    public function init() {
        //MauticAuth::init();
        $this->log('in MauticSync init');
        // create this object's menu items
        add_action('network_admin_menu', array($this, 'add_network_pages'));
        // add our updated links to the site nav links array via the filter
        //add_filter('network_edit_site_nav_links', array($this, 'insert_site_nav_link'), $links);
        add_filter('network_edit_site_nav_links', array($this, 'insert_site_nav_link'));
        // register all relevant hooks
        $this->register_hooks();
        $this->catchup_init();
        // create other necessary objects
        $this->mautic = new MauticClient();
    }

    public function site_init($id = '1') {
        $this->log('in site_init, id = '. $id);
        // set up appropriate ajax js file
        wp_enqueue_script( 'mautic-site-ajax-request', MAUTIC_URL.'app/js/site-ajax.js', array(
            'jquery',
            'jquery-form'
        ));
        // declare the URL to the file that handles the AJAX request
        // (wp-admin/admin-ajax.php)
        wp_localize_script( 'mautic-site-ajax-request', 'mautic_site', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'segment_nonce' => wp_create_nonce( 'mautic-segment-nonse'),
            'contact_nonce' => wp_create_nonce( 'mautic-contact-nonse')
        ));
        //add_action( 'wp_ajax_mautic_site', array($this, 'site_submit'));
        add_action( 'wp_ajax_mautic_create_segment', array($this, 'create_segment_for_site'));
        $this->site_tab($id);
    }

    public function demographics_init($id = '1') {
        $this->log('in demographics_init, id = '. $id);

        // don't get the main site...
        $site_id = $id;
        $site = get_site($site_id);
        $site_tag = $this->get_site_tag($site);
        //
        $this->log('site id: '.$site_id.', tag: '.$site_tag.'.');
        // get additional info about the blog
        $site_info = get_blog_details($site_id);
        $this->log('Site name: '. $site_info->blogname);
        // get the WP users for this site/course
        $searchFilter = 'blog_id='.$site_id.
            '&orderby=display_name&orderby=nicename';
        $users = get_users($searchFilter);
        $this->log('For site '.$site_tag.', '.count($users).' to be processed.');
        // get a list of contacts *already* in the segment, to compare emails
        $user_count = count($users);
        $country_list = array();
        foreach ($users as $user) {
            if ($country = get_user_meta($user->ID, 'usercountry', true)) {
               if ($country != '') {
                  $country = $this->translate_country($country);
                  $country_list[$country] += 1;
                  $this->log('setting country to '.$country);
                }
            }
        }
        $this->log('Number of countries represented: '.count($country_list));
        // sort in descending order.
        arsort($country_list);
        $countries = array();
        $num_counted = 0;
        foreach ($country_list as $country => $count) {
          $countries[$count][] = $country;
          $this->log($country.' '.$count);
          $num_counted += $count;
        }
        $this->log('Total number of segment contacts specifying a country: '.
            $num_counted.' out of '.$user_count);
        ?>
        <div class="mautic-demographics demographics">
            <h2 class="mautic-demographics demographics"><?php echo MAUTIC_DEMOGRAPHICS_TITLE; ?></h2>
        <?php
        $country_text = "countries were";
        if (count($country_list) == 1) {
            $country_text = "country is";
        }
        $this->log(count($country_list).' '.$country_text.' listed by '.$num_counted. ' users (out of '.
            $user_count.' total) with the following breakdown:');
        $msg = '<h3 class="demographics">'.count($country_list).' '.$country_text.' listed by '.$num_counted. ' (out of '.
            $user_count.' users total) with the following breakdown:</h3>';
        $msg .= '    <p class="demographics">';
        krsort($countries, SORT_NUMERIC);
        foreach($countries as $count => $array) {
            sort($array, SORT_NATURAL);
            $last = array_slice($array, -1);
            $first = join(', ', array_slice($array, 0, -1));
            $both  = array_filter(array_merge(array($first), $last), 'strlen');
            $joiner = (count($array) > 2) ? ', and ' : ' and ';
            $each = (count($array) > 1) ? 'each of ' : '';
            $string = join($joiner , $both);
            $msg .= '        <span class="demographics country"><strong>'.$count.'</strong> from '.$each.$string.'</span><br/>';
            $this->log('  '.$count.' from '.$each.$string);
        }
        $msg .= '    </p><!-- End demographics p-->';
        print $msg;
        ?>
        </div><!-- End of demographics -->
        <?php
    }

    // convert a country code into a country name or false if there's no match
    protected function translate_country($abbr) {
        global $country_picker;
        if ($country = $country_picker[$abbr]) {
            // adjust a few countries where we have a different country name from Mautic
            switch ($country) {
                case 'Korea':
                    $country = "South Korea";
                    break;
                case 'Russian Federation':
                    $country = "Russia";
                    break;
               case 'Trinidad And Tobago':
                    $country = "Trinidad and Tobago";
                    break;
               default:
                    break;
            }
            return $country;
        }
        return false;
    }

    public function catchup_init() {
        $this->log('in catchup_init');
        // set up appropriate ajax js file
        wp_enqueue_script( 'mautic-catchup-ajax-request', MAUTIC_URL.'app/js/catchup-ajax.js', array(
            'jquery',
            'jquery-form'
        ));
                // declare the URL to the file that handles the AJAX request
        // (wp-admin/admin-ajax.php)
        wp_localize_script( 'mautic-catchup-ajax-request', 'mautic_catchup', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'catchup_nonce' => wp_create_nonce( 'mautic-catchup-nonse')
        ));
        add_action( 'wp_ajax_mautic_catchup', array($this, 'catchup_submit'));
    }

    // Add settings menu entry and various other sub pages
    public function add_network_pages() {
        $this->log('in MauticSync->add_network_pages');
        // no op right now....
        add_menu_page(MAUTIC_TITLE, MAUTIC_MENU,
            'manage_options', MAUTIC_SLUG, array($this, 'sync_page'));
        add_submenu_page(MAUTIC_SLUG, MAUTIC_CATCHUP_TITLE,
            MAUTIC_CATCHUP_MENU, 'manage_options', MAUTIC_CATCHUP_SLUG,
            array($this, 'catchup_page'));
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
        $this->log('sync_stats_tab');
        $wp_stats = wp_get_stats();
        $m_stats = $this->mautic->get_stats();
        $people = $this->get_people();
        $groups = $this->get_groups();
        $catchup_path =  '/wp-admin/network/admin.php?page='.MAUTIC_CATCHUP_SLUG;;
        $settings_path =  '/wp-admin/network/admin.php?page='.MAUTIC_ADMIN_SLUG;
        ?>
        <div class="wrap" id="mautic-site">
            <h2>Mautic Synchronisation</h2>
            <p>The purpose of this plugin is to allow the synchronisation
            between WordPress users on this site and Mautic "contacts". </p>
            <p>Also, because this is a WordPress Multisite instance, we can
            have multiple sub-sites (called "sites") to which users can
            belong. We represent these in Mautic with "segments", and this
            plugin allows you to keep them synchronised.</p>
            <p>This section provides the status of synchronisation between
            WordPress and Mautic. Site-level integration with individual
            Mautic Segments is managed using the "Mautic Sync" tab on each
            <a href="/wp-admin/network/sites.php">site's administration page</a>.
            </p>
            <p>After <a href="<?php echo $settings_path; ?>">configuring your Mautic authentication</a> details, if you're adding Mautic integration to an existing Wordpress
                Multisite, you will probably want to perform an <a href="<?php echo $catchup_path; ?>">initial catch up</a>.</p>
            <table class="sync-table">
                <tr><th colspan=2 class="title">Mautic Sync Statistics</th>
                <tr>
                <tr>
                    <th scope="row">WordPress</th>
                    <td>
                        <p><strong><?php print($wp_stats['num_users']); ?></strong> Users</p>
                        <p><strong><?php print($wp_stats['num_sites']); ?></strong> Sites</p>
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
                        <p>Matched <strong><?php print($groups['matches']); ?></strong> WP Sites to corresonding Mautic Segments</p>
                        <p>Unmatched: <strong><?php print($wp_stats['num_users']-$people['matches']); ?></strong> WP Users, <strong><?php print($wp_stats['num_sites']-$groups['matches']); ?></strong> Sites</p>
                        <p>Unmatched: <strong><?php print($m_stats['num_contacts']-$people['matches']); ?></strong> Mautic Contacts,  <strong><?php print($m_stats['num_segments']-$groups['matches']); ?></strong> Segments</p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    // Print the menu page itself
    public function site_tab($site_id) {
        // get the site's name:
        $site = get_site($site_id);
        $site_name = $this->get_site_tag($site);
        $this->log('site: '.print_r($site, true));

        $this->log('site_tab');
        ?>
        <div class="wrap" id="mautic-site-sync">
            <h2>Mautic Synchronisation for <strong><?php echo $site->blogname.' ('.$site_name.')'; ?></strong></h2>
            <p>Site users and their Mautic status, and actions to alter that status.</p>
            <table class="sync-table segment">
                <?php
                // first check if there's a linked Segment in Mautic
                if ($segment = $this->has_segment($site_name)) {
                    $this->log('valid segment found');
                    $mautic_url = $this->get_baseurl();
                    $segment_edit = '/s/segments/edit/';
                    $segment_name = $segment['name'];
                    //$this->create_segment($site->blogname, $site_name);
                    ?>
                    <tr class="segment found">
                        <td class="label">Linked Mautic Segment:</td>
                        <td colspan=2><strong><?php echo $segment['name'].' ('.$segment['alias'].')'; ?></strong>
                            <a href="<?php echo $mautic_url.$segment_edit.$segment['id']; ?>" title="Edit on Mautic - <?php echo $mautic_url; ?>">edit segment</a>
                        </td>
                    </tr>
                    <?php
                } else {
                    $this->log('no segment found');
                    // if not, offer to create one
                    ?>
                    <tr class="segment create">
                        <th  class="label">No corresponding Mautic Segment</th>
                        <td colspan=2><p class="submit">
                            <input type="button" id="mautic-create-segment" class="button-primary" value="Create Segment"/>
                            <input type="hidden" id="mautic-current-site" value="<?php echo $site_id; ?>"/>
                            <input type="hidden" id="mautic-current-sitename" value="<?php echo $site_name; ?>"/>
                        </p></td>
                    </tr>
                    <?php
                }
                ?>
            </table>
            <table class="sync-table site">
            <?php
                // get the WP users for this site/course
                $searchFilter = 'blog_id='.$site_id.
                    '&orderby=display_name&orderby=nicename';
                $users = get_users($searchFilter);
                $this->log('users for blog '.$site_id.':'. print_r($users,true));
                // now find the Mautic contacts corresponding to the
                // users, and whether or not they're in the segment
                $people = $this->get_contacts_by_email($users);
                $this->log('people: '.print_r($people, true));
                //$contacts = $this->get_contacts_for_segment($users);
                if (count($users)) {
                    $alt = 0;
                    ?>
                    <tr class="heading">
                        <th class="label">WordPress User</th>
                        <th class="label">Mautic Contact</th>
                        <th class="label">Actions</th>
                    </tr>
                    <?php
                    foreach ($users as $index => $data){
                        $user_id = $data->ID;
                        $referrer =
                            '/wp-content/plugins/wpms-mautic/mautic-site.php?id='.
                            $site_id;
                        $wp_url = '/wp-admin/network/user-edit.php?user_id='.$user_id.
                            '&wp_http_referer='.$referrer;
                        $wp_name = $data->data->display_name;
                        $wp_email = $data->data->user_email;
                        $rowclass = "user-row";
                        $rowclass .= ($alt%2==0)? " odd":" even";
                        echo '<tr "'.$rowclass.'">';
                        echo '    <td class="wp-details"><a href="'.$wp_url.'">'.$wp_name.'</a> (<a href="mailto:'.$wp_email.'">'.$wp_email.'</a>)</td>';
                        //if ($contact = $this->get_mautic) {
                            echo '    <td class="mautic-details"><a href="'.$mautic_url.'">'.$mautic_name.'</a> (<a href="mailto:'.$mautic_email.'">'.$mautic_email.'</a>)</td>';
                        //}
                        echo '    <td class="actions">';
                        // if there's a valid segment, offer to add the user
                        if ($segment_name && !$contact) {
                            //echo '        <p class="button">';
                            echo '            <input type="button" class="button" id="mautic-add-user-to-segment" value="Add to Segment"/>';
                        //    echo '        </p>';
                        } else { // if not, don't
                            echo '        Segment required...';
                        }
                        echo '    </td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr "'.$rowclass.'">';
                    echo ' <td class="no-users">This Site has no Users.</td>';
                    echo '</tr>';
                }?>
            </table>
            <!--<input type="hidden" id="mautic-create-segment-nonce" value="<?php echo $nonce_create_segment; ?>" />-->
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
            $name = $this->get_site_tag($site);
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

    // add our Mautic Sync per Site tab to the Site Edit Nav
    public function insert_site_nav_link($links) {
        $path =  '../..'.parse_url(MAUTIC_URL, PHP_URL_PATH).'mautic-site.php';
        $links['site-mautic-sync'] =  array('label' => __('Mautic Sync'),
            'url' => $path, 'cap' => 'manage_sites');
        $path = '../..'.parse_url(MAUTIC_URL, PHP_URL_PATH).'mautic-site-demographics.php';
        $links['site-mautic-demographics'] =  array('label' => __('Demographics'),
            'url' => $path, 'cap' => 'manage_sites');
        return $links;
    }

    // given a site object, return the site's tag/name
    public function get_site_tag($site) {
        return strtolower(substr($site->path,1,-1));
    }
    // clean up if this plugin is deactivated.
    /*public function deactivate() {
        // nuke everything created by other objects
        $this->auth->deactivate;
    }*/

    // initially bring a site up to speed by syncing existing content
    // with a Mautic instance!
    public function catchup_page() {
        $this->log('in catchup!!');

        // @TODO we should probably check if this site has valid
        // Mautic site credentials and direct the admin to set them
        // if not!!

        // check if this has been run before
        $setting = 'mautic_catchup';
        if (!get_option($setting) || get_option($setting) == false) {
            $this->catchup_init();
            $catchup_nonce = wp_create_nonce('mautic-catchup');
            $this->log("catchup_nonce: ". $catchup_nonce);
            ?>
            <div class="wrap" id="mautic_catchup">
                <h1 id="edit-site"><?php echo MAUTIC_CATCHUP_TITLE; ?></h1>
                <p>This page initiates the synchronisation of an existing WordPress Multisite instance with a Mautic instance.</p>
                <p>It creates a Mautic Segment for each WP Site, creates an equivalent Mautic Contact for each WP User, and based on their Site membership, associates them with the relevant Mautic Segment.</p>
                <p>Use with care! It is <em>probably</em> safe to run this multiple times, although it may overwrite manual changes made to Contacts who are also WordPress users. It might also create
                    additional Segments in addition to any manually created Site corresponding Segments that don't use the appropriate naming convention.</p>
                <form method="post" action="" id="mautic-catchup-form">
                    <p>This site requires a catch up!</p>
                    <p class="submit">
                        <input type="submit" id="mautic-catchup-submit" class="button-primary" value="Catch Up" />
                        <input type="hidden" id="mautic-catchup-nonce" value="<?php echo $catchup_nonce; ?>" />
                    </p>
                    <p id="mautic-userstatus" style="color: red">&nbsp;</p>
                </form>
            </div>
            <?php
            $this->log('catchup form completed');
            // get a list of Sites and create equivalent Segments in
            // Mautic for any not aleady there.

            // Once run successfully, set a variable to
            // avoid running again.
            //return update_option($setting, true);
        } else {
            ?>
            <p><strong>This site has already been caught up!</strong></p>
            <?php
            // this can be removed when this code works (correctly catchups)
            if (MAUTIC_DEBUG) {
                //return update_option($setting, false);
            }
        }
        return false;
    }

    // ajax function
    public function catchup_submit() {
        $this->log('in catchup_submit:'.print_r($_POST, true));
        /*if ( ! wp_verify_nonce( $_POST['mautic-catchup-nonce'], 'mautic-catchup') ) {
            $this->log('nonce not verified!');
            die ("Busted - someone's trying something funny in submit!");
        } else { */

        // store the info for processing
        $list = array();
        // don't get the main site...
        $args = array(
            'site__not_in' => '1',
        );
        $sites = get_sites($args);
        //$this->log('sites: '. print_r($sites, true));
        // get the name
        $parser = new FullNameParser();
        foreach ($sites as $site) {
            $site_id = $site->blog_id;
            $site_tag = $this->get_site_tag($site);
            //
            // we're only looking to create lists for scheduled courses!
            if (!array_key_exists($site_tag, $this->site_map)) {
                $this->log('skipping site "'.$site_tag.'"');
                continue;
            }
            $this->log('site id: '.$site_id.', tag: '.$site_tag.'.');
            $list[$site_id]['tag'] = $site_tag;
            // get additional info about the blog
            $site_info = get_blog_details($site_id);
            $list[$site_id]['name'] = $site_info->blogname;
            $this->log('Site name: '. $site_info->blogname);
            // get the WP users for this site/course
            $searchFilter = 'blog_id='.$site_id.
                '&orderby=display_name&orderby=nicename';
            $users = get_users($searchFilter);
            $this->log('For site '.$site_tag.', '.count($users).' to be processed.');
            // get a list of contacts *already* in the segment, to compare emails
            foreach ($users as $user) {
                $name = array();
                $user_id = $user->ID;
                //$this->log('site_info: '. print_r($site_info, true));
                // get the user's country (if it's been set)
                $country = get_user_meta($user_id, 'usercountry', true);
                $this->log('User '.$user->display_name.' has country '.$country);
                // parse the user's name!
                $name = $parser->parse_name($user->display_name);
                $list[$site_id]['users'][$user_id] = array(
                    'username' => $user->user_nicename,
                    'fullname' => $user->display_name,
                    'firstname' => $name['fname'],
                    'lastname' => $name['lname'],
                    'email' => $user->user_email,
                    'country' => $country,
                    'ipAddress' => '127.0.0.1'
                );
            }
        }
        $this->log('##### starting processing of sites and users ######');
        foreach($list as $site_id => $site) {
            //$this->log('Site (pre-slashes)'.$site['name'].' ('.$site['tag'].'): ');
            $segment_name = quotemeta($site['name']).' starting '.$this->site_map[$site['tag']]['start_long'];
            $segment_alias = quotemeta($site['tag']).'-'.$this->site_map[$site['tag']]['start_short'];
            //$this->log('Site (post-slashes) '.$site['name'].' ('.$site['tag'].'): ');
            if ($segment = $this->has_segment($segment_alias)) {
                $this->log('Segment "'.$segment_alias.'" exists:'.print_r($segment, true));
            } else {
                $this->log('Creating segment '.$segment_alias.'.');
                if ($segment = $this->create_segment($segment_name, $segment_alias)) {
                    $this->log('Segment '.$segment_alias.' created.');
                } else {
                    $this->log('Creating segment '.$segment_alias.' failed.');
                }
            }
            $user_count = count($site['users']);
            $this->log('For segment '.$segment_name.', '.$user_count.' to be processed.');
            $segment_contacts = $this->get_contacts_for_segment($segment_alias);
            $this->log('Found '.count($segment_contacts).' already part of the segment');
            $country_list = array();
            foreach($site['users'] as $user) {
                $person = array(
                    // these are field aliases, and values
                    'email' => $user['email'],
                    //'ipAddress' => $user['ipAddress'],
                );
                // if there's no firstname, use the user's username
                $person['firstname'] = ($user['firstname']) ? $user['firstname'] : $username;
                // work out the full country name from the abbreviation, because
                // Mautic doesn't store the abbreviation for some reason. So
                // much for open standards *sigh*
                // only include the country if the field is set.
                if ($user['country'] != '') {
                    if ($person['country'] = $this->translate_country($user['country'])) {
                        $country_list[$person['country']] += 1;
                        $this->log('setting country for '.$person['firstname'].' to '.$person['country']);
                    } else {
                        $this->log('No country found for designator '.$user['country'].'!!');
                    }
                }
                // before we go to the trouble of adding this user, check if they're already
                // in the segment... if so, skip them.
                if (isset($segment_contacts[$user['email']])) {
                    $this->log('Skipping User with email '.$user['email'].', already in '
                        .$segment_alias.', id: '.$segment_contacts[$user['email']]['m_id']
                        .' name: '.$segment_contacts[$user['email']]['m_name'].'.');
                    $user_count--;
                    continue;
                }
                $this->log('creating/modifying user: '. print_r($user, true));
                // if there's no lastname, don't set one.
                if (isset($user['lastname'])) $person['lastname'] = $user['lastname'];
                $this->log($user_count.' **** person to be made a contact: '.print_r($person, true));
                // now make it happen in Mautic!
                $contact = $this->create_contact($person);
                //$this->log('segment: '.print_r($segment, true));
                $this->log('adding user '.$user['email'].' to segment "'.$segment_name.'" ('.$segment_alias.')');
                if ($this->add_contact_to_segment($contact['contact']['id'], $segment['id'])) {
                    $this->log('added user '.$user['email'].' to segment "'.$segment_name.'" ('.$segment_alias.')');
                } else {
                    $this->log('failed to add user '.$user['email'].' to segment "'.$segment_name.'" ('.$segment_alias.')');
                }
                $user_count--;
            }
            //$this->demographics($country_list, count($site['users']));
        }
    }
}
