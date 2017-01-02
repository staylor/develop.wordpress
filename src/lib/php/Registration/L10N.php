<?php
namespace WP\Registration;

use WP\Magic\Data;

class L10N {
	use Data;

	public function __construct() {
		$this->data = [
            'activate' => __('Activate'),
            'activation_key_required' => __('Activation Key Required'),
            'activation_key_label' => __('Activation Key:'),
            'now_active' => __('Your account is now active!'),
            'error_occurred' => __('An error occurred during the activation'),
            'username' => __('Username:'),
            'password' => __('Password:'),
        ];
    }
}
