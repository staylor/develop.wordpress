<?php
namespace WP\Admin\View;

use WP\App;
use WP\Admin\View;
use WP\Dashboard\{Help,L10N};

class Dashboard extends View {
	public function __construct( App $app ) {
		parent::__construct( $app );

		$this->help = new Help();

		$this->setL10n( new L10N() );
	}

	public function enqueueIndexScripts() {
		wp_enqueue_script( 'dashboard' );
		if ( current_user_can( 'edit_theme_options' ) ) {
			wp_enqueue_script( 'customize-loader' );
		}
		if ( current_user_can( 'install_plugins' ) ) {
			wp_enqueue_script( 'plugin-install' );
			wp_enqueue_script( 'updates' );
		}
		if ( current_user_can( 'upload_files' ) ) {
			wp_enqueue_script( 'media-upload' );
		}
		add_thickbox();

		if ( wp_is_mobile() ) {
			wp_enqueue_script( 'jquery-touch-punch' );
		}
	}
}