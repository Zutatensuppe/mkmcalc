<?php
namespace system\abstr;

abstract class View {


	protected $vars = null;
	protected $template = null;

	public function __construct( $template ) {
		$this->vars = new \stdClass;
		$this->template = $template;
	}


	public function assign($key, $value) {
		$this->vars->{$key} = $value;
	}



	abstract function render();

}