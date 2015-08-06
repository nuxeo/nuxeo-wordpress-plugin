<?php

// Register settings page under the WordPress settings menu
add_action( 'admin_menu', 'basic_nuxeo_menu' );
function basic_nuxeo_menu() {
	add_options_page( __('Nuxeo Options','menu-nuxeo'), __('Nuxeo Options','menu-nuxeo'), 'manage_options', 'basic-nuxeo-settings', 'basic_nuxeo_admin' );
	add_action( 'admin_init', 'register_nuxeo_settings' );
}

// Render settings page
function basic_nuxeo_admin() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	?>
	<div class="wrap">
	<h2>Nuxeo options</h2>
	<form method="post" action="options.php">
		<?php settings_fields( 'nuxeo-settings-group' ); ?>
		<?php do_settings_sections( 'nuxeo_repository' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="nx_repository_url">Nuxeo URL</label>
				</th>
				<td>
					<input type="text" class="regular-text" name="nx_repository_url" id="nx_repository_url" value="<?php echo get_option('nx_repository_url') ?>">
                    <span class="description">ex: http://localhost:8080/nuxeo</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="nx_workspace_root">Nuxeo Domain path</label>
				</th>
				<td>
					<input type="text" class="regular-text" name="nx_workspace_root" id="nx_workspace_root" value="<?php echo get_option('nx_workspace_root') ?>">
                    <span class="description">ex: /default-domain</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="nx_username">User name</label>
				</th>
				<td>
					<input type="text" name="nx_username" id="nx_username" value="<?php echo get_option('nx_username') ?>">
				</td>
			</tr>
			<tr valign="row">
				<th scope="row">
					<label for="nx_password">Password (not mandatory)</label>
				</th>
				<td>
					<input type="password" name="nx_password" id="nx_password" value="<?php echo get_option('nx_password') ?>">
				</td>
			</tr>
			<tr valign="row">
				<th scope="row">
					<label for="nx_display_query">Display NXQL query (Debug)</label>
				</th>
				<td>
					<input type="radio" name="nx_display_query" id="nx_display_query" value="true" <?php if (get_option('nx_display_query') === 'true') { echo 'checked'; } ?>>True
					<input type="radio" name="nx_display_query" id="nx_display_query" value="false" <?php if (get_option('nx_display_query') === 'false') { echo 'checked'; } ?>>False<br/>
				</td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		</p>
	</form>
	</div>
	<?php
}

// Register Nuxeo settings with WordPress
function register_nuxeo_settings() {
	register_setting( 'nuxeo-settings-group', 'nx_repository_url' );
	register_setting( 'nuxeo-settings-group', 'nx_workspace_root' );
	register_setting( 'nuxeo-settings-group', 'nx_username' );
	register_setting( 'nuxeo-settings-group', 'nx_password' );
	register_setting( 'nuxeo-settings-group', 'nx_display_query' );
	add_settings_section( 'nuxeo_repository', 'Nuxeo Repository Settings', 'f_nuxeo_repository_settings_section', 'nuxeo_repository' );
}

// Render section content for Nuxeo Repository section
function f_nuxeo_repository_settings_section() {
	?>
	<p>
		Provide connection informations for a Nuxeo repository.
	</p>
	<?php
}
?>
