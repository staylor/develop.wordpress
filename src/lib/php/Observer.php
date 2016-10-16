<?php
namespace WP;

abstract class Observer implements \SplObserver {
    abstract public function update( Observable $subject );
}
