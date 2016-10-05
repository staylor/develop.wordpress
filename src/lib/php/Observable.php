<?php
namespace WP;

abstract class Observable implements \SplSubject {
	protected $observers;

	public $message = [];

	public function attach( \SplObserver $observer ) {
		if ( ! $this->observers ) {
			$this->observers = new \SplObjectStorage();
		}
		$this->observers->attach( $observer );
	}

	public function detach( \SplObserver $observer ) {
		if ( ! $this->observers || ! $this->observers->contains( $observer ) ) {
			return;
		}

		$this->observers->detach( $observer );
	}

	public function notify() {
		if ( ! $this->observers ) {
			return;
		}

        foreach ( $this->observers as $observer ) {
            $observer->update( $this );
        }
	}
}
