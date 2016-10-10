<?php
namespace WP\Admin;

use WP\View as BaseView;

abstract class View extends BaseView {
	public $l10n;
	public $help;
	public $handler;

	public function setL10n( $l10n ) {
		$this->l10n = $l10n;

		$this->setConfig( [
			'helpers' => [
				'l10n' => $l10n
			]
		] );
	}
}