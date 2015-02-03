<?php


class mkm_Metaproduct {
	

	private $_idMetaproduct = null;

	private $_name = array();
	private $_products = array();



	private $__loaded = false;


	public function __construct() {

	}


	public function isLoaded( ) {
		return $this->__loaded;
	}
	public function load() {


		// NAMES:
		$this->_name = array();
		$sql = '
			SELECT
				`l`.*,
				`mn`.`metaproductName`
			FROM 
				`mkm_MetaproductName` AS `mn`
				INNER JOIN `mkm_Language` AS `l` ON `l`.`idLanguage` = `mn`.`idLanguage`
			WHERE
				`mn`.`idMetaproduct` = '.(int)$this->_idMetaproduct.'
			;
		';
		$rows = MCalcUtil::dbrows($sql);
		foreach ( $rows as $row ) {
			$this->_name[] = array(
				'idLanguage' => $row->idLanguage,
				'languageName' => $row->languageName,
				'metaproductName' => $row->metaproductName,
			);
		}

		// PRODUCTS: 
		// TODO...


		$this->__loaded = true;

	}


	public function save() {



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


			// metaproduct name eintragen
			$sql = '
				INSERT INTO
					`mkm_MetaproductName`
				SET
					`idMetaproduct` = '.(int)$this->_idMetaproduct.',
					`idLanguage` = '.(int)$name['idLanguage'].',
					`metaproductName` = "'.MCalcUtil::dbescape($name['metaproductName']).'"

				ON DUPLICATE KEY UPDATE
					`metaproductName` = "'.MCalcUtil::dbescape($name['metaproductName']).'"
				;
			';
			MCalcUtil::dbquery($sql);

		}

		// produkte eintragen, zumindest erst mal die id
		foreach ( $this->_products as $idProduct ) {

			$sql = '
				INSERT INTO
					`mkm_Product`
				SET
					`idProduct` = '.(int)$idProduct.',
					`idMetaproduct` = '.(int)$this->_idMetaproduct.'

				ON DUPLICATE KEY UPDATE
					`idMetaproduct` = '.(int)$this->_idMetaproduct.'
				;
			';
			MCalcUtil::dbquery($sql);

		}


		$this->__loaded = true;

	}



	public function setIdMetaproduct( $idMetaproduct ) {
		$this->_idMetaproduct = $idMetaproduct;
	}

	public function getName( $idLanguage ) {
		foreach ( $this->_name as $name ) {
			if ( $name['idLanguage'] == $idLanguage ) {
				return $name['metaproductName'];
			}
		}
		return false;
	}

	public function addName( $idLanguage, $languageName, $metaproductName ) {
		$this->_name[] = array(
			'idLanguage' => $idLanguage,
			'languageName' => $languageName,
			'metaproductName' => $metaproductName,
		);
	}

	public function addProduct( $idProduct ) {
		$this->_products[] = $idProduct;
	}





}