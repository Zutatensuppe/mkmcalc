<?php
namespace system;

class User {

	private 
		$id = null,

		$loginId = null,
		$password = null, // PASSWORD(BLABLA)

		$name = null
	;

	public function __construct() {

	}

	public function load() {
		if ( empty($this->id) )	{
			return false;
		}


		$sql = 'SELECT * FROM `user_User` WHERE `idUser` = '.(int)$this->id.';';
		$row = Database::instance()->getRow($sql);

		if ( !empty($row) ) {
			$this->loginId = $row->loginId;
			$this->password = $row->password;
			$this->name = $row->name;
			return true;
		}

		return false;

	}

	public function getLists() {
		$sql = 'SELECT * FROM `user_List` WHERE `idUser` = '.(int)$this->id.';';
		return Database::instance()->getRows($sql);
	}

	public function setId( $id ) {
		$this->id = $id;
	}


	public function setLoginId( $loginId ) {
		$this->loginId = $loginId;
	}
	public function setPassword( $password ) {
		$this->password = $password;
	}

	public function setName( $name ) {
		$this->name = $name;
	}



	public function getId() {
		return $this->id;
	}

	public function getLoginId() {
		return $this->loginId;
	}
	public function getPassword() {
		return $this->password;
	}
	public function getName() {
		return $this->name;
	}
}