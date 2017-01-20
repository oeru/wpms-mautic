<?php
/**
 * @package OERu Mautic
 */
/*
Plugin Name: OERuMautic
Plugin URI: http://github.com/oeru/wpms-mautic
Description: Synchronise WordPress users with Course-specific Mautic Segments
  depending on Courses (subsites) with which they are associated
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

define( 'OERU_MAUTIC_VERSION', '0.1.0' );

if ( !function_exists( 'add_action' ) ) {
	echo 'This only works as a WordPress plugin.';
	exit;
}

add_shortcode( 'WEnotes', 'wenotes_func' );
add_shortcode( 'WEnotesPost', 'wenotespost' );

function wenotes_func( $atts ) {
	$a = shortcode_atts( array(
	    'tag' => '_',
	    'count' => 20
	    ), $atts );

	$tag = strtolower( $a['tag'] );
	$count = $a['count'];

	$wenotesdiv = <<<EOD
<div class="WEnotes WEnotes-$count-$tag" data-tag="$tag" data-count="$count">
  <img class="WEnotesSpinner" src="//wikieducator.org/skins/common/images/ajax-loader.gif" alt="Loading..." style="margin-left: 53px;">
</div>
<script type="text/javascript">/*<![CDATA[*/
$ = window.jQuery;
window.wgUserName = null;
$(function() {
  if (!window.WEnotes) {
    window.WEnotes = true;
    $.getScript('//wikieducator.org/extensions/WEnotes/WEnotes-min.js');
  }
})/*]]>*/</script>
EOD;
	return $wenotesdiv;
}

function wenotespost( $atts ) {
	$a = shortcode_atts( array(
	    'tag' => '',
	    'button' => 'Post a WEnote',
	    'leftmargin' => '53',
	    'anonymous' => 'You must be logged in to post to WEnotes.'
	), $atts );
	$current_user = wp_get_current_user();
	if ( $current_user->ID == 0 ) {
		$wenotespostdiv = '';
		if ( $a['anonymous'] ) {
			$wenotespostdiv = '<div><p>' . $a['anonymous'] . '</p></div>';
		}
	} else {
		wp_enqueue_script( 'wenotespostwp',
			plugins_url( 'wenotes/WEnotesPostWP.js', __FILE__ ),
			array( 'jquery' ),
			WENOTES_VERSION,
			true );
		$wenotespostdiv = <<<EOD
<div id="WEnotesPost1"></div>
<script type="text/javascript">/*<![CDATA[*/
$ = window.jQuery;
$(function() {
  WEnotesPostWP("WEnotesPost1", '${a['tag']}', '${a['button']}', '${a['leftmargin']}');
})/*]]>*/</script>
EOD;
	}
	return $wenotespostdiv;
}

add_action( 'wp_ajax_wenotes', 'wenotespost_ajax' );
function wenotespostresponse( $a ) {
	echo json_encode( $a );
	die();
}

function wenotespost_ajax() {
	require_once( 'sag/src/Sag.php' );

	$current_user = wp_get_current_user();
	list( $usec, $ts ) = explode( ' ', microtime() );
        $sag = new Sag( WENOTES_HOST, WENOTES_PORT );
        $sag->setDatabase( WENOTES_DB );
        $sag->login( WENOTES_USER, WENOTES_PASS );

        $data = array(
                'from_user' => $current_user->user_login,
                'from_user_name' => $current_user->display_name,
                'created_at' => date( 'r', $ts ),
                'text' => stripslashes(trim($_POST['notext'])),
                'id' => $current_user->ID . $ts . substr( "00000$usec", 0, 6 ),
                'we_source' => 'course',
                'we_tags' => array( strtolower(trim($_POST['notag'])) ),
                'we_timestamp' => date('Y-m-d\TH:i:s.000\Z', $ts)
        );
        if ( $current_user->user_email ) {
                $data['gravatar'] = md5( strtolower( trim( $current_user->user_email ) ) );
        }
        if ( $current_user->user_url ) {
                $data['profile_url'] = $current_user->user_url;
        }
        if ( isset( $_POST['we_page'] ) ) {
                $data['we_page'] = stripslashes($_POST['we_page']);
        }
        if ( isset( $_POST['we_root'] ) ) {
                $data['we_root'] = $_POST['we_root'];
        }
        if ( isset( $_POST['we_parent'] ) ) {
                $data['we_parent'] = $_POST['we_parent'];
        }

        $sag->post( $data );

	wenotespostresponse( array(
		'posted' => true
	));
}
