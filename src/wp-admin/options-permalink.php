<?php
/**
 * Permalink Settings Administration Screen.
 *
 * @package WordPress
 * @subpackage Administration
 */

use WP\Option\Admin\Help as OptionHelp;

/** WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

if ( ! current_user_can( 'manage_options' ) )
	wp_die( __( 'Sorry, you are not allowed to manage options for this site.' ) );

$app->title = __('Permalink Settings');
$app->parent_file = 'options-general.php';
$app->current_screen->set_parentage( $app->parent_file );

( new OptionHelp( get_current_screen() ) )->addPermalink();

add_filter('admin_head', 'options_permalink_add_js');

$home_path = get_home_path();
$iis7_permalinks = iis7_supports_permalinks();
$permalink_structure = get_option( 'permalink_structure' );

$prefix = $blog_prefix = '';
if ( ! got_url_rewrite() )
	$prefix = '/index.php';

/**
 * In a subdirectory configuration of multisite, the `/blog` prefix is used by
 * default on the main site to avoid collisions with other sites created on that
 * network. If the `permalink_structure` option has been changed to remove this
 * base prefix, WordPress core can no longer account for the possible collision.
 */
if ( is_multisite() && ! is_subdomain_install() && is_main_site() && 0 === strpos( $permalink_structure, '/blog/' ) ) {
	$blog_prefix = '/blog';
}

$category_base       = get_option( 'category_base' );
$tag_base            = get_option( 'tag_base' );
$update_required     = false;

if ( $iis7_permalinks ) {
	if ( ( ! file_exists($home_path . 'web.config') && win_is_writable($home_path) ) || win_is_writable($home_path . 'web.config') )
		$writable = true;
	else
		$writable = false;
} elseif ( $app['is_nginx'] ) {
	$writable = false;
} else {
	if ( ( ! file_exists( $home_path . '.htaccess' ) && is_writable( $home_path ) ) || is_writable( $home_path . '.htaccess' ) ) {
		$writable = true;
	} else {
		$writable = false;
		$existing_rules  = array_filter( extract_from_markers( $home_path . '.htaccess', 'WordPress' ) );
		$new_rules       = array_filter( explode( "\n", $app['rewrite']->mod_rewrite_rules() ) );
		$update_required = ( $new_rules !== $existing_rules );
	}
}

$using_index_permalinks = $app['rewrite']->using_index_permalinks();

if ( $_post->get( 'permalink_structure' ) || $_post->get( 'category_base' ) ) {
	check_admin_referer('update-permalink');

	if ( $_post->get( 'permalink_structure' ) ) {
		if ( $_post->get( 'selection' ) && 'custom' != $_post->get( 'selection' ) )
			$permalink_structure = $_post->get( 'selection' );
		else
			$permalink_structure = $_post->get( 'permalink_structure' );

		if ( ! empty( $permalink_structure ) ) {
			$permalink_structure = preg_replace( '#/+#', '/', '/' . str_replace( '#', '', $permalink_structure ) );
			if ( $prefix && $blog_prefix )
				$permalink_structure = $prefix . preg_replace( '#^/?index\.php#', '', $permalink_structure );
			else
				$permalink_structure = $blog_prefix . $permalink_structure;
		}

		$permalink_structure = sanitize_option( 'permalink_structure', $permalink_structure );

		$app['rewrite']->set_permalink_structure( $permalink_structure );
	}

	if ( $_post->get( 'category_base' ) ) {
		$category_base = $_post->get( 'category_base' );
		if ( ! empty( $category_base ) )
			$category_base = $blog_prefix . preg_replace('#/+#', '/', '/' . str_replace( '#', '', $category_base ) );
		$app['rewrite']->set_category_base( $category_base );
	}

	if ( $_post->get( 'tag_base' ) ) {
		$tag_base = $_post->get( 'tag_base' );
		if ( ! empty( $tag_base ) )
			$tag_base = $blog_prefix . preg_replace('#/+#', '/', '/' . str_replace( '#', '', $tag_base ) );
		$app['rewrite']->set_tag_base( $tag_base );
	}

	$message = __( 'Permalink structure updated.' );

	if ( $iis7_permalinks ) {
		if ( $permalink_structure && ! $using_index_permalinks && ! $writable ) {
			$message = __( 'You should update your web.config now.' );
		} elseif ( $permalink_structure && ! $using_index_permalinks && $writable ) {
			$message = __( 'Permalink structure updated. Remove write access on web.config file now!' );
		}
	} elseif ( ! $app['is_nginx'] && $permalink_structure && ! $using_index_permalinks && ! $writable && $update_required ) {
		$message = __( 'You should update your .htaccess now.' );
	}

	if ( ! get_settings_errors() ) {
		add_settings_error( 'general', 'settings_updated', $message, 'updated' );
	}

	set_transient( 'settings_errors', get_settings_errors(), 30 );

	wp_redirect( admin_url( 'options-permalink.php?settings-updated=true' ) );
	exit;
}

flush_rewrite_rules();

require( ABSPATH . 'wp-admin/admin-header.php' );
?>
<div class="wrap">
<h1><?php echo esc_html( $app->title ); ?></h1>

<form name="form" action="options-permalink.php" method="post">
<?php wp_nonce_field('update-permalink') ?>

  <p><?php _e( 'WordPress offers you the ability to create a custom URL structure for your permalinks and archives. Custom URL structures can improve the aesthetics, usability, and forward-compatibility of your links. A <a href="https://codex.wordpress.org/Using_Permalinks">number of tags are available</a>, and here are some examples to get you started.' ); ?></p>

<?php
if ( is_multisite() && ! is_subdomain_install() && is_main_site() && 0 === strpos( $permalink_structure, '/blog/' ) ) {
	$permalink_structure = preg_replace( '|^/?blog|', '', $permalink_structure );
	$category_base = preg_replace( '|^/?blog|', '', $category_base );
	$tag_base = preg_replace( '|^/?blog|', '', $tag_base );
}

$structures = array(
	0 => '',
	1 => $prefix . '/%year%/%monthnum%/%day%/%postname%/',
	2 => $prefix . '/%year%/%monthnum%/%postname%/',
	3 => $prefix . '/' . _x( 'archives', 'sample permalink base' ) . '/%post_id%',
	4 => $prefix . '/%postname%/',
);
?>
<h2 class="title"><?php _e('Common Settings'); ?></h2>
<table class="form-table permalink-structure">
	<tr>
		<th><label><input name="selection" type="radio" value="" <?php checked('', $permalink_structure); ?> /> <?php _e( 'Plain' ); ?></label></th>
		<td><code><?php echo get_option('home'); ?>/?p=123</code></td>
	</tr>
	<tr>
		<th><label><input name="selection" type="radio" value="<?php echo esc_attr($structures[1]); ?>" <?php checked($structures[1], $permalink_structure); ?> /> <?php _e('Day and name'); ?></label></th>
		<td><code><?php echo get_option('home') . $blog_prefix . $prefix . '/' . date('Y') . '/' . date('m') . '/' . date('d') . '/' . _x( 'sample-post', 'sample permalink structure' ) . '/'; ?></code></td>
	</tr>
	<tr>
		<th><label><input name="selection" type="radio" value="<?php echo esc_attr($structures[2]); ?>" <?php checked($structures[2], $permalink_structure); ?> /> <?php _e('Month and name'); ?></label></th>
		<td><code><?php echo get_option('home') . $blog_prefix . $prefix . '/' . date('Y') . '/' . date('m') . '/' . _x( 'sample-post', 'sample permalink structure' ) . '/'; ?></code></td>
	</tr>
	<tr>
		<th><label><input name="selection" type="radio" value="<?php echo esc_attr($structures[3]); ?>" <?php checked($structures[3], $permalink_structure); ?> /> <?php _e('Numeric'); ?></label></th>
		<td><code><?php echo get_option('home') . $blog_prefix . $prefix . '/' . _x( 'archives', 'sample permalink base' ) . '/123'; ?></code></td>
	</tr>
	<tr>
		<th><label><input name="selection" type="radio" value="<?php echo esc_attr($structures[4]); ?>" <?php checked($structures[4], $permalink_structure); ?> /> <?php _e('Post name'); ?></label></th>
		<td><code><?php echo get_option('home') . $blog_prefix . $prefix . '/' . _x( 'sample-post', 'sample permalink structure' ) . '/'; ?></code></td>
	</tr>
	<tr>
		<th>
			<label><input name="selection" id="custom_selection" type="radio" value="custom" <?php checked( !in_array($permalink_structure, $structures) ); ?> />
			<?php _e('Custom Structure'); ?>
			</label>
		</th>
		<td>
			<code><?php echo get_option('home') . $blog_prefix; ?></code>
			<input name="permalink_structure" id="permalink_structure" type="text" value="<?php echo esc_attr($permalink_structure); ?>" class="regular-text code" />
		</td>
	</tr>
</table>

<h2 class="title"><?php _e('Optional'); ?></h2>
<p><?php
/* translators: %s is a placeholder that must come at the start of the URL. */
printf( __( 'If you like, you may enter custom structures for your category and tag URLs here. For example, using <code>topics</code> as your category base would make your category links like <code>%s/topics/uncategorized/</code>. If you leave these blank the defaults will be used.' ), get_option( 'home' ) . $blog_prefix . $prefix ); ?></p>

<table class="form-table">
	<tr>
		<th><label for="category_base"><?php /* translators: prefix for category permalinks */ _e('Category base'); ?></label></th>
		<td><?php echo $blog_prefix; ?> <input name="category_base" id="category_base" type="text" value="<?php echo esc_attr( $category_base ); ?>" class="regular-text code" /></td>
	</tr>
	<tr>
		<th><label for="tag_base"><?php _e('Tag base'); ?></label></th>
		<td><?php echo $blog_prefix; ?> <input name="tag_base" id="tag_base" type="text" value="<?php echo esc_attr($tag_base); ?>" class="regular-text code" /></td>
	</tr>
	<?php do_settings_fields('permalink', 'optional'); ?>
</table>

<?php do_settings_sections('permalink'); ?>

<?php submit_button(); ?>
  </form>
<?php if ( !is_multisite() ) { ?>
<?php if ( $iis7_permalinks ) :
	if ( $_post->get( 'submit' ) && $permalink_structure && ! $using_index_permalinks && ! $writable ) :
		if ( file_exists($home_path . 'web.config') ) : ?>
<p><?php _e('If your <code>web.config</code> file were <a href="https://codex.wordpress.org/Changing_File_Permissions">writable</a>, we could do this automatically, but it isn&#8217;t so this is the url rewrite rule you should have in your <code>web.config</code> file. Click in the field and press <kbd>CTRL + a</kbd> to select all. Then insert this rule inside of the <code>/&lt;configuration&gt;/&lt;system.webServer&gt;/&lt;rewrite&gt;/&lt;rules&gt;</code> element in <code>web.config</code> file.') ?></p>
<form action="options-permalink.php" method="post">
<?php wp_nonce_field('update-permalink') ?>
	<p><textarea rows="9" class="large-text readonly" name="rules" id="rules" readonly="readonly"><?php echo esc_textarea( $app['rewrite']->iis7_url_rewrite_rules() ); ?></textarea></p>
</form>
<p><?php _e('If you temporarily make your <code>web.config</code> file writable for us to generate rewrite rules automatically, do not forget to revert the permissions after rule has been saved.') ?></p>
		<?php else : ?>
<p><?php _e('If the root directory of your site were <a href="https://codex.wordpress.org/Changing_File_Permissions">writable</a>, we could do this automatically, but it isn&#8217;t so this is the url rewrite rule you should have in your <code>web.config</code> file. Create a new file, called <code>web.config</code> in the root directory of your site. Click in the field and press <kbd>CTRL + a</kbd> to select all. Then insert this code into the <code>web.config</code> file.') ?></p>
<form action="options-permalink.php" method="post">
<?php wp_nonce_field('update-permalink') ?>
	<p><textarea rows="18" class="large-text readonly" name="rules" id="rules" readonly="readonly"><?php echo esc_textarea( $app['rewrite']->iis7_url_rewrite_rules(true) ); ?></textarea></p>
</form>
<p><?php _e('If you temporarily make your site&#8217;s root directory writable for us to generate the <code>web.config</code> file automatically, do not forget to revert the permissions after the file has been created.') ?></p>
		<?php endif; ?>
	<?php endif; ?>
<?php elseif ( $app['is_nginx'] ) : ?>
	<p><?php _e( '<a href="https://codex.wordpress.org/Nginx">Documentation on Nginx configuration</a>.' ); ?></p>
<?php else:
	if ( $permalink_structure && ! $using_index_permalinks && ! $writable && $update_required ) : ?>
<p><?php _e('If your <code>.htaccess</code> file were <a href="https://codex.wordpress.org/Changing_File_Permissions">writable</a>, we could do this automatically, but it isn&#8217;t so these are the mod_rewrite rules you should have in your <code>.htaccess</code> file. Click in the field and press <kbd>CTRL + a</kbd> to select all.') ?></p>
<form action="options-permalink.php" method="post">
<?php wp_nonce_field('update-permalink') ?>
	<p><textarea rows="6" class="large-text readonly" name="rules" id="rules" readonly="readonly"><?php echo esc_textarea( $app['rewrite']->mod_rewrite_rules() ); ?></textarea></p>
</form>
	<?php endif; ?>
<?php endif; ?>
<?php } // multisite ?>

</div>

<?php require( ABSPATH . 'wp-admin/admin-footer.php' ); ?>
