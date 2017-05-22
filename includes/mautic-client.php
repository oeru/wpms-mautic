<?php

include_once MAUTIC_PATH . '/includes/mautic-auth.php';
use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;

class MauticClient extends MauticAuth {

    //protected $auth = NULL; // need to be able to access API endpoint
    protected $api = NULL; // a validated API session

    public function __construct() {
        // do nothing
        MauticAuth::__construct();
    }

    // create a
    public function init_api($context = 'contacts') {
        // make sure auth is valid
        if ($settings = $this->get_auth_details()) {
            //$settings['password'] = 'wrong'; // set this to wrong...
            //$this->log('settings we are using to Auth: '.print_r($settings, TRUE));
            // see https://github.com/mautic/api-library
            session_start();  // initiate a session

            $initAuth = new ApiAuth();
            $auth = $initAuth->newAuth($settings, $settings['AuthMethod']);
            //$this->log('Auth request: '.print_r($auth, true));

            // Get a Contact context
            $api = new MauticApi();
            $connection = $api->newApi($context, $auth, $settings['apiUrl']);
            //$this->log('API connection: '.print_r($connection, true));

            // test the connection
            if ($results = $connection->getList()) {
                //$this->log('getList test: '.print_r($results, true));
                // integrate a proper error message for WP,
                // redirect to setting page, let admin correct error
                if (isset($results['errors'])) {
                    $type = $results['error'];
                    $message = $results['error_description'];
                    add_settings_error(
                        MAUTIC_ADMIN_TITLE,
                        MAUTIC_ADMIN_SLUG,
                        $message,
                        $type
                    );
                    $this->log('Oh noes! Error. Redirecting to settings page.');
                //    $url = 'admin.php?page='.MAUTIC_ADMIN_SLUG;
                //    wp_redirect(admin_url($url));
                //    exit;
                } elseif (isset($results['total'])) {
                    return $connection;
                }
            }
        }
        return false;
    }

    // get some basic stats on the WP and Mautic sites to
    // test the API
    public function get_stats() {
        $stats = array(
            'num_contacts' => 0,
            'num_segments' => 0
        );

        // get the number of Contacts
        if ($contacts = $this->init_api('contacts')) {
            $results = $contacts->getList();
            //$this->log('contacts context - total:'.$results['total']);
            $stats['num_contacts'] = $results['total'];
        } else {
            $this->log('failed to get a contacts context!');
            return false;
        }
        // get the number of Segments
        if ($segments = $this->init_api('segments')) {
            $results = $segments->getList();
            //$this->log('segments context - total:'.$results['total']);
            $stats['num_segments'] = $results['total'];
        } else  {
            $this->log('failed to get a segments context!');
            return false;
        }
        return $stats;
    }

    // get the actual contact data
    public function get_contacts() {
        $contacts = array();
        // get the full list of Contacts
        if ($context = $this->init_api('contacts')) {
            // default upper limit is 30 - set it to a large number
            $contacts = $context->getList('',0,1000000,'','ASC',true,true);
        } else {
            $this->log('failed to get a contacts context!');
            return false;
        }
        return $contacts;
    }

    // get the actual segment data
    public function get_segments() {
        $segments = array();
        // get the full list of Contacts
        if ($context = $this->init_api('segments')) {
            $segments = $context->getList();
        } else {
            $this->log('failed to get a segments context!');
            return false;
        }
        return $segments;
    }

    // check for a segment matching a site
    public function has_segment($alias) {
        if ($segmentApi = $this->init_api('segments')) {
            $searchFilter = 'alias:'.$alias;
            $searchFilter = $alias;
            $this->log('searchFilter: '.$searchFilter);
            if ($segments = $segmentApi->getList($searchFilter,0,1,'','',true,true)) {
                $this->log('segments info: '.print_r($segments, true));
                if (!$this->api_error($segments)) {
                    $this->log('segment info: '.print_r($segment, true));
                    foreach($segments['lists'] as $id => $segment) {
                        // return the first one
                        return $segment;
                    }
                }
            }
        }
        return false;
    }

    // create a new segment with the given name and short name (aka "tag")
    public function create_segment($name, $tag) {
        if ($segmentApi = $this->init_api('segments')) {
            $data = array(
                'name'        => $name,
                'alias'       => strtolower($tag),
                'description' => 'Segment created to represent "'.$name.'" site.',
                'isPublished' => 1
            );
            $segment = $segmentApi->create($data);
            if ($this->api_error($segment)) {
                return false;
            } else {
                $this->log('segments: '.print_r($segment, true));
                return true;
            }
        } else {
            $this->log('failed to get a segments context!');
            return false;
        }
    }

    // get a list of contacts by email
    public function get_contacts_by_email($users) {
        if ($contactApi = $this->init_api('contacts')) {
            $people = array(); // initialise array we're going to return
            $searchFilter = ''; // initialise
            foreach($users as $user) {
                // add the user object to our people array, keyed on email
                //$people[$user->data->user_email]['user'] = $user;
                $people[$user->data->user_email]['wp_id'] = $user->data->ID;
                $people[$user->data->user_email]['wp_name'] = $user->data->display_name;
                // build the search filter
                if ($searchFilter == '') {
                    $searchFilter .= 'email:'.$user->data->user_email;
                } else {
                    $searchFilter .= ' OR email:'.$user->data->user_email;
                }
            }
            $this->log('searchFilter: '.$searchFilter);
            if ($contacts = $contactApi->getList($searchFilter,0,count($contacts),
                '','',true,true)) {
                $this->log('contacts info: '.print_r($contacts, true));
                if (!$this->api_error($contacts)) {
                    //$this->log('contact info: '.var_dump($contacts, true));
                    foreach($contacts['contacts'] as $contact) {
                        //$this->log('contact core fields:'.print_r($contact['fields']['core'], true));
                        $email = $contact['fields']['core']['email']['value'];
                        //$people[$email]['contact'] = $contact['fields']['core'];
                        $people[$email]['m_id'] = $contact['id'];
                        $people[$email]['m_name'] = $contact['fields']['core']['firstname']['value'].' '.
                            $contact['fields']['core']['lastname']['value'];
                    }
                    return $people;
                }
            }
        }
        return false;
    }

    // get the contacts in a segment
    public function get_contacts_for_segment($segment_id, $contacts) {
    }
    // handle API errors in a standard way
    protected function api_error($obj) {
        if (is_array($obj['errors'])) {
            $num_errors = count($obj['errors']);
            $msg = $num_errors.' ';
            $msg .= ($num_errors == 1) ? 'error' : 'errors';
            $msg .= ' found';
            $this->log($msg);
            foreach($obj['errors'] as $error) {
                $this->log('error code: '.$error['code']);
                $this->log('error message: '.$error['message']);
                foreach($error['details'] as $key => $val) {
                    $this->log('error detail - '.$key.': '.$val[0]);
                }
            }
            return true;
        }
        return false;
    }
}
