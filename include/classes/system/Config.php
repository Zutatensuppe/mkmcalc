<?php
namespace system;

class Config {

	private static $config = array();

	public static function set( $configKey, $configValue ) {
		self::$config[$configKey] = $configValue;
	}

	public static function get( $configKey ) {
		return isset(self::$config[$configKey]) ? self::$config[$configKey] : null;
	}

}