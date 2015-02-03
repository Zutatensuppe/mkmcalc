<?php


require_once __DIR__.'/util/ganon.php';
require_once __DIR__.'/util/phpQuery.php';
require_once __DIR__.'/util/functions.php';


function __mkmcalc_autoload( $classname ) {
	$try_files = array();
	$try_files[] = __DIR__.'/'.$classname.'.class.php';
	$try_files[] = __DIR__.'/'.$classname.'.php';
	$try_files[] = __DIR__.'/mkm/'.$classname.'.class.php';
	$try_files[] = __DIR__.'/mkm/'.$classname.'.php';


	$classname_array = explode('_', from_camel_case($classname));
	array_pop($classname_array);
	if ( !empty($classname_array) ) {
		$try_files[] = __DIR__.'/app/'.implode('/', $classname_array).'/'.$classname.'.class.php';
	}
	
	$try_files[] = __DIR__.'/system/'.strtolower($classname).'/'.$classname.'.class.php';
	$try_files[] = __DIR__.'/system/'.strtolower($classname).'/'.$classname.'.php';

	foreach ( $try_files as $try_file ) {
		if ( file_exists($try_file) ) {
			require_once $try_file;
		}
	}

}
spl_autoload_register('__mkmcalc_autoload');