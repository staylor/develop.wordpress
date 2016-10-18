<?php
namespace WP\IXR;
/**
 * @package IXR
 * @since 1.5.0
 */
class Date {

	private $year;
	private $month;
	private $day;
	private $hour;
	private $minute;
	private $second;
	private $timezone;

	public function __construct( $time )
	{
		// $time can be a PHP timestamp or an ISO one
		if ( is_numeric( $time ) ) {
			$this->parseTimestamp( $time );
		} else {
			$this->parseIso( $time );
		}
	}

	private function parseTimestamp( $timestamp ) {
		$this->year = date( 'Y', $timestamp );
		$this->month = date( 'm', $timestamp );
		$this->day = date( 'd', $timestamp );
		$this->hour = date( 'H', $timestamp );
		$this->minute = date( 'i', $timestamp );
		$this->second = date( 's', $timestamp );
		$this->timezone = '';
	}

	private function parseIso( $iso ) {
		$this->year = substr( $iso, 0, 4 );
		$this->month = substr( $iso, 4, 2 );
		$this->day = substr( $iso, 6, 2 );
		$this->hour = substr( $iso, 9, 2 );
		$this->minute = substr( $iso, 12, 2 );
		$this->second = substr( $iso, 15, 2 );
		$this->timezone = substr( $iso, 17 );
	}

	public function getIso(): string
	{
		return join( '', [
			$this->year,
			$this->month,
			$this->day,
			'T',
			$this->hour,
			':',
			$this->minute,
			':',
			$this->second,
			$this->timezone,
		] );
	}

	public function getXml(): string
	{
		return '<dateTime.iso8601>' . $this->getIso() . '</dateTime.iso8601>';
	}

	public function getTimestamp(): int
	{
		return mktime(
			$this->hour,
			$this->minute,
			$this->second,
			$this->month,
			$this->day,
			$this->year
		);
	}
}
