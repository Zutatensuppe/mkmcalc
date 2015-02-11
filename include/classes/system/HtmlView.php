<?php

namespace system;

use system\abstr\View as AbstractView;

class HtmlView extends AbstractView {

	public function render() {
		ob_start();
		include TEMPLATE_DIR .'/'.$this->template.'.phtml';
		return ob_get_clean();
	}

}