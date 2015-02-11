<?php

define('TMP_DIR', realpath(__DIR__.'/../tmp'));
define('INCLUDE_DIR', realpath(__DIR__.'/../include'));
define('WWW_DIR', realpath(__DIR__.'/../www'));
define('TEMPLATE_DIR', INCLUDE_DIR.'/templates');

require_once INCLUDE_DIR.'/classes/autoload.php';

require_once __DIR__.'/config.php';


use system\Config as Config;
use system\Database as Database;

Config::set('siteurl', SITEURL);

$routes = array(
	array(
		'pattern' => '%^'.preg_quote(Config::get('siteurl'), '%').'/lists/(\d+)/calculate/%',
		'controller' => 'app\front\lists\Controller',
		'action' => 'calcAction',
	),
	array(
		'pattern' => '%^'.preg_quote(Config::get('siteurl'), '%').'/lists/(\d+)/%',
		'controller' => 'app\front\lists\Controller',
		'action' => 'indexAction',
	),

	array(
		'pattern' => '%^.*$%',
		'controller' => 'app\front\index\Controller',
	),
);

Config::set('routes', $routes);

Database::instance('__default__', array(
	'host' => DB_HOST,
	'user' => DB_USER,
	'pass' => DB_PASS,
	'name' => DB_NAME,
));