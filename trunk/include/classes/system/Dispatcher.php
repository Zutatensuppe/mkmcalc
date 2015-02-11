<?php
namespace system;

class Dispatcher {

	private $url = '';
	private $vars = array();

	public function __construct( $url = null ) {

		if ( $url === null ) {
			$url = 'http'.(empty($_SERVER['HTTPS'])?'':'s').'://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		}

		$this->url = $url;

	}


	public function getVar( $index ) {
		return isset($this->vars[$index]) ? $this->vars[$index] : null;
	}


	public function dispatch() {


		$routes = Config::get('routes');
		if ( empty($routes) || !is_array($routes) ) {
			throw new Exception('NoRoutesException');
		}

		foreach ( $routes as $route ) {

			if ( preg_match($route['pattern'], $this->url, $matches) ) {

				$this->vars = $matches;

				$controller = new $route['controller']($this);
				$action = isset($route['action']) ? $route['action'] : 'indexAction';

				$controller->$action();
				break;
			}

		}

	}

	public function redirect($url) {
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: '.$url);
		exit();
	}

	public function getUrl() {
		return $this->url;
	}
}