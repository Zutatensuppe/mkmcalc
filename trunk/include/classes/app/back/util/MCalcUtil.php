<?php
namespace app\back\util;

class MCalcUtil {

	public static function CountryNames() {
		return array(
			'D' => 'Deutschland',
			'IT' => 'Italien',
			'AT' => 'Österreich',
			'GB' => 'Großbritannien',
			'CH' => 'Schweiz',
			'ES' => 'Estland',
			'PT' => 'Portugal',
			'FR' => 'Frankreich',
			'BE' => 'Belgien',
			'FI' => 'Finnland',
			'NL' => 'Niederlande',
			'GR' => 'Griechenland',
			'PL' => 'Polen',
			'SG' => 'Singapur',
			'DK' => 'Dänemark',
			'CZ' => 'Tschechien',
			'SI' => 'Slowenien',
			'LU' => 'Luxemburg',
			'SE' => 'Schweden',
			'IE' => 'Irland',
			'RO' => 'Rumänien',
			'EE' => 'Estland',
			'BG' => 'Bulgarien',
			'JP' => 'Japan',
			'LT' => 'Litauen',
			'SK' => 'Slowakei',
			'CA' => 'Kanada',
			'MT' => 'Malta',
			'HU' => 'Ungarn',
			'HR' => 'Kroatien',
			'NO' => 'Norwegen',
			'CY' => 'Zypern',
		);
	}
	public static function CardLanguages() {
		return array(
			1 => 'Englisch',
			2 => 'Französisch',
			3 => 'Deutsch',
			4 => 'Spanisch',
			5 => 'Italienisch',
			6 => 'Chinesisch (S)',
			7 => 'Japanisch',
			8 => 'Portugiesisch',
			9 => 'Russisch',
			10 => 'Koreanisch',
			11 => 'Chinesisch (T)',
		);
	}

	public static function log( $str ) {
		$mysqli = self::dbconnect();
		return self::dbquery($mysqli, 'INSERT INTO `log` SET `message` = "'.mysqli_escape_string($mysqli, $str).'"');
	}

	private static function ReadCardsFromStringArray( $stringArray ) {
		$cards = array();
		foreach ( $stringArray as $string ) {
			if ( preg_match('%^delete%', $string) ) continue;
			if ( preg_match('%^cardThumbnail%', $string) ) continue;

			$string = preg_replace('%\t.*%ims', '', $string);

			$string = preg_replace('%\([rgbuw/]+\)%ims', '', $string);
			$string = preg_replace('%#.*$%ims', '', $string);
			$string = preg_replace('%//.*$%ims', '', $string);
			$string = trim($string);

			// comments ignorieren
			if ( strpos($string, '#') === 0 ) continue;
			if ( strpos($string, '\'') === 0 ) continue;
			if ( strpos($string, '//') === 0 ) continue;
			if ( $string === '') continue;

			$card = $string;
			$cards[] = $card;

		}
		return $cards;
	}

	public static function ReadCardsFromString( $string ) {
		return self::ReadCardsFromStringArray(preg_split('%[\r\n]+%', $string));
	}

	public static function ReadCardsFromFile( $filename ) {
		$cards = array();

		if ( file_exists($filename) ) {
			$lines = file($filename);
			$cards = self::ReadCardsFromStringArray($lines);
		}
		return $cards;

	}

	public static function encodeForSearch($cardname) {
		return urlencode($cardname);
	}


	public static function FindCardsToDatabase( $cards ) {

		$sellers = array();

		foreach ( $cards as $card ) {

			$result = null;
			$cache_file = TMP_DIR.'/tmpcache_'.md5($card);
			if ( file_exists($cache_file) ) {
				$result = unserialize(file_get_contents($cache_file)); 
			} else {
				$result = MKMApi::search($card);
				file_put_contents($cache_file, serialize($result));
			}
			if ( !empty($result->items) ) {
				foreach ( $result->items as $item ) {
					$card_wishlist = new MCalcProduct;
					$card_wishlist->setName($card);
					$card_wishlist->setMkmProductName($item->productName);
					$card_wishlist->setMkmIdCategory($item->idCategory);
					$card_wishlist->setMkmIdProduct($item->idProduct);

					foreach ( $item->offers as $offer ) {

						if ( !isset($sellers[$offer->username]) ) {
							$sellers[$offer->username] = new MCalcSeller;
							$sellers[$offer->username]->setShippingCost(0.90); // erstmal hier default einsetzen
							$sellers[$offer->username]->setName($offer->username);
							$sellers[$offer->username]->setLocation($offer->location);
						}
						$sellers[$offer->username]->addProduct(
							MCalcProduct::create($card_wishlist),
							$offer
						);
					}
				}
			}
		}

		foreach ( $sellers as $seller ) {
			MCalcUtil::SellerToDatabase($seller);
		}

	}


	public static function IrrelevantSellerIds() {
		$seller_ids = array();
		$sql = '
			select * FROM 
				(
					SELECT 
					DISTINCT _x_.`seller_id`,_x_.`product_id`,
					(SELECT _XXXX_.price FROM seller_x_product AS _XXXX_ WHERE _x_.`seller_id` = _XXXX_.`seller_id` AND _x_.`product_id` = _XXXX_.`product_id` ORDER BY `price` ASC LIMIT 0, 1) AS `price`
					FROM seller_x_product AS _x_
				) AS `x`
			WHERE
				EXISTS (
					SELECT `x2`.seller_id FROM seller_x_product AS `x2`
					WHERE
						`x2`.seller_id <> `x`.seller_id
						AND `x2`.`price` < `x`.`price`
						AND `x2`.`product_id` = `x`.`product_id`
				)
			GROUP BY
			`x`.`seller_id`
			HAVING COUNT(`product_id`) = 1
			;
		';
		$res = self::dbquery($sql);
		while ( $row = mysqli_fetch_object($res) ) {
			$seller_ids[] = (int) $row->seller_id;
		}
		return $seller_ids;
	}
	public static function ProductlistFromDatabase( $products ) {

		$productlist = new MCalcProductList;

		$products_escaped = array();
		foreach ( $products as $product ) {
			$products_escaped[] = self::dbescape($product);
		}
		$sql = 'SELECT * FROM `product` WHERE `mkm_ProductName` IN ("'.implode('","', $products_escaped).'");';
		$res = self::dbquery($sql);
		while ( $row = mysqli_fetch_object($res) ) {
			$product = new MCalcProduct;
			$product->setMkmProductName($row->mkm_ProductName);
			if ( $productlist->contains($product) ) {

			} else {
				$productlist->add($product);
			}
		}
		return $productlist;

	}
	public static function SellersFromDatabase( $products ) {

		$sellers = array();

		foreach ( $products as $i=>$_product ) {


			$product_ids = array();
			$sql = 'SELECT * FROM `product` WHERE `mkm_ProductName` = "'.self::dbescape($_product).'";';
			$res = self::dbquery($sql);
			while ( $row = mysqli_fetch_object($res) ) {
				$product_ids[] = $row->product_id;
			}

		//	$irrelevant_seller_ids = self::IrrelevantSellerIds();
			$sql = '
				SELECT
					DISTINCT
					`seller`.`name` AS `seller_name`,
					`x`.`price` AS `price`,
					`product`.`mkm_ProductName` AS `product_name`
				FROM
					`seller`
					INNER JOIN `seller_x_product` AS `x`
					ON `x`.`seller_id` = `seller`.`seller_id`
					INNER JOIN `product`
					ON `x`.`product_id` = `product`.`product_id`
				WHERE
					`product`.`product_id` IN ('.implode(',',$product_ids).')
					'.(!empty($irrelevant_seller_ids) ? 'AND `seller`.`seller_id` NOT IN ('.implode(',', $irrelevant_seller_ids).')' : '').'
				GROUP BY
					`seller`.`seller_id`
				ORDER BY
					`x`.`price` ASC
				;
			';
			$res = self::dbquery($sql);
			while ( $row = mysqli_fetch_object($res) ) {
				if ( !isset($sellers[$row->seller_name]) ) {
					$sellers[$row->seller_name] = new MCalcSeller;
					$sellers[$row->seller_name]->setShippingCost(0.90); // erstmal hier default einsetzen
					$sellers[$row->seller_name]->setName($row->seller_name);
				}

				$product = new MCalcProduct;
				$product->setMkmProductName($row->product_name);
				$product->setPrice($row->price);
				$sellers[$row->seller_name]->addProduct($product);
			}
			mcalc_debug($i . '.. product fetched');
		}

		return $sellers;

	}


	public static function ProductToDatabase( MCalcProduct $product ) {

		$sql = '
			INSERT IGNORE INTO 
				`product`
			SET
				`name` = "'.self::dbescape($product->getName()).'",
				`mkm_ProductName` = "'.self::dbescape($product->getMkmProductName()).'",
				`mkm_IdProduct` = '.(int)$product->getMkmIdProduct().',
				`mkm_IdCategory` = '.(int)$product->getMkmIdCategory().'
			;
		';
		self::dbquery($sql);

	}

	public static function SellerToDatabase( MCalcSeller $seller ) {


		$sql = '
			INSERT IGNORE INTO
				`seller`
			SET
				`name` = "'.self::dbescape($seller->getName()).'",
				`location` = "'.self::dbescape($seller->getLocation()).'"
			;
		';
		self::dbquery($sql);

		$sql = 'SELECT `seller_id` FROM `seller` WHERE `name` = "'.self::dbescape($seller->getName()).'"';
		$row = self::dbgetrow($sql);
		if ( !empty($row->seller_id) ) {

			self::dbquery('DELETE FROM `seller_x_product` WHERE `seller_id` = '.(int)$row->seller_id.';');

			foreach ( $seller->getProductList() as $product ) {

				if ( (int)$product->getMkmIdProduct() === 0 ) {
					throw new Exception('keine mkm id gegeben...');
				}

				self::ProductToDatabase($product);

				$_prod = self::dbgetrow('SELECT `product_id` FROM `product` WHERE `mkm_IdProduct` = '.(int)$product->getMkmIdProduct().';');
				if ( !empty($_prod) ) {

				}


				$sql = '
					INSERT INTO `seller_x_product`
						(`seller_id`, `product_id`, `price`, `language`, `condition`)
						VALUES
						(
							'.$row->seller_id.',
							'.$_prod->product_id.',
							'.$product->getPrice().',
							"'.self::dbescape($product->getLanguage()).'",
							"'.self::dbescape($product->getCondition()).'"
						)
					;
				';
				self::dbquery($sql);
			}
		}

	}
}