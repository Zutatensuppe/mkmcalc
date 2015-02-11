<?php
namespace system\abstr;

use system\Dispatcher as Dispatcher;

abstract class Controller {

	protected $dispatcher = null;

	public function __construct( Dispatcher $dispatcher) {
		$this->dispatcher = $dispatcher;
	}
}