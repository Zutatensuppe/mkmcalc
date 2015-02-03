<?php




class Auth {

	private static $user = null; // User



	public static function user() {
		return self::$user;
	}


	public static function reset() {
		self::$user = null;
	}


	public static function checkSession() {

		$userId = Session::getUserId(); // user id aus der session wiederherstellen
		if ( !empty($userId) ) {
			// user autentifizieren (auth den user zuweisen...)
			$user = new User;
			$user->setId($userId);
			$user->load();
			Auth::authenticateUser($user);
		}

	}

	public static function authenticate( $loginId, $password) {
		$sql = '
			SELECT
				*
			FROM
				`user_User`
			WHERE
				`loginId` = "'.MCalcUtil::dbescape($loginId).'"
				AND
				`password` = PASSWORD("'.MCalcUtil::dbescape($password).'")
			;
		';
		$row = MCalcUtil::dbgetrow($sql);
		if ( !empty($row) ) {
			self::$user = new User;
			self::$user->setId($row->idUser);
			self::$user->load();
			return true;
		} else {
			self::$user = null;
			return false;
		}
	}

	public static function authenticateUser( User $user ) {
		$sql = '
			SELECT
				*
			FROM
				`user_User`
			WHERE
				`loginId` = "'.MCalcUtil::dbescape($user->getLoginId()).'"
				AND
				`password` = "'.MCalcUtil::dbescape($user->getPassword()).'"
			;
		';
		$row = MCalcUtil::dbgetrow($sql);
		if ( !empty($row) ) {
			self::$user = new User;
			self::$user->setId($row->idUser);
			self::$user->load();
			return true;
		} else {
			self::$user = null;
			return false;
		}
	}

}