<?php

function from_camel_case($input) {
	preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
	$ret = $matches[0];
	foreach ($ret as &$match) {
		$match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
	}
	return implode('_', $ret);
}

function mcalc_debug( $str, $debug_level = 1 ) {

	if ( defined('MCALC_CRON') && MCALC_CRON === true ) {

		if ( $debug_level > MCALC_DEBUG_LEVEL ) {
			return;
		}

		$time = date('H:i:s');
		if ( isset($_SERVER['argc']) && PHP_SAPI === 'cli' ) {
			// debug to command line
			echo '('.$time.')  '. $str ."\n";
		} else {
			// debug to browser 
			echo '<div>('.$time.')  '. $str.'</div>';
		}
	}


}

function clear_database() {
	$sqls = array(
		'truncate mkm_Article;',
		'truncate mkm_ArticleComment;',
		'truncate mkm_User;',
		// 'truncate mkm_Category;',
		// 'truncate mkm_Category_x_Product;',
		// 'truncate mkm_Language;',
		// 'truncate mkm_Metaproduct;',
		// 'truncate mkm_MetaproductName;',
		// 'truncate mkm_Product;',
		// 'truncate mkm_ProductName;',
	);
	foreach ( $sqls as $sql ) {
		MCalcUtil::dbquery($sql);
	}
}

function find_and_save_cards( $cards ) {


	$retval = array(
		'metaproductIds' => array()
	);

	$api = new MKMApi();

	foreach ( $cards as $card ) {

		$productIds = array();

		// search card in db
		$sql = '
			SELECT
				DISTINCT (`idMetaproduct`)
			FROM
				`mkm_MetaproductName`
			WHERE
				`metaproductName` = "'.MCalcUtil::dbescape($card).'"
			;
		';
		$rows = MCalcUtil::dbrows($sql);
		$metaproductIds = array();
		foreach ( $rows as $row ) {
			$metaproductIds[$row->idMetaproduct] = true;
			$retval['metaproductIds'][] = $row->idMetaproduct;
		}

		$need_update = array();

		if ( !empty($metaproductIds) ) {
			// have the metaproduct in database

			// if the last update is too long ago, update from api
			foreach ( array_keys($metaproductIds) as $idMetaproduct ) {
				$sql = '
					SELECT
						COUNT(*) AS `Count`
					FROM
						`meta_MetaproductUpdates`
					WHERE
						`idMetaproduct` = '.(int)$idMetaproduct.'
						AND
						`lastUpdate` > DATE_SUB(NOW(), INTERVAL 2 WEEK)
					;
				';
				$row = MCalcUtil::dbgetrow($sql);
				if ( (int)$row->Count === 0 ) {
					// keine aktuellen daten vorhanden! :(
					$need_update[$idMetaproduct] = true;
				} else {
					// aktuelle daten sind vorhanden :)
				}
			}

		} else {
			// we dont have the product in our database, search in api!

			$errors = array();
			// search card in api
			try {
				$res = $api->getMetaProductBySearch(MCalcUtil::encodeForSearch($card));
				$metaproductIds[$res->idMetaproduct] = true;
			} catch ( Exception $ex ) {
				$errors[] = $ex;
				// try to search for a product and if a product is found, get the meta product
				try {

					$res_products = $api->getProductsBySearch(MCalcUtil::encodeForSearch($card), 1, true);
					foreach ( $res_products as $res_product ) {
						$metaproductIds[$res_product->idMetaproduct] = true;
					}
				} catch ( Exception $ex2 ) {

					// 
					$errors[] = $ex2;
				}

			}

			if ( empty($metaproductIds) ) {

				// not found
				echo implode("\n", $errors);
				throw new Exception('CARD: '.$card.'... Response for getMetaProductBySearch is empty or ID of meta product is not set.');
			}
			foreach ( array_keys($metaproductIds) as $idMetaproduct ) {
				$need_update[$idMetaproduct] = true;
				$retval['metaproductIds'][] = $idMetaproduct;
			}

		}

		foreach ( array_keys($need_update) as $idMetaproduct ) {
			$res = $api->getMetaproductById($idMetaproduct);
			if ( empty($res) ) continue;
			if ( empty($res->idMetaproduct) ) continue;
			if ( empty($res->name) ) continue;
			if ( empty($res->products) ) continue;

			$metaproduct = new mkm_Metaproduct();
			$metaproduct->setIdMetaproduct($res->idMetaproduct);
			foreach ( $res->name as $name ) {
				$metaproduct->addName($name->idLanguage, $name->languageName, $name->metaproductName);
			}

			$products = is_array($res->products->idProduct) ? $res->products->idProduct : array($res->products->idProduct);

			foreach ( $products as $idProduct ) {
				$metaproduct->addProduct($idProduct);

				// search card in database
				$sql = 'SELECT `idProduct` FROM `mkm_Product` WHERE `idProduct` = '.(int)$idProduct.';';
				$row = MCalcUtil::dbgetrow($sql);
				if ( empty($row->idProduct) ) {

					$res2 = $api->getProductById($idProduct);
					if ( empty($res2) || empty($res2->idProduct) ) {

					} else {
						$product = new mkm_Product();
						$product->setIdProduct($res2->idProduct);
						$product->setIdMetaproduct($res2->idMetaproduct);
						$product->setImage($res2->image);
						$product->setRarity($res2->rarity);
						$product->setExpansion($res2->expansion);
						$product->setCategory($res2->category->idCategory, $res2->category->categoryName);
						foreach ( $res2->name as $name ) {
							$product->addName($name->idLanguage, $name->languageName, $name->productName);
						}
						$product->save();
					}

				} else {

					// product already exists

				}
			}
			$metaproduct->save();
			// echo 'Saved Metaproduct "'.$metaproduct->getName(MKMApi::ID_LANGUAGE_ENGLISH).'"'."\n";

			$sql = '
				INSERT INTO
					`meta_MetaproductUpdates`
				SET
					`idMetaproduct` = '.(int)$idMetaproduct.',
					`lastUpdate` = NOW()
				ON DUPLICATE KEY UPDATE
					`lastUpdate` = NOW()
				;
			';
			MCalcUtil::dbquery($sql);

		}

	}

	// array unique machen :)
	array_unique($retval['metaproductIds']);

	return $retval;
}



function find_and_save_articles( $product_ids ) {



	$api = new MKMApi();


	foreach ( $product_ids as $idProduct ) {

		// check if last update was not so long ago
		$sql = '
			SELECT
				COUNT(*) AS `Count`
			FROM
				`meta_ProductPriceUpdates`
			WHERE
				`idProduct` = '.(int)$idProduct.'
				AND
				`lastUpdate` > DATE_SUB(NOW(), INTERVAL 2 DAY)
			;
		';
		$row = MCalcUtil::dbgetrow($sql);
		if ( (int)$row->Count === 0 ) {
			// keine aktuellen daten vorhanden! :(
			$res_articles = false;
			// daten noch aus der db entfernen
			$sql = 'DELETE FROM `mkm_Article` WHERE `idProduct` = '.(int)$idProduct.';';
			MCalcUtil::dbquery($sql);
		} else {
			// try to get via db:
			$sql = 'SELECT * FROM `mkm_Article` WHERE `idProduct` = '.(int)$idProduct.';';
			$res_articles = MCalcUtil::dbrows($sql);
		}



		// if empty, try to get via api:
		if ( empty($res_articles) ) {
			$res_articles = $api->getArticlesByProductId($idProduct);
				
			foreach ( $res_articles as $res ) {

				if ( empty($res) ) {
					throw new Exception('PRODUCT ID: '.$idProduct.'... No Articles!');
				} else {

					//var_dump($res);

					$article = new mkm_Article();
					$article->setIdArticle($res->idArticle);
					$article->setIdProduct($res->idProduct);
					$article->setLanguage($res->language->idLanguage, $res->language->languageName);
					if ( empty($res->comments) || is_object($res->comments) ) {
						//
					} else {
						$comments = is_array($res->comments) ? $res->comments : array($res->comments);
						foreach ( $comments as $comment ) {
							$article->addComment($comment);
						}
					}
					$article->setPrice($res->price);
					$article->setCount($res->count);
					$article->setCondition($res->condition);
					$article->setIsFoil(in_array($res->isFoil, array('false', '0', 0)) ? false : true);
					$article->setIsSigned(in_array($res->isSigned, array('false', '0', 0)) ? false : true);
					$article->setIsAltered(in_array($res->isAltered, array('false', '0', 0)) ? false : true);
					$article->setIsPlayset(in_array($res->isPlayset, array('false', '0', 0)) ? false : true);

					$article->setSeller(
						$res->seller->idUser,
						$res->seller->username,
						$res->seller->country,
						in_array($res->seller->isCommercial, array('false', '0', 0)) ? false : true,
						$res->seller->riskGroup,
						$res->seller->reputation
					);

					$article->save();

				}
				
			}
			$sql = '
				INSERT INTO
					`meta_ProductPriceUpdates`
				SET
					`idProduct` = '.(int)$idProduct.',
					`lastUpdate` = NOW()
				ON DUPLICATE KEY UPDATE
					`lastUpdate` = NOW()
				;
			';
			MCalcUtil::dbquery($sql);

		}

	}


}