<?php

/*
 *  Provides per-site Mautic Sync integration
 *  limits the context of actions to a particular site in a multi-site
 *  implementation.
 */

/* Load WordPress Administration Bootstrap */

/* Start Wordpress Site Admin boilerplate */
require_once( dirname( __FILE__ ) . '/../../../wp-admin/admin.php' );

if ( ! current_user_can( 'manage_sites' ) ) {
	wp_die( __( 'Sorry, you are not allowed to edit this site.' ) );
}

get_current_screen()->add_help_tab( array(
	'id'      => 'mautic-sync',
	'title'   => __( 'Overview' ),
	'content' =>
		'<p>' . __( '<strong>Mautic Sync Remediation</strong> &mdash; This page initiates the synchronisation of an existing WordPress Multisite instance with a Mautic instance.' ) . '</p><p>' . __( 'It creates a Mautic Segment for each WP Site, creates an equivalent Mautic Contact for each WP User, and based on their Site membership, associates them with the relevant Mautic Segment.' ) . '</p><p>' . __('After running it once, this plugin will maintain synchronisation as new Sites and Users are added/removed and associations between them change over time. <em>This script should not be run again on a site</em> (unless you know what you are doing!), as it will pollute subsequent ongoing synchronisation provided by this plugin.') . '</p>'
) );

get_current_screen()->set_help_sidebar(
	'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
	'<p>' . __( '<a href="https://codex.wordpress.org/Network_Admin_Sites_Screen">Documentation on Site Management</a>' ) . '</p>' .
	'<p>' . __( '<a href="https://wordpress.org/support/forum/multisite/">Support Forums</a>' ) . '</p>'
);

if ( isset( $_GET['update'] ) ) {
	$messages = array();
	if ( 'updated' == $_GET['update'] ) {
		$messages[] = __( 'Site info updated.' );
	}
}

/* translators: %s: site name */
$title = sprintf( __( 'Mautic Synchronisation Remediation' ));

$parent_file = 'sites.php';
$submenu_file = 'sites.php';

require( ABSPATH . 'wp-admin/admin-header.php' );

/* End Wordpress Site Admin boilerplate */

/**
 * Start the plugin only if in Admin side and if site is Multisite
 * see http://stackoverflow.com/questions/13960514/how-to-adapt-my-plugin-to-multisite/
 */
if (is_admin() && is_multisite()) {
    $sync = MauticSync::get_instance();
    $sync->log('testing!!');
}
?>

<div class="wrap">
<h1 id="edit-site"><?php echo $title; ?></h1>
<p>This page initiates the synchronisation of an existing WordPress Multisite instance with a Mautic instance.</p>
<p>It creates a Mautic Segment for each WP Site, creates an equivalent Mautic Contact for each WP User, and based on their Site membership, associates them with the relevant Mautic Segment.</p>
<p>After running it once, this plugin will maintain synchronisation as new Sites and Users are added/removed and associations between them change over time. <em>This script should not be run again on a site</em> (unless you know what you are doing!), as it will pollute subsequent ongoing synchronisation provided by this plugin.</p>
<?php

if ( ! empty( $messages ) ) {
	foreach ( $messages as $msg ) {
		echo '<div id="message" class="updated notice is-dismissible"><p>' . $msg . '</p></div>';
	}
}

// setup the remediation functionality
//$sync->catchup_init();
// do the remediation (if told to do so!)
$sync->catchup();

?>

</div>
<?php
require( ABSPATH . 'wp-admin/admin-footer.php' );
