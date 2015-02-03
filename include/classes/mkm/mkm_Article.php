<?php




class mkm_Article { 

	private $_idArticle = null;
	private $_idProduct = null;


	private $_language = null;
	private $_comments = array();


	private $_price = null;
	private $_count = null;


	private $_seller = null;
	private $_condition = null;


	private $_isFoil = null;
	private $_isSigned = null;
	private $_isAltered = null;
	private $_isPlayset = null;



	public function save() {


		$sql = '
			INSERT INTO
				`mkm_Article`
			SET
				`idArticle` = '.(int)$this->_idArticle.',
				`idProduct` = '.(int)$this->_idProduct.',
				`idSeller` = '.(int)$this->_seller['idUser'].',
				`idLanguage` = '.(int)$this->_language['idLanguage'].',
				`price` = '.(int)$this->_price.',
				`count` = '.(int)$this->_count.',
				`condition` = "'.MCalcUtil::dbescape($this->_condition).'",
				`isFoil` = '.($this->_isFoil ? 1 : 0).',
				`isSigned` = '.($this->_isSigned ? 1 : 0).',
				`isAltered` = '.($this->_isAltered ? 1 : 0).',
				`isPlayset` = '.($this->_isPlayset ? 1 : 0).'
			ON DUPLICATE KEY UPDATE
				`idArticle` = '.(int)$this->_idArticle.',
				`idProduct` = '.(int)$this->_idProduct.',
				`idLanguage` = '.(int)$this->_language['idLanguage'].',
				`price` = '.(int)$this->_price.',
				`count` = '.(int)$this->_count.',
				`condition` = "'.MCalcUtil::dbescape($this->_condition).'",
				`isFoil` = '.($this->_isFoil ? 1 : 0).',
				`isSigned` = '.($this->_isSigned ? 1 : 0).',
				`isAltered` = '.($this->_isAltered ? 1 : 0).',
				`isPlayset` = '.($this->_isPlayset ? 1 : 0).'
			;
		';
		MCalcUtil::dbquery($sql);


		// seller speichern
		$sql = '
			INSERT INTO
				`mkm_User`
			SET
				`idUser` = '.(int)$this->_seller['idUser'].',
				`username` = "'.MCalcUtil::dbescape($this->_seller['username']).'",
				`country` = "'.MCalcUtil::dbescape($this->_seller['country']).'",
				`isCommercial` = '.($this->_seller['isCommercial'] ? 1 : 0).',
				`riskGroup` = '.(int)$this->_seller['riskGroup'].',
				`reputation` = '.(int)$this->_seller['reputation'].'
			ON DUPLICATE KEY UPDATE
				`idUser` = '.(int)$this->_seller['idUser'].',
				`username` = "'.MCalcUtil::dbescape($this->_seller['username']).'",
				`country` = "'.MCalcUtil::dbescape($this->_seller['country']).'",
				`isCommercial` = '.($this->_seller['isCommercial'] ? 1 : 0).',
				`riskGroup` = '.(int)$this->_seller['riskGroup'].',
				`reputation` = '.(int)$this->_seller['reputation'].'
			;
		';
		MCalcUtil::dbquery($sql);


		// language speichern
		$sql = '
			INSERT INTO
				`mkm_Language`
			SET
				`idLanguage` = '.(int)$this->_language['idLanguage'].',
				`languageName` = "'.MCalcUtil::dbescape($this->_language['languageName']).'"

			ON DUPLICATE KEY UPDATE
				`languageName` = "'.MCalcUtil::dbescape($this->_language['languageName']).'"
			;
		';
		MCalcUtil::dbquery($sql);



		// kommentare speichern
		$sql = 'DELETE FROM `mkm_ArticleComment` WHERE `idArticle` = '.(int)$this->_idArticle.';';
		MCalcUtil::dbquery($sql);

		foreach ( $this->_comments as $comment ) {
			$sql = '
				INSERT INTO
					`mkm_ArticleComment`
				SET 
					`idArticle` = '.(int)$this->_idArticle.',
					`comment` = "'.MCalcUtil::dbescape($comment).'"
				;
			';
			MCalcUtil::dbquery($sql);

		}



	}



	public function setIdArticle( $idArticle ) {
		$this->_idArticle = $idArticle;
	}
	public function setIdProduct( $idProduct ) {
		$this->_idProduct = $idProduct;
	}
	public function setLanguage( $idLanguage, $languageName ) {
		$this->_language = array(
			'idLanguage' => $idLanguage,
			'languageName' => $languageName,
		);
	}
	public function addComment( $comment ) {
		$this->_comments[] = $comment;
	}
	public function setPrice ( $price ) {
		if ( is_string($price) ) {
			// jaja so umstaendlich da sonst manchma zu wenig oder zu viel rauskommt
			$price = (int)round(number_format($price, 2)*100);
		}
		$this->_price = $price;
	}
	public function setCount( $count ) {
		$this->_count = $count;
	}

	public function setCondition ( $condition ) {
		$this->_condition = $condition;
	}
	public function setIsFoil ( $isFoil ) {
		$this->_isFoil = $isFoil;
	}
	public function setIsSigned ( $isSigned ) {
		$this->_isSigned = $isSigned;
	}
	public function setIsAltered ( $isAltered ) {
		$this->_isAltered = $isAltered;
	}
	public function setIsPlayset( $isPlayset ) {
		$this->_isPlayset = $isPlayset;
	}


	public function setSeller( $idUser, $username, $country, $isCommercial, $riskGroup, $reputation ) {
		$this->_seller = array(
			'idUser' => $idUser,
			'username' => $username,
			'country' => $country,
			'isCommercial' => $isCommercial,
			'riskGroup' => $riskGroup,
			'reputation' => $reputation,
		);
	}

}