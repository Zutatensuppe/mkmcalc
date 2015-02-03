<?php


class Template {


	private $vars = null;
	private $template = null;

	public function __construct( $template ) {
		$this->vars = new stdClass;
		$this->template = $template;
	}


	public function assign($key, $value) {
		$this->vars->{$key} = $value;
	}



	public function render() {
		ob_start();
		include TEMPLATE_DIR .'/'.$this->template.'.phtml';
		return ob_get_clean();
	}


}