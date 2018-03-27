<?php
/**
 * @package Mautic Synchronise
 */
/*
Plugin Name: Mautic Synchronise
Plugin URI: http://github.com/oeru/wpms-mautic
Description: Synchronise WordPress multi-site users with OERu Course-specific Mautic Segments depending on Courses (subsites) with which they are associated.
Version: 0.3.0
Author: Dave Lane
Author URI: https://davelane.nz
License: GPLv2 or later
Network: true
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

define('MAUTIC_VERSION', '0.3.0');
// the path to this file
define('MAUTIC_FILE', __FILE__);
// absolute URL for this plugin, including site name, e.g.
// https://sitename.nz/wp-content/plugins/
define('MAUTIC_URL', plugins_url("/", __FILE__));
// absolute server path to this plugin
define('MAUTIC_PATH', plugin_dir_path(__FILE__));
// module details
define('MAUTIC_SLUG', 'mautic_sync');
define('MAUTIC_TITLE', 'Mautic Synchronisation');
define('MAUTIC_MENU', 'Mautic Sync');
// admin details
define('MAUTIC_ADMIN_SLUG', 'mautic_settings');
define('MAUTIC_ADMIN_TITLE', 'Mautic Synchronisation Settings');
define('MAUTIC_ADMIN_MENU', 'Mautic Settings');
// catchup details
define('MAUTIC_CATCHUP_SLUG', 'mautic_catchup');
define('MAUTIC_CATCHUP_TITLE', 'Mautic Synchronisation Catch Up');
define('MAUTIC_CATCHUP_MENU', 'Mautic Catch Up');
// demographcis details
define('MAUTIC_DEMOGRAPHICS_SLUG', 'mautic_demographics');
define('MAUTIC_DEMOGRAPHICS_TITLE', 'Site Demographics');
define('MAUTIC_DEMOGRAPHICS_MENU', 'Demographics');
// api endpoint
define('MAUTIC_API_ENDPOINT', 'api');
//// turn on debugging with true, off with false
define('MAUTIC_DEBUG', false);
//define('MAUTIC_DEBUG', false);
// allow extra time to accommodate the fact that our learners might be
// up to 12 hrs different from our time zones - based UTC, which our server uses
define('MAUTIC_TZ_PRE_OFFSET', 12);
define('MAUTIC_TZ_POST_OFFSET', 12);

// include Mautic API and Auth code
include_once MAUTIC_PATH . '/vendor/autoload.php';
// the rest of the app
require MAUTIC_PATH . '/includes/mautic-sync.php';

/**
 * Start the plugin only if in Admin side and if site is Multisite
 * see http://stackoverflow.com/questions/13960514/how-to-adapt-my-plugin-to-multisite/
 */
if (is_admin() && is_multisite()) {
    add_action('plugins_loaded',
        array(MauticSync::get_instance(), 'init')
    );
}
