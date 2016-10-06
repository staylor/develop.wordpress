<?php
namespace WP\Admin;

use WP\{App,Mustache};

class View {
	use Mustache;

	protected $app;
	public $l10n;

	public function __construct( App $app ) {
		$this->app = $app;
		$this->l10n = new L10N();

		$this->setConfig( [
			'helpers' => [
				'l10n' => $this->l10n
			]
		] );
	}
}