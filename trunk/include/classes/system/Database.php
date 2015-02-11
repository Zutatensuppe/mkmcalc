<?php

namespace system;

class Database {

	/* Instantiation
	=============================== */

	private static $instances = array();
	private static $current_key = '__default__';

	public static function instance( $key = '__default__', array $settings = array() ) {

		self::$current_key = $key;

		if ( empty(self::$instances[self::$current_key]) ) {
			self::$instances[self::$current_key] = new self($settings);
		}

		return self::$instances[self::$current_key];

	}






	private
		$con = null
	;


	private function __construct( array $settings = array() ) {

		if ( isset($settings['host']) 
			&& isset($settings['user'])
			&& isset($settings['pass'])
			&& isset($settings['name'])
			) {

			$this->con = mysqli_connect($settings['host'], $settings['user'], $settings['pass'], $settings['name']);
			if ( !$this->con ) {
				throw new \Exception('Unable to connect to database');
			}

			$this->query('SET NAMES utf8');
			return $this;

		} else {
			throw new \Exception('Insufficiant settings. Please provide host, user, pass, name.');
		}
	}

	public function query($sql) {

		$res = mysqli_query($this->con, $sql);
		if ( $res === false ) {
			throw new \Exception('dbquery fail, SQL: '.$sql);
		} else {
			return $res;
		}

	}

	public function escape($string) {
		return mysqli_escape_string($this->con, $string);
	}

	public function getRow($sql) {
		$res = $this->query($sql);
		if ( $res === false ) {
			throw new \Exception('dbquery fail, SQL: '.$sql);
		} else {
			$row = mysqli_fetch_object($res);
			return $row;
		}
	}

	public function getRows($sql) {
		$rows = array();
		$res = $this->query($sql);
		if ( $res === false ) {
			throw new \Exception('dbquery fail, SQL: '.$sql);
		} else {
			while ( $row = mysqli_fetch_object($res) ) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

}