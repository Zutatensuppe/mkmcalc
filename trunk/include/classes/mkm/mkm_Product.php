<?php


class mkm_Product {
	
	private $_idProduct = null;
	private $_idMetaproduct = null;

	private $_name = array();
	private $_category = null;

	private $_image = null;
	private $_expansion = null;
	private $_rarity = null;




	public function save() {


		$sql = '
			INSERT INTO
				`mkm_Product`
			SET
				`idProduct` = '.(int)$this->_idProduct.',
				`idMetaproduct` = '.(int)$this->_idMetaproduct.',
				`image` = "'.MCalcUtil::dbescape($this->_image).'",
				`expansion` = "'.MCalcUtil::dbescape($this->_expansion).'",
				`rarity` = "'.MCalcUtil::dbescape($this->_rarity).'"
			ON DUPLICATE KEY UPDATE
				`idMetaproduct` = '.(int)$this->_idMetaproduct.',
				`image` = "'.MCalcUtil::dbescape($this->_image).'",
				`expansion` = "'.MCalcUtil::dbescape($this->_expansion).'",
				`rarity` = "'.MCalcUtil::dbescape($this->_rarity).'"
			;
		';
		MCalcUtil::dbquery($sql);

		// metaproduct eintragen, zumindest erst mal die id
		$sql = '
			INSERT INTO
				`mkm_Metaproduct`
			SET
				`idMetaproduct` = '.(int)$this->_idMetaproduct.'
			ON DUPLICATE KEY UPDATE
				`idMetaproduct` = '.(int)$this->_idMetaproduct.'
			;
		';
		MCalcUtil::dbquery($sql);


		foreach ( $this->_name as $name ) {

			// language eintragen wenn nicht vorhanden
			$sql = '
				INSERT INTO
					`mkm_Language`
				SET
					`idLanguage` = '.(int)$name['idLanguage'].',
					`languageName` = "'.MCalcUtil::dbescape($name['languageName']).'"
				ON DUPLICATE KEY UPDATE
					`languageName` = "'.MCalcUtil::dbescape($name['languageName']).'"
				;
			';
			MCalcUtil::dbquery($sql);


			// product name eintragen
			$sql = '
				INSERT INTO
					`mkm_ProductName`
				SET
					`idProduct` = '.(int)$this->_idProduct.',
					`idLanguage` = '.(int)$name['idLanguage'].',
					`productName` = "'.MCalcUtil::dbescape($name['productName']).'"
				ON DUPLICATE KEY UPDATE
					`productName` = "'.MCalcUtil::dbescape($name['productName']).'"
				;
			';
			MCalcUtil::dbquery($sql);

		}


		// category eintragen
		$sql = '
			INSERT INTO
				`mkm_Category`
			SET
				`idCategory` = '.(int)$this->_category['idCategory'].',
				`categoryName` = "'.MCalcUtil::dbescape($this->_category['categoryName']).'"
			ON DUPLICATE KEY UPDATE
				`categoryName` = "'.MCalcUtil::dbescape($this->_category['categoryName']).'"
			;
		';
		MCalcUtil::dbquery($sql);


		$sql = '
			INSERT IGNORE INTO
				`mkm_Category_x_Product`
			SET
				`idCategory` = '.(int)$this->_category['idCategory'].',
				`idProduct` = '.(int)$this->_idProduct.'
			;
		';
		MCalcUtil::dbquery($sql);



	}






	public function setIdProduct( $idProduct ) {
		$this->_idProduct = $idProduct;
	}

	public function setIdMetaproduct( $idMetaproduct ) {
		$this->_idMetaproduct = $idMetaproduct;
	}

	public function setImage( $image ) {
		$this->_image = $image;
	}

	public function setExpansion( $expansion ) {
		$this->_expansion = $expansion;
	}

	public function setRarity( $rarity ) {
		$this->_rarity = $rarity;
	}

	public function setCategory ( $idCategory, $categoryName ) {
		$this->_category = array(
			'idCategory' => $idCategory,
			'categoryName' => $categoryName,
		);
	}

	public function addName( $idLanguage, $languageName, $productName ) {
		$this->_name[] = array(
			'idLanguage' => $idLanguage,
			'languageName' => $languageName,
			'productName' => $productName,
		);
	}
}