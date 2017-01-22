<?php
// see https://codex.wordpress.org/Creating_Options_Pages

// create custom plugin settings menu
add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'oeru_mautic_create_menu' );

function oeru_mautic_create_menu() {

	//create new top-level menu
	add_menu_page('OERu Mautic Synchronisation Settings', 'Mautic Settings', 'administrator', __FILE__, 'oms_settings_page' , plugins_url('/images/icon.png', __FILE__) );

	//call register settings function
	add_action( 'admin_init', 'register_oeru_mautic_settings' );
}


function register_oeru_mautic_settings() {
	//register our settings
	register_setting( 'oeru-mautic-settings-group', 'mautic_api_url' );
	register_setting( 'oeru-mautic-settings-group', 'mautic_auth_method' );
    register_setting( 'oeru-mautic-settings-group', 'mautic_api_public_key' );
    register_setting( 'oeru-mautic-settings-group', 'mautic_api_secret_key' );
}

function oeru_mautic_settings_page() {
?>
<div class="wrap">
<h1>OERu Mautic API configuration</h1>

<form method="post" action="options.php">
    <?php settings_fields( 'oeru-mautic-settings-group' ); ?>
    <?php do_settings_sections( 'oeru-mautic-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Mautic API address (URL)</th>
        <td><input type="text" name="mautic_api_url" value="<?php echo esc_attr( get_option('mautic_api_url') ); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Mautic Authentication Method</th>
        <td><input type="text" name="mautic_auth_method" value="<?php echo esc_attr( get_option('mautic_auth_method') ); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Mautic API Public Key</th>
        <td><input type="text" name="mautic_api_public_key" value="<?php echo esc_attr( get_option('mautic_api_public_key') ); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Mautic API Secret Key</th>
        <td><input type="text" name="mautic_api_secret_key" value="<?php echo esc_attr( get_option('mautic_api_secret_key') ); ?>" /></td>
        </tr>

    </table>

    <?php submit_button(); ?>

</form>
</div>
<?php } ?>
