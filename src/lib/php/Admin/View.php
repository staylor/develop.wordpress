<?php
namespace WP\Admin;

use WP\{App,View as BaseView};

class View extends BaseView {

	public $l10n;

	public function __construct( App $app ) {
		parent::__construct( $app );

		$this->l10n = new L10N();

		$this->setConfig( [
			'helpers' => [
				'l10n' => $this->l10n
			]
		] );
	}
}