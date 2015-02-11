<?php

require_once __DIR__.'/util/functions.php';


function __mkmcalc_autoload( $class ) {

	$try_files = array();


	$parts = explode('\\', $class);
	$end = end($parts);
	$try_files[] = __DIR__ .'/'. implode('/', $parts).'.php';
	foreach ( $try_files as $try_file ) {
		if ( file_exists($try_file) ) {
			require_once $try_file;
		}
	}

}
spl_autoload_register('__mkmcalc_autoload');