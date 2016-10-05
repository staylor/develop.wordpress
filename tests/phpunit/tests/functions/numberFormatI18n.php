<?php

/**
 * Tests for number_format_i18n()
 *
 * @group functions.php
 * @group i18n
 */
class Tests_Functions_Number_Format_I18n extends WP_UnitTestCase {
	public function test_should_fall_back_to_number_format_when_wp_locale_is_not_set() {
		unset( $this->app['locale'] );

		$actual_1 = number_format_i18n( 123456.789, 0 );
		$actual_2 = number_format_i18n( 123456.789, 4 );

		$this->app['locale'] = $this->app['locale.factory'];

		$this->assertEquals( '123,457', $actual_1 );
		$this->assertEquals( '123,456.7890', $actual_2 );
	}

	public function test_should_respect_number_format_of_locale() {
		$wp_locale = $this->app['locale'];

		$decimal_point = $wp_locale->number_format['decimal_point'];
		$thousands_sep = $wp_locale->number_format['thousands_sep'];

		$wp_locale->number_format['decimal_point'] = '@';
		$wp_locale->number_format['thousands_sep'] = '^';

		$actual_1 = number_format_i18n( 123456.789, 0 );
		$actual_2 = number_format_i18n( 123456.789, 4 );

		$wp_locale->number_format['decimal_point'] = $decimal_point;
		$wp_locale->number_format['thousands_sep'] = $thousands_sep;

		$this->assertEquals( '123^457', $actual_1 );
		$this->assertEquals( '123^456@7890', $actual_2 );
	}

	public function test_should_default_to_en_us_format() {
		$this->assertEquals( '123,457', number_format_i18n( 123456.789, 0 ) );
		$this->assertEquals( '123,456.7890', number_format_i18n( 123456.789, 4 ) );
	}

	public function test_should_handle_negative_precision() {
		$this->assertEquals( '123,457', number_format_i18n( 123456.789, 0 ) );
		$this->assertEquals( '123,456.7890', number_format_i18n( 123456.789, -4 ) );
	}
}
