<?php
use function WP\getApp;

/**
 * @staticvar bool $first_pass
 *
 * @param object $update
 */
function list_core_update( $update ) {
  	static $first_pass = true;

	$app = getApp();
	$wpdb = $app['db'];
	$wp_local_package = $app['wp_local_package'];

 	if ( 'en_US' == $update->locale && 'en_US' == get_locale() )
 		$version_string = $update->current;
 	// If the only available update is a partial builds, it doesn't need a language-specific version string.
 	elseif ( 'en_US' == $update->locale && $update->packages->partial && $app['wp_version'] == $update->partial_version && ( $updates = get_core_updates() ) && 1 == count( $updates ) )
 		$version_string = $update->current;
 	else
 		$version_string = sprintf( "%s&ndash;<strong>%s</strong>", $update->current, $update->locale );

	$current = false;
	if ( !isset($update->response) || 'latest' == $update->response )
		$current = true;
	$submit = __('Update Now');
	$form_action = 'update-core.php?action=do-core-upgrade';
	$php_version    = phpversion();
	$mysql_version  = $wpdb->db_version();
	$show_buttons = true;
	if ( 'development' == $update->response ) {
		$message = __('You are using a development version of WordPress. You can update to the latest nightly build automatically:');
	} else {
		if ( $current ) {
			$message = sprintf( __( 'If you need to re-install version %s, you can do so here:' ), $version_string );
			$submit = __('Re-install Now');
			$form_action = 'update-core.php?action=do-core-reinstall';
		} else {
			$php_compat     = version_compare( $php_version, $update->php_version, '>=' );
			if ( file_exists( WP_CONTENT_DIR . '/db.php' ) && empty( $wpdb->is_mysql ) )
				$mysql_compat = true;
			else
				$mysql_compat = version_compare( $mysql_version, $update->mysql_version, '>=' );

			if ( !$mysql_compat && !$php_compat )
				$message = sprintf( __('You cannot update because <a href="https://codex.wordpress.org/Version_%1$s">WordPress %1$s</a> requires PHP version %2$s or higher and MySQL version %3$s or higher. You are running PHP version %4$s and MySQL version %5$s.'), $update->current, $update->php_version, $update->mysql_version, $php_version, $mysql_version );
			elseif ( !$php_compat )
				$message = sprintf( __('You cannot update because <a href="https://codex.wordpress.org/Version_%1$s">WordPress %1$s</a> requires PHP version %2$s or higher. You are running version %3$s.'), $update->current, $update->php_version, $php_version );
			elseif ( !$mysql_compat )
				$message = sprintf( __('You cannot update because <a href="https://codex.wordpress.org/Version_%1$s">WordPress %1$s</a> requires MySQL version %2$s or higher. You are running version %3$s.'), $update->current, $update->mysql_version, $mysql_version );
			else
				$message = 	sprintf(__('You can update to <a href="https://codex.wordpress.org/Version_%1$s">WordPress %2$s</a> automatically:'), $update->current, $version_string);
			if ( !$mysql_compat || !$php_compat )
				$show_buttons = false;
		}
	}

	echo '<p>';
	echo $message;
	echo '</p>';
	echo '<form method="post" action="' . $form_action . '" name="upgrade" class="upgrade">';
	wp_nonce_field('upgrade-core');
	echo '<p>';
	echo '<input name="version" value="'. esc_attr($update->current) .'" type="hidden"/>';
	echo '<input name="locale" value="'. esc_attr($update->locale) .'" type="hidden"/>';
	if ( $show_buttons ) {
		if ( $first_pass ) {
			submit_button( $submit, $current ? '' : 'primary regular', 'upgrade', false );
			$first_pass = false;
		} else {
			submit_button( $submit, '', 'upgrade', false );
		}
	}
	if ( 'en_US' != $update->locale )
		if ( !isset( $update->dismissed ) || !$update->dismissed )
			submit_button( __( 'Hide this update' ), '', 'dismiss', false );
		else
			submit_button( __( 'Bring back this update' ), '', 'undismiss', false );
	echo '</p>';
	if ( 'en_US' != $update->locale && ( !isset($wp_local_package) || $wp_local_package != $update->locale ) )
	    echo '<p class="hint">'.__('This localized version contains both the translation and various other localization fixes. You can skip upgrading if you want to keep your current translation.').'</p>';
	// Partial builds don't need language-specific warnings.
	elseif ( 'en_US' == $update->locale && get_locale() != 'en_US' && ( ! $update->packages->partial && $app['wp_version'] == $update->partial_version ) ) {
	    echo '<p class="hint">'.sprintf( __('You are about to install WordPress %s <strong>in English (US).</strong> There is a chance this update will break your translation. You may prefer to wait for the localized version to be released.'), $update->response != 'development' ? $update->current : '' ).'</p>';
	}
	echo '</form>';

}

/**
 * @since 2.7.0
 */
function dismissed_updates() {
	$dismissed = get_core_updates( array( 'dismissed' => true, 'available' => false ) );
	if ( $dismissed ) {

		$show_text = esc_js(__('Show hidden updates'));
		$hide_text = esc_js(__('Hide hidden updates'));
	?>
	<script type="text/javascript">

		jQuery(function($) {
			$('dismissed-updates').show();
			$('#show-dismissed').toggle(function(){$(this).text('<?php echo $hide_text; ?>');}, function() {$(this).text('<?php echo $show_text; ?>')});
			$('#show-dismissed').click(function() { $('#dismissed-updates').toggle('slow');});
		});
	</script>
	<?php
		echo '<p class="hide-if-no-js"><a id="show-dismissed" href="#">'.__('Show hidden updates').'</a></p>';
		echo '<ul id="dismissed-updates" class="core-updates dismissed">';
		foreach ( (array) $dismissed as $update) {
			echo '<li>';
			list_core_update( $update );
			echo '</li>';
		}
		echo '</ul>';
	}
}

/**
 * Display upgrade WordPress for downloading latest or upgrading automatically form.
 *
 * @since 2.7.0
 */
function core_upgrade_preamble() {
	$app = getApp();

	$updates = get_core_updates();

	if ( !isset($updates[0]->response) || 'latest' == $updates[0]->response ) {
		echo '<h2>';
		_e('You have the latest version of WordPress.');

		if ( wp_http_supports( array( 'ssl' ) ) ) {
			$upgrader = new WP_Automatic_Updater;
			$future_minor_update = (object) array(
				'current'       => $app['wp_version'] . '.1.next.minor',
				'version'       => $app['wp_version'] . '.1.next.minor',
				'php_version'   => $app['required_php_version'],
				'mysql_version' => $app['required_mysql_version'],
			);
			$should_auto_update = $upgrader->should_update( 'core', $future_minor_update, ABSPATH );
			if ( $should_auto_update )
				echo ' ' . __( 'Future security updates will be applied automatically.' );
		}
		echo '</h2>';
	} else {
		echo '<div class="notice notice-warning"><p>';
		_e('<strong>Important:</strong> before updating, please <a href="https://codex.wordpress.org/WordPress_Backups">back up your database and files</a>. For help with updates, visit the <a href="https://codex.wordpress.org/Updating_WordPress">Updating WordPress</a> Codex page.');
		echo '</p></div>';

		echo '<h2 class="response">';
		_e( 'An updated version of WordPress is available.' );
		echo '</h2>';
	}

	if ( isset( $updates[0] ) && $updates[0]->response == 'development' ) {
		$upgrader = new WP_Automatic_Updater;
		if ( wp_http_supports( 'ssl' ) && $upgrader->should_update( 'core', $updates[0], ABSPATH ) ) {
			echo '<div class="updated inline"><p>';
			echo '<strong>' . __( 'BETA TESTERS:' ) . '</strong> ' . __( 'This site is set up to install updates of future beta versions automatically.' );
			echo '</p></div>';
		}
	}

	echo '<ul class="core-updates">';
	foreach ( (array) $updates as $update ) {
		echo '<li>';
		list_core_update( $update );
		echo '</li>';
	}
	echo '</ul>';
	// Don't show the maintenance mode notice when we are only showing a single re-install option.
	if ( $updates && ( count( $updates ) > 1 || $updates[0]->response != 'latest' ) ) {
		echo '<p>' . __( 'While your site is being updated, it will be in maintenance mode. As soon as your updates are complete, your site will return to normal.' ) . '</p>';
	} elseif ( ! $updates ) {
		list( $normalized_version ) = explode( '-', $app['wp_version'] );
		echo '<p>' . sprintf( __( '<a href="%s">Learn more about WordPress %s</a>.' ), esc_url( self_admin_url( 'about.php' ) ), $normalized_version ) . '</p>';
	}
	dismissed_updates();
}

function list_plugin_updates() {
	$app = getApp();
	$cur_wp_version = preg_replace( '/-.*$/', '', $app['wp_version'] );

	require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
	$plugins = get_plugin_updates();
	if ( empty( $plugins ) ) {
		echo '<h2>' . __( 'Plugins' ) . '</h2>';
		echo '<p>' . __( 'Your plugins are all up to date.' ) . '</p>';
		return;
	}
	$form_action = 'update-core.php?action=do-plugin-upgrade';

	$core_updates = get_core_updates();
	if ( !isset($core_updates[0]->response) || 'latest' == $core_updates[0]->response || 'development' == $core_updates[0]->response || version_compare( $core_updates[0]->current, $cur_wp_version, '=') )
		$core_update_version = false;
	else
		$core_update_version = $core_updates[0]->current;
	?>
<h2><?php _e( 'Plugins' ); ?></h2>
<p><?php _e( 'The following plugins have new versions available. Check the ones you want to update and then click &#8220;Update Plugins&#8221;.' ); ?></p>
<form method="post" action="<?php echo esc_url( $form_action ); ?>" name="upgrade-plugins" class="upgrade">
<?php wp_nonce_field('upgrade-core'); ?>
<p><input id="upgrade-plugins" class="button" type="submit" value="<?php esc_attr_e('Update Plugins'); ?>" name="upgrade" /></p>
<table class="widefat updates-table" id="update-plugins-table">
	<thead>
	<tr>
		<td class="manage-column check-column"><input type="checkbox" id="plugins-select-all" /></td>
		<td class="manage-column"><label for="plugins-select-all"><?php _e( 'Select All' ); ?></label></td>
	</tr>
	</thead>

	<tbody class="plugins">
<?php
	foreach ( (array) $plugins as $plugin_file => $plugin_data ) {
		// Get plugin compat for running version of WordPress.
		if ( isset($plugin_data->update->tested) && version_compare($plugin_data->update->tested, $cur_wp_version, '>=') ) {
			$compat = '<br />' . sprintf(__('Compatibility with WordPress %1$s: 100%% (according to its author)'), $cur_wp_version);
		} elseif ( isset($plugin_data->update->compatibility->{$cur_wp_version}) ) {
			$compat = $plugin_data->update->compatibility->{$cur_wp_version};
			$compat = '<br />' . sprintf(__('Compatibility with WordPress %1$s: %2$d%% (%3$d "works" votes out of %4$d total)'), $cur_wp_version, $compat->percent, $compat->votes, $compat->total_votes);
		} else {
			$compat = '<br />' . sprintf(__('Compatibility with WordPress %1$s: Unknown'), $cur_wp_version);
		}
		// Get plugin compat for updated version of WordPress.
		if ( $core_update_version ) {
			if ( isset( $plugin_data->update->tested ) && version_compare( $plugin_data->update->tested, $core_update_version, '>=' ) ) {
				$compat .= '<br />' . sprintf( __( 'Compatibility with WordPress %1$s: 100%% (according to its author)' ), $core_update_version );
			} elseif ( isset( $plugin_data->update->compatibility->{$core_update_version} ) ) {
				$update_compat = $plugin_data->update->compatibility->{$core_update_version};
				$compat .= '<br />' . sprintf(__('Compatibility with WordPress %1$s: %2$d%% (%3$d "works" votes out of %4$d total)'), $core_update_version, $update_compat->percent, $update_compat->votes, $update_compat->total_votes);
			} else {
				$compat .= '<br />' . sprintf(__('Compatibility with WordPress %1$s: Unknown'), $core_update_version);
			}
		}
		// Get the upgrade notice for the new plugin version.
		if ( isset($plugin_data->update->upgrade_notice) ) {
			$upgrade_notice = '<br />' . strip_tags($plugin_data->update->upgrade_notice);
		} else {
			$upgrade_notice = '';
		}

		$details_url = self_admin_url('plugin-install.php?tab=plugin-information&plugin=' . $plugin_data->update->slug . '&section=changelog&TB_iframe=true&width=640&height=662');
		$details = sprintf(
			'<a href="%1$s" class="thickbox open-plugin-details-modal" aria-label="%2$s">%3$s</a>',
			esc_url( $details_url ),
			/* translators: 1: plugin name, 2: version number */
			esc_attr( sprintf( __( 'View %1$s version %2$s details' ), $plugin_data->Name, $plugin_data->update->new_version ) ),
			/* translators: %s: plugin version */
			sprintf( __( 'View version %s details.' ), $plugin_data->update->new_version )
		);

		$checkbox_id = "checkbox_" . md5( $plugin_data->Name );
		?>
		<tr>
			<td class="check-column">
				<input type="checkbox" name="checked[]" id="<?php echo $checkbox_id; ?>" value="<?php echo esc_attr( $plugin_file ); ?>" />
				<label for="<?php echo $checkbox_id; ?>" class="screen-reader-text"><?php
					/* translators: %s: plugin name */
					printf( __( 'Select %s' ),
						$plugin_data->Name
					);
				?></label>
			</td>
			<td class="plugin-title"><p>
				<strong><?php echo $plugin_data->Name; ?></strong>
				<?php
					/* translators: 1: plugin version, 2: new version */
					printf( __( 'You have version %1$s installed. Update to %2$s.' ),
						$plugin_data->Version,
						$plugin_data->update->new_version
					);
					echo ' ' . $details . $compat . $upgrade_notice;
				?>
			</p></td>
		</tr>
		<?php
	}
?>
	</tbody>

	<tfoot>
	<tr>
		<td class="manage-column check-column"><input type="checkbox" id="plugins-select-all-2" /></td>
		<td class="manage-column"><label for="plugins-select-all-2"><?php _e( 'Select All' ); ?></label></td>
	</tr>
	</tfoot>
</table>
<p><input id="upgrade-plugins-2" class="button" type="submit" value="<?php esc_attr_e('Update Plugins'); ?>" name="upgrade" /></p>
</form>
<?php
}

/**
 * @since 2.9.0
 */
function list_theme_updates() {
	$themes = get_theme_updates();
	if ( empty( $themes ) ) {
		echo '<h2>' . __( 'Themes' ) . '</h2>';
		echo '<p>' . __( 'Your themes are all up to date.' ) . '</p>';
		return;
	}

	$form_action = 'update-core.php?action=do-theme-upgrade';
?>
<h2><?php _e( 'Themes' ); ?></h2>
<p><?php _e( 'The following themes have new versions available. Check the ones you want to update and then click &#8220;Update Themes&#8221;.' ); ?></p>
<p><?php printf( __( '<strong>Please Note:</strong> Any customizations you have made to theme files will be lost. Please consider using <a href="%s">child themes</a> for modifications.' ), __( 'https://codex.wordpress.org/Child_Themes' ) ); ?></p>
<form method="post" action="<?php echo esc_url( $form_action ); ?>" name="upgrade-themes" class="upgrade">
<?php wp_nonce_field('upgrade-core'); ?>
<p><input id="upgrade-themes" class="button" type="submit" value="<?php esc_attr_e('Update Themes'); ?>" name="upgrade" /></p>
<table class="widefat updates-table" id="update-themes-table">
	<thead>
	<tr>
		<td class="manage-column check-column"><input type="checkbox" id="themes-select-all" /></td>
		<td class="manage-column"><label for="themes-select-all"><?php _e( 'Select All' ); ?></label></td>
	</tr>
	</thead>

	<tbody class="plugins">
<?php
	foreach ( $themes as $stylesheet => $theme ) {
		$checkbox_id = 'checkbox_' . md5( $theme->get( 'Name' ) );
		?>
		<tr>
			<td class="check-column">
				<input type="checkbox" name="checked[]" id="<?php echo $checkbox_id; ?>" value="<?php echo esc_attr( $stylesheet ); ?>" />
				<label for="<?php echo $checkbox_id; ?>" class="screen-reader-text"><?php
					/* translators: %s: theme name */
					printf( __( 'Select %s' ),
						$theme->display( 'Name' )
					);
				?></label>
			</td>
			<td class="plugin-title"><p>
				<img src="<?php echo esc_url( $theme->get_screenshot() ); ?>" width="85" height="64" class="updates-table-screenshot" alt="" />
				<strong><?php echo $theme->display( 'Name' ); ?></strong>
				<?php
					/* translators: 1: theme version, 2: new version */
					printf( __( 'You have version %1$s installed. Update to %2$s.' ),
						$theme->display( 'Version' ),
						$theme->update['new_version']
					);
				?>
			</p></td>
		</tr>
		<?php
	}
?>
	</tbody>

	<tfoot>
	<tr>
		<td class="manage-column check-column"><input type="checkbox" id="themes-select-all-2" /></td>
		<td class="manage-column"><label for="themes-select-all-2"><?php _e( 'Select All' ); ?></label></td>
	</tr>
	</tfoot>
</table>
<p><input id="upgrade-themes-2" class="button" type="submit" value="<?php esc_attr_e('Update Themes'); ?>" name="upgrade" /></p>
</form>
<?php
}

/**
 * @since 3.7.0
 */
function list_translation_updates() {
	$updates = wp_get_translation_updates();
	if ( ! $updates ) {
		if ( 'en_US' != get_locale() ) {
			echo '<h2>' . __( 'Translations' ) . '</h2>';
			echo '<p>' . __( 'Your translations are all up to date.' ) . '</p>';
		}
		return;
	}

	$form_action = 'update-core.php?action=do-translation-upgrade';
	?>
	<h2><?php _e( 'Translations' ); ?></h2>
	<form method="post" action="<?php echo esc_url( $form_action ); ?>" name="upgrade-translations" class="upgrade">
		<p><?php _e( 'New translations are available.' ); ?></p>
		<?php wp_nonce_field( 'upgrade-translations' ); ?>
		<p><input class="button" type="submit" value="<?php esc_attr_e( 'Update Translations' ); ?>" name="upgrade" /></p>
	</form>
	<?php
}

/**
 * Upgrade WordPress core display.
 *
 * @since 2.7.0
 *
 * @global WP_Filesystem_Base $wp_filesystem Subclass
 *
 * @param bool $reinstall
 */
function do_core_upgrade( $reinstall = false ) {
	global $wp_filesystem;

	if ( $reinstall )
		$url = 'update-core.php?action=do-core-reinstall';
	else
		$url = 'update-core.php?action=do-core-upgrade';
	$url = wp_nonce_url($url, 'upgrade-core');

	$app = getApp();
	$_post = $app['request']->request;
	$version = $_post->get( 'version', false );
	$locale = $_post->get( 'locale', 'en_US' );
	$update = find_core_update( $version, $locale );
	if ( !$update )
		return;

	// Allow relaxed file ownership writes for User-initiated upgrades when the API specifies
	// that it's safe to do so. This only happens when there are no new files to create.
	$allow_relaxed_file_ownership = ! $reinstall && isset( $update->new_files ) && ! $update->new_files;

?>
	<div class="wrap">
	<h1><?php _e( 'Update WordPress' ); ?></h1>
<?php

	if ( false === ( $credentials = request_filesystem_credentials( $url, '', false, ABSPATH, array( 'version', 'locale' ), $allow_relaxed_file_ownership ) ) ) {
		echo '</div>';
		return;
	}

	if ( ! WP_Filesystem( $credentials, ABSPATH, $allow_relaxed_file_ownership ) ) {
		// Failed to connect, Error and request again
		request_filesystem_credentials( $url, '', true, ABSPATH, array( 'version', 'locale' ), $allow_relaxed_file_ownership );
		echo '</div>';
		return;
	}

	if ( $wp_filesystem->errors->get_error_code() ) {
		foreach ( $wp_filesystem->errors->get_error_messages() as $message )
			show_message($message);
		echo '</div>';
		return;
	}

	if ( $reinstall )
		$update->response = 'reinstall';

	add_filter( 'update_feedback', 'show_message' );

	$upgrader = new Core_Upgrader();
	$result = $upgrader->upgrade( $update, array(
		'allow_relaxed_file_ownership' => $allow_relaxed_file_ownership
	) );

	if ( is_wp_error($result) ) {
		show_message($result);
		if ( 'up_to_date' != $result->get_error_code() && 'locked' != $result->get_error_code() )
			show_message( __('Installation Failed') );
		echo '</div>';
		return;
	}

	show_message( __('WordPress updated successfully') );
	show_message( '<span class="hide-if-no-js">' . sprintf( __( 'Welcome to WordPress %1$s. You will be redirected to the About WordPress screen. If not, click <a href="%2$s">here</a>.' ), $result, esc_url( self_admin_url( 'about.php?updated' ) ) ) . '</span>' );
	show_message( '<span class="hide-if-js">' . sprintf( __( 'Welcome to WordPress %1$s. <a href="%2$s">Learn more</a>.' ), $result, esc_url( self_admin_url( 'about.php?updated' ) ) ) . '</span>' );
	?>
	</div>
	<script type="text/javascript">
	window.location = '<?php echo self_admin_url( 'about.php?updated' ); ?>';
	</script>
	<?php
}

/**
 * @since 2.7.0
 */
function do_dismiss_core_update() {
	$app = getApp();
	$_post = $app['request']->request;
	$version = $_post->get( 'version', false );
	$locale = $_post->get( 'locale', 'en_US' );
	$update = find_core_update( $version, $locale );
	if ( ! $update ) {
		return;
	}
	dismiss_core_update( $update );
	wp_redirect( wp_nonce_url('update-core.php?action=upgrade-core', 'upgrade-core') );
	exit;
}

/**
 * @since 2.7.0
 */
function do_undismiss_core_update() {
	$app = getApp();
	$_post = $app['request']->request;
	$version = $_post->get( 'version', false );
	$locale = $_post->get( 'locale', 'en_US' );
	$update = find_core_update( $version, $locale );
	if ( ! $update ) {
		return;
	}
	undismiss_core_update( $version, $locale );
	wp_redirect( wp_nonce_url('update-core.php?action=upgrade-core', 'upgrade-core') );
	exit;
}
