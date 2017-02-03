<?php
/**
 * @package Mautic Synchronise
 */
/*
Plugin Name: Mautic Synchronise
Plugin URI: http://github.com/oeru/wpms-mautic
Description: Synchronise WordPress multi-site users with OERu Course-specific Mautic Segments depending on Courses (subsites) with which they are associated.
Version: 0.1.0
Author: Dave Lane
Author URI: https://davelane.nz
License: GPLv2 or later
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

define( 'MAUTIC_VERSION', '0.1.4' );

if ( !function_exists( 'add_action' ) ) {
	echo 'This only works as a WordPress plugin.';
	exit;
}

if ( !is_multisite() ) {
    echo "This plugin is only useful with a multi-site implementation.";
    exit;
}
//add_shortcode( 'WEnotes', 'wenotes_func' );
//add_shortcode( 'WEnotesPost', 'wenotespost' );

define('MAUTIC_FILE', __FILE__);
define('MAUTIC_URL', plugins_url("/", __FILE__));
define('MAUTIC_PATH', plugin_dir_path(__FILE__));
define('MAUTIC_KEY_SIZE', 50);
define('MAUTIC_SLUG', 'mautic-sync');

require MAUTIC_PATH . 'includes/mautic-sync.php';
//require MAUTIC_PATH . 'includes/MauticAdmin.php';

new MauticSync();
?>
