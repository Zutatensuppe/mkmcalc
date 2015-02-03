<?php


class Controller {

	protected $dispatcher = null;

	public function __construct( Dispatcher $dispatcher) {
		$this->dispatcher = $dispatcher;
	}
}