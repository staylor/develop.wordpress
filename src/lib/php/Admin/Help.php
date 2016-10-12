<?php
namespace WP\Admin;

abstract class Help {
	protected $screen;

	public function __construct( Screen $screen ) {
		$this->screen = $screen;
	}
}