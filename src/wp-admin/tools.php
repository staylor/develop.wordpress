<?php
/**
 * Tools Administration Screen.
 *
 * @package WordPress
 * @subpackage Administration
 */
use WP\Admin\View as AdminView;
use WP\Tools\Admin\Help as ToolsHelp;

/** WordPress Administration Bootstrap */
require_once( __DIR__ . '/admin.php' );

$app->set( 'title', __( 'Tools' ) );

$view = new AdminView( $app );
$view->help = new ToolsHelp( get_current_screen() );
$view->help->addMain();

$cats = get_taxonomy( 'category' );
$tags = get_taxonomy( 'post_tag' );

$data = [
	'title' => $app->get( 'title' ),
	'caps' => [
		'edit_posts' => current_user_can( 'edit_posts' ),
		'import' => current_user_can( 'import' ) &&
			( current_user_can( $cats->cap->manage_terms ) || current_user_can( $tags->cap->manage_terms ) ),
	],
	'shortcut_link' => htmlspecialchars( get_shortcut_link() ),
	'press_this_url' => htmlspecialchars( admin_url( 'press-this.php' ) ),
	'import_text' => sprintf( $view->l10n->if_you_want_to_convert, 'import.php' ),
];

$view->setActions( [
	/**
	 * Fires at the end of the Tools Administration screen.
	 *
	 * @since 2.8.0
	 */
	'tool_box' => [],
] );

$view->setData( $data );

echo $view->render( 'admin/tools', $view );
