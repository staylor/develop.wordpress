<?php
namespace WP\Admin;

abstract class Help {
	protected $screen;

	public function __construct() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			throw new HelpException( '"$screen" must be an instance of ' . Screen::class );
		}

		$this->screen = $screen;
	}

	protected function addOverview( $content ) {
		$this->screen->add_help_tab( [
			'id'      => 'overview',
			'title'   => __( 'Overview' ),
			'content' => $content
		] );
	}
}