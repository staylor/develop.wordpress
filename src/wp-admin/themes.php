<?php
/**
 * Themes administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */

use WP\Theme\Admin\Help as ThemeHelp;

/** WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

if ( ! current_user_can( 'switch_themes' ) && ! current_user_can( 'edit_theme_options' ) ) {
	wp_die(
		'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
		'<p>' . __( 'Sorry, you are not allowed to edit theme options on this site.' ) . '</p>',
		403
	);
}

if ( current_user_can( 'switch_themes' ) && $_get->get( 'action' ) ) {
	if ( 'activate' == $_get->get( 'action' ) ) {
		check_admin_referer('switch-theme_' . $_get->get( 'stylesheet' ) );
		$theme = wp_get_theme( $_get->get( 'stylesheet' ) );

		if ( ! $theme->exists() || ! $theme->is_allowed() ) {
			wp_die(
				'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
				'<p>' . __( 'The requested theme does not exist.' ) . '</p>',
				403
			);
		}

		switch_theme( $theme->get_stylesheet() );
		wp_redirect( admin_url('themes.php?activated=true') );
		exit;
	} elseif ( 'delete' == $_get->get( 'action' ) ) {
		check_admin_referer('delete-theme_' . $_get->get( 'stylesheet' ) );
		$theme = wp_get_theme( $_get->get( 'stylesheet' ) );

		if ( ! current_user_can( 'delete_themes' ) ) {
			wp_die(
				'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
				'<p>' . __( 'Sorry, you are not allowed to delete this item.' ) . '</p>',
				403
			);
		}

		if ( ! $theme->exists() ) {
			wp_die(
				'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
				'<p>' . __( 'The requested theme does not exist.' ) . '</p>',
				403
			);
		}

		$active = wp_get_theme();
		if ( $active->get( 'Template' ) == $_get->get( 'stylesheet' ) ) {
			wp_redirect( admin_url( 'themes.php?delete-active-child=true' ) );
		} else {
			delete_theme( $_get->get( 'stylesheet' ) );
			wp_redirect( admin_url( 'themes.php?deleted=true' ) );
		}
		exit;
	}
}

$app->set( 'title', __( 'Manage Themes' ) );
$app->set( 'parent_file', 'themes.php' );
$app->current_screen->set_parentage( $app->get( 'parent_file' ) );

( new ThemeHelp( get_current_screen() ) )->addMain();

if ( current_user_can( 'switch_themes' ) ) {
	$themes = wp_prepare_themes_for_js();
} else {
	$themes = wp_prepare_themes_for_js( array( wp_get_theme() ) );
}
wp_reset_vars( array( 'theme', 'search' ) );

wp_localize_script( 'theme', '_wpThemeSettings', array(
	'themes'   => $themes,
	'settings' => array(
		'canInstall'    => ( ! is_multisite() && current_user_can( 'install_themes' ) ),
		'installURI'    => ( ! is_multisite() && current_user_can( 'install_themes' ) ) ? admin_url( 'theme-install.php' ) : null,
		'confirmDelete' => __( "Are you sure you want to delete this theme?\n\nClick 'Cancel' to go back, 'OK' to confirm the delete." ),
		'adminUrl'      => parse_url( admin_url(), PHP_URL_PATH ),
	),
 	'l10n' => array(
 		'addNew'            => __( 'Add New Theme' ),
 		'search'            => __( 'Search Installed Themes' ),
 		'searchPlaceholder' => __( 'Search installed themes...' ), // placeholder (no ellipsis)
		'themesFound'       => __( 'Number of Themes found: %d' ),
		'noThemesFound'     => __( 'No themes found. Try a different search.' ),
  	),
) );

add_thickbox();
wp_enqueue_script( 'theme' );
wp_enqueue_script( 'updates' );
wp_enqueue_script( 'customize-loader' );

require_once( ABSPATH . 'wp-admin/admin-header.php' );
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Themes' ); ?>
		<span class="title-count theme-count"><?php echo count( $themes ); ?></span>
	<?php if ( ! is_multisite() && current_user_can( 'install_themes' ) ) : ?>
		<a href="<?php echo admin_url( 'theme-install.php' ); ?>" class="hide-if-no-js page-title-action"><?php echo esc_html_x( 'Add New', 'Add new theme' ); ?></a>
	<?php endif; ?>
	</h1>
<?php
if ( ! validate_current_theme() || $_get->get( 'broken' ) ) : ?>
<div id="message1" class="updated notice is-dismissible"><p><?php _e('The active theme is broken. Reverting to the default theme.'); ?></p></div>
<?php elseif ( $_get->get( 'activated' ) ) :
		if ( $_get->get( 'previewed' ) ) { ?>
		<div id="message2" class="updated notice is-dismissible"><p><?php _e( 'Settings saved and theme activated.' ); ?> <a href="<?php echo home_url( '/' ); ?>"><?php _e( 'Visit site' ); ?></a></p></div>
		<?php } else { ?>
<div id="message2" class="updated notice is-dismissible"><p><?php _e( 'New theme activated.' ); ?> <a href="<?php echo home_url( '/' ); ?>"><?php _e( 'Visit site' ); ?></a></p></div><?php
		}
	elseif ( $_get->get( 'deleted' ) ) : ?>
<div id="message3" class="updated notice is-dismissible"><p><?php _e('Theme deleted.') ?></p></div>
<?php elseif ( $_get->get( 'delete-active-child' ) ) : ?>
	<div id="message4" class="error"><p><?php _e( 'You cannot delete a theme while it has an active child theme.' ); ?></p></div>
<?php
endif;

$ct = wp_get_theme();

if ( $ct->errors() && ( ! is_multisite() || current_user_can( 'manage_network_themes' ) ) ) {
	echo '<div class="error"><p>' . sprintf( __( 'ERROR: %s' ), $ct->errors()->get_error_message() ) . '</p></div>';
}

/*
// Certain error codes are less fatal than others. We can still display theme information in most cases.
if ( ! $ct->errors() || ( 1 == count( $ct->errors()->get_error_codes() )
	&& in_array( $ct->errors()->get_error_code(), array( 'theme_no_parent', 'theme_parent_invalid', 'theme_no_index' ) ) ) ) : ?>
*/

	$parent_file = $app->get( 'parent_file' );
	// Pretend you didn't see this.
	$current_theme_actions = [];
	if ( is_array( $app->submenu ) && isset( $app->submenu['themes.php'] ) ) {
		foreach ( (array) $app->submenu['themes.php'] as $item) {
			$class = '';
			if ( 'themes.php' == $item[2] || 'theme-editor.php' == $item[2] || 0 === strpos( $item[2], 'customize.php' ) )
				continue;
			// 0 = name, 1 = capability, 2 = file
			if ( ( strcmp($self, $item[2]) == 0 && ! $parent_file ) || ( $parent_file && ( $item[2] === $parent_file ) ) ) {
				$class = ' current';
			}
			if ( ! empty( $app->submenu[ $item[2] ] ) ) {
				$app->submenu[$item[2]] = array_values($app->submenu[$item[2]]); // Re-index.
				$menu_hook = get_plugin_page_hook($app->submenu[$item[2]][0][2], $item[2]);
				if ( file_exists(WP_PLUGIN_DIR . "/{$app->submenu[$item[2]][0][2]}") || !empty($menu_hook)) {
					$current_theme_actions[] = "<a class='button$class' href='admin.php?page={$app->submenu[$item[2]][0][2]}'>{$item[0]}</a>";
				} else {
					$current_theme_actions[] = "<a class='button$class' href='{$app->submenu[$item[2]][0][2]}'>{$item[0]}</a>";
				}
			} elseif ( ! empty( $item[2] ) && current_user_can( $item[1] ) ) {
				$menu_file = $item[2];

				if ( current_user_can( 'customize' ) ) {
					if ( 'custom-header' === $menu_file ) {
						$current_theme_actions[] = "<a class='button hide-if-no-customize$class' href='customize.php?autofocus[control]=header_image'>{$item[0]}</a>";
					} elseif ( 'custom-background' === $menu_file ) {
						$current_theme_actions[] = "<a class='button hide-if-no-customize$class' href='customize.php?autofocus[control]=background_image'>{$item[0]}</a>";
					}
				}

				if ( false !== ( $pos = strpos( $menu_file, '?' ) ) ) {
					$menu_file = substr( $menu_file, 0, $pos );
				}

				if ( file_exists( ABSPATH . "wp-admin/$menu_file" ) ) {
					$current_theme_actions[] = "<a class='button$class' href='{$item[2]}'>{$item[0]}</a>";
				} else {
					$current_theme_actions[] = "<a class='button$class' href='themes.php?page={$item[2]}'>{$item[0]}</a>";
				}
			}
		}
	}

?>

<div class="theme-browser">
	<div class="themes wp-clearfix">

<?php
/*
 * This PHP is synchronized with the tmpl-theme template below!
 */

foreach ( $themes as $theme ) :
	$aria_action = esc_attr( $theme['id'] . '-action' );
	$aria_name   = esc_attr( $theme['id'] . '-name' );
	?>
<div class="theme<?php if ( $theme['active'] ) echo ' active'; ?>" tabindex="0" aria-describedby="<?php echo $aria_action . ' ' . $aria_name; ?>">
	<?php if ( ! empty( $theme['screenshot'][0] ) ) { ?>
		<div class="theme-screenshot">
			<img src="<?php echo $theme['screenshot'][0]; ?>" alt="" />
		</div>
	<?php } else { ?>
		<div class="theme-screenshot blank"></div>
	<?php } ?>

	<?php if ( $theme['hasUpdate'] ) : ?>
		<div class="update-message notice inline notice-warning notice-alt">
			<p><?php _e( 'New version available. <button class="button-link" type="button">Update now</button>' ); ?></p>
		</div>
	<?php endif; ?>

	<span class="more-details" id="<?php echo $aria_action; ?>"><?php _e( 'Theme Details' ); ?></span>
	<div class="theme-author"><?php printf( __( 'By %s' ), $theme['author'] ); ?></div>

	<?php if ( $theme['active'] ) { ?>
		<h2 class="theme-name" id="<?php echo $aria_name; ?>">
			<?php
			/* translators: %s: theme name */
			printf( __( '<span>Active:</span> %s' ), $theme['name'] );
			?>
		</h2>
	<?php } else { ?>
		<h2 class="theme-name" id="<?php echo $aria_name; ?>"><?php echo $theme['name']; ?></h2>
	<?php } ?>

	<div class="theme-actions">

	<?php if ( $theme['active'] ) { ?>
		<?php if ( $theme['actions']['customize'] && current_user_can( 'edit_theme_options' ) && current_user_can( 'customize' ) ) { ?>
			<a class="button button-primary customize load-customize hide-if-no-customize" href="<?php echo $theme['actions']['customize']; ?>"><?php _e( 'Customize' ); ?></a>
		<?php } ?>
	<?php } else { ?>
		<?php
		/* translators: %s: Theme name */
		$aria_label = sprintf( _x( 'Activate %s', 'theme' ), '{{ data.name }}' );
		?>
		<a class="button activate" href="<?php echo $theme['actions']['activate']; ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>"><?php _e( 'Activate' ); ?></a>
		<?php if ( current_user_can( 'edit_theme_options' ) && current_user_can( 'customize' ) ) { ?>
			<a class="button button-primary load-customize hide-if-no-customize" href="<?php echo $theme['actions']['customize']; ?>"><?php _e( 'Live Preview' ); ?></a>
		<?php } ?>
	<?php } ?>

	</div>
</div>
<?php endforeach; ?>
	</div>
</div>
<div class="theme-overlay"></div>

<p class="no-themes"><?php _e( 'No themes found. Try a different search.' ); ?></p>

<?php
// List broken themes, if any.
if ( ! is_multisite() && current_user_can('edit_themes') && $broken_themes = wp_get_themes( array( 'errors' => true ) ) ) {
?>

<div class="broken-themes">
<h3><?php _e('Broken Themes'); ?></h3>
<p><?php _e( 'The following themes are installed but incomplete.' ); ?></p>

<?php
$can_delete = current_user_can( 'delete_themes' );
$can_install = current_user_can( 'install_themes' );
?>
<table>
	<tr>
		<th><?php _ex('Name', 'theme name'); ?></th>
		<th><?php _e('Description'); ?></th>
		<?php if ( $can_delete ) { ?>
			<td></td>
		<?php } ?>
		<?php if ( $can_install ) { ?>
			<td></td>
		<?php } ?>
	</tr>
	<?php foreach ( $broken_themes as $broken_theme ) : ?>
		<tr>
			<td><?php echo $broken_theme->get( 'Name' ) ? $broken_theme->display( 'Name' ) : $broken_theme->get_stylesheet(); ?></td>
			<td><?php echo $broken_theme->errors()->get_error_message(); ?></td>
			<?php
			if ( $can_delete ) {
				$stylesheet = $broken_theme->get_stylesheet();
				$delete_url = add_query_arg( array(
					'action'     => 'delete',
					'stylesheet' => urlencode( $stylesheet ),
				), admin_url( 'themes.php' ) );
				$delete_url = wp_nonce_url( $delete_url, 'delete-theme_' . $stylesheet );
				?>
				<td><a href="<?php echo esc_url( $delete_url ); ?>" class="button delete-theme"><?php _e( 'Delete' ); ?></a></td>
				<?php
			}

			if ( $can_install && 'theme_no_parent' === $broken_theme->errors()->get_error_code() ) {
				$parent_theme_name = $broken_theme->get( 'Template' );
				$parent_theme = themes_api( 'theme_information', array( 'slug' => urlencode( $parent_theme_name ) ) );

				if ( ! is_wp_error( $parent_theme ) ) {
					$install_url = add_query_arg( array(
						'action' => 'install-theme',
						'theme'  => urlencode( $parent_theme_name ),
					), admin_url( 'update.php' ) );
					$install_url = wp_nonce_url( $install_url, 'install-theme_' . $parent_theme_name );
					?>
					<td><a href="<?php echo esc_url( $install_url ); ?>" class="button install-theme"><?php _e( 'Install Parent Theme' ); ?></a></td>
					<?php
				}
			}
			?>
		</tr>
	<?php endforeach; ?>
</table>
</div>

<?php
}
?>
</div><!-- .wrap -->

<?php
/*
 * The tmpl-theme template is synchronized with PHP above!
 */
?>
<script id="tmpl-theme" type="text/template">
	<# if ( data.screenshot[0] ) { #>
		<div class="theme-screenshot">
			<img src="{{ data.screenshot[0] }}" alt="" />
		</div>
	<# } else { #>
		<div class="theme-screenshot blank"></div>
	<# } #>

	<# if ( data.hasUpdate ) { #>
		<# if ( data.hasPackage ) { #>
			<div class="update-message notice inline notice-warning notice-alt"><p><?php _e( 'New version available. <button class="button-link" type="button">Update now</button>' ); ?></p></div>
		<# } else { #>
			<div class="update-message notice inline notice-warning notice-alt"><p><?php _e( 'New version available.' ); ?></p></div>
		<# } #>
	<# } #>

	<span class="more-details" id="{{ data.id }}-action"><?php _e( 'Theme Details' ); ?></span>
	<div class="theme-author">
		<?php
		/* translators: %s: Theme author name */
		printf( __( 'By %s' ), '{{{ data.author }}}' );
		?>
	</div>

	<# if ( data.active ) { #>
		<h2 class="theme-name" id="{{ data.id }}-name">
			<?php
			/* translators: %s: Theme name */
			printf( __( '<span>Active:</span> %s' ), '{{{ data.name }}}' );
			?>
		</h2>
	<# } else { #>
		<h2 class="theme-name" id="{{ data.id }}-name">{{{ data.name }}}</h2>
	<# } #>

	<div class="theme-actions">
		<# if ( data.active ) { #>
			<# if ( data.actions.customize ) { #>
				<a class="button button-primary customize load-customize hide-if-no-customize" href="{{{ data.actions.customize }}}"><?php _e( 'Customize' ); ?></a>
			<# } #>
		<# } else { #>
			<?php
			/* translators: %s: Theme name */
			$aria_label = sprintf( _x( 'Activate %s', 'theme' ), '{{ data.name }}' );
			?>
			<a class="button activate" href="{{{ data.actions.activate }}}" aria-label="<?php echo $aria_label; ?>"><?php _e( 'Activate' ); ?></a>
			<a class="button button-primary load-customize hide-if-no-customize" href="{{{ data.actions.customize }}}"><?php _e( 'Live Preview' ); ?></a>
		<# } #>
	</div>
</script>

<script id="tmpl-theme-single" type="text/template">
	<div class="theme-backdrop"></div>
	<div class="theme-wrap wp-clearfix">
		<div class="theme-header">
			<button class="left dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Show previous theme' ); ?></span></button>
			<button class="right dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Show next theme' ); ?></span></button>
			<button class="close dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Close details dialog' ); ?></span></button>
		</div>
		<div class="theme-about wp-clearfix">
			<div class="theme-screenshots">
			<# if ( data.screenshot[0] ) { #>
				<div class="screenshot"><img src="{{ data.screenshot[0] }}" alt="" /></div>
			<# } else { #>
				<div class="screenshot blank"></div>
			<# } #>
			</div>

			<div class="theme-info">
				<# if ( data.active ) { #>
					<span class="current-label"><?php _e( 'Current Theme' ); ?></span>
				<# } #>
				<h2 class="theme-name">{{{ data.name }}}<span class="theme-version"><?php printf( __( 'Version: %s' ), '{{ data.version }}' ); ?></span></h2>
				<p class="theme-author"><?php printf( __( 'By %s' ), '{{{ data.authorAndUri }}}' ); ?></p>

				<# if ( data.hasUpdate ) { #>
				<div class="notice notice-warning notice-alt notice-large">
					<h3 class="notice-title"><?php _e( 'Update Available' ); ?></h3>
					{{{ data.update }}}
				</div>
				<# } #>
				<p class="theme-description">{{{ data.description }}}</p>

				<# if ( data.parent ) { #>
					<p class="parent-theme"><?php printf( __( 'This is a child theme of %s.' ), '<strong>{{{ data.parent }}}</strong>' ); ?></p>
				<# } #>

				<# if ( data.tags ) { #>
					<p class="theme-tags"><span><?php _e( 'Tags:' ); ?></span> {{{ data.tags }}}</p>
				<# } #>
			</div>
		</div>

		<div class="theme-actions">
			<div class="active-theme">
				<a href="{{{ data.actions.customize }}}" class="button button-primary customize load-customize hide-if-no-customize"><?php _e( 'Customize' ); ?></a>
				<?php echo implode( ' ', $current_theme_actions ); ?>
			</div>
			<div class="inactive-theme">
				<?php
				/* translators: %s: Theme name */
				$aria_label = sprintf( _x( 'Activate %s', 'theme' ), '{{ data.name }}' );
				?>
				<# if ( data.actions.activate ) { #>
					<a href="{{{ data.actions.activate }}}" class="button activate" aria-label="<?php echo $aria_label; ?>"><?php _e( 'Activate' ); ?></a>
				<# } #>
				<a href="{{{ data.actions.customize }}}" class="button button-primary load-customize hide-if-no-customize"><?php _e( 'Live Preview' ); ?></a>
			</div>

			<# if ( ! data.active && data.actions['delete'] ) { #>
				<a href="{{{ data.actions['delete'] }}}" class="button delete-theme"><?php _e( 'Delete' ); ?></a>
			<# } #>
		</div>
	</div>
</script>

<?php
wp_print_request_filesystem_credentials_modal();
wp_print_admin_notice_templates();
wp_print_update_row_templates();

wp_localize_script( 'updates', '_wpUpdatesItemCounts', array(
	'totals'  => wp_get_update_data(),
) );

require( ABSPATH . 'wp-admin/admin-footer.php' );
