<?php

include_once MAUTIC_PATH . '/includes/mautic-client.php';
use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;

class MauticHooks extends MauticClient {
    /*
     * Hooks for WP actions
     */

    // initialise the hook methods
    public function register_hooks() {
        // register the hook methods
        add_action('wpmu_new_blog', array($this, 'add_site'));

    }

    // site related
    /**
     * add a site
     *
     * @param int    $blog_id Blog ID.
     * @param int    $user_id User ID.
     * @param string $domain  Site domain.
     * @param string $path    Site path.
     * @param int    $site_id Site ID. Only relevant on multi-network installs.
     * @param array  $meta    Meta data. Used to set initial site options.
     */
    public function add_site($blog_id, $user_id, $domain, $path, $site_id, $meta) {
        $this->log('in add_site hook');
        // need to create a new Segment

    }

    public function delete_site() {
        $this->log('in delete_site hook');
    }
    // end site related

    // user related

    // end user related

    // user and site related

    // user and site related

    /*
     * End Hooks
     */
}
