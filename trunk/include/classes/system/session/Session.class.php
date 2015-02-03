<?php


class Session {


	public static function setUserId( $idUser ) {

		$uniqid = md5(uniqid() . mt_rand(0, 10000));
		self::set('idSession', $uniqid);
		$sql = '
			INSERT INTO
				`user_Session`
			SET
				`idUser` = '.(int)$idUser.',
				`idSession` = "'.MCalcUtil::dbescape(self::get('idSession')).'"
			ON DUPLICATE KEY UPDATE
				`idUser` = '.(int)$idUser.'
			;
		';
		MCalcUtil::dbquery($sql);
		
	}
	public static function getUserId() {
		if ( self::get('idSession') !== null ) {
			// try to load from session table
			$sql = '
				SELECT
					`idUser`
				FROM
					`user_Session`
				WHERE
					`idSession` = "'.MCalcUtil::dbescape(self::get('idSession')).'"
				;
			';
			$row = MCalcUtil::dbgetrow($sql);
			if ( !empty($row) ) {
				return (int)$row->idUser;
			}
		}
		return false;
	}



	public static function start() {
		session_start();
	}

	public static function destroy() {
		session_destroy();
	}


	public static function get($key) {
		return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
	}

	public static function set($key, $value) {

		$_SESSION[$key] = $value;

	}

	public static function delete($key) {
		unset($_SESSION[$key]);
	}


}