<?php

define('TMP_DIR', realpath(__DIR__.'/../tmp'));
define('INCLUDE_DIR', realpath(__DIR__.'/../include'));
define('WWW_DIR', realpath(__DIR__.'/../www'));
define('TEMPLATE_DIR', INCLUDE_DIR.'/templates');

require_once INCLUDE_DIR.'/classes/autoload.php';

require_once __DIR__.'/config.php';



Config::set('siteurl', SITEURL);

$routes = array(
	array(
		'pattern' => '%^'.preg_quote(Config::get('siteurl'), '%').'/lists/(\d+)/calculate/%',
		'controller' => 'ListsController',
		'action' => 'calcAction',
	),
	array(
		'pattern' => '%^'.preg_quote(Config::get('siteurl'), '%').'/lists/(\d+)/%',
		'controller' => 'ListsController',
		'action' => 'indexAction',
	),

	array(
		'pattern' => '%^.*$%',
		'controller' => 'IndexController',
	),
);

Config::set('routes', $routes);
