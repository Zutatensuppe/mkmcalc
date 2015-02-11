<?php

namespace app\back\mkm;

use system\Database as Database;

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
				`image` = "'.Database::instance()->escape($this->_image).'",
				`expansion` = "'.Database::instance()->escape($this->_expansion).'",
				`rarity` = "'.Database::instance()->escape($this->_rarity).'"
			ON DUPLICATE KEY UPDATE
				`idMetaproduct` = '.(int)$this->_idMetaproduct.',
				`image` = "'.Database::instance()->escape($this->_image).'",
				`expansion` = "'.Database::instance()->escape($this->_expansion).'",
				`rarity` = "'.Database::instance()->escape($this->_rarity).'"
			;
		';
		Database::instance()->query($sql);

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
		Database::instance()->query($sql);


		foreach ( $this->_name as $name ) {

			// language eintragen wenn nicht vorhanden
			$sql = '
				INSERT INTO
					`mkm_Language`
				SET
					`idLanguage` = '.(int)$name['idLanguage'].',
					`languageName` = "'.Database::instance()->escape($name['languageName']).'"
				ON DUPLICATE KEY UPDATE
					`languageName` = "'.Database::instance()->escape($name['languageName']).'"
				;
			';
			Database::instance()->query($sql);


			// product name eintragen
			$sql = '
				INSERT INTO
					`mkm_ProductName`
				SET
					`idProduct` = '.(int)$this->_idProduct.',
					`idLanguage` = '.(int)$name['idLanguage'].',
					`productName` = "'.Database::instance()->escape($name['productName']).'"
				ON DUPLICATE KEY UPDATE
					`productName` = "'.Database::instance()->escape($name['productName']).'"
				;
			';
			Database::instance()->query($sql);

		}


		// category eintragen
		$sql = '
			INSERT INTO
				`mkm_Category`
			SET
				`idCategory` = '.(int)$this->_category['idCategory'].',
				`categoryName` = "'.Database::instance()->escape($this->_category['categoryName']).'"
			ON DUPLICATE KEY UPDATE
				`categoryName` = "'.Database::instance()->escape($this->_category['categoryName']).'"
			;
		';
		Database::instance()->query($sql);


		$sql = '
			INSERT IGNORE INTO
				`mkm_Category_x_Product`
			SET
				`idCategory` = '.(int)$this->_category['idCategory'].',
				`idProduct` = '.(int)$this->_idProduct.'
			;
		';
		Database::instance()->query($sql);



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