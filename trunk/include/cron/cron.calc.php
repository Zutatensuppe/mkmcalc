<?php

(!defined('MCALC_CRON') && define('MCALC_CRON', true));
(!defined('MCALC_DEBUG_LEVEL') && define('MCALC_DEBUG_LEVEL', 9));



require_once __DIR__.'/../bootstrap.php';


$cards = MCalcUtil::ReadCardsFromFile(__DIR__.'/etb_burn.txt');
// foreach ( $cards as $i => $card ) {
// 	echo $i.'. '. $card ."\n";
// }

$api = new MKMApi();

// $meta_product = false;
// $card = 'Æther Charge';

// $meta_product = $api->getMetaProductBySearch(MCalcUtil::encodeForSearch($card));
// if ( empty($meta_product) ) {

// 	$ps_res_array = $api->getProductsBySearch(MCalcUtil::encodeForSearch($card), 1, true);
// 	foreach ( $ps_res_array as $ps_res ) {
// 		$meta_product = $api->getMetaproductById($ps_res->idMetaproduct);
// 	}
// }
// // $api = new MKMApi();
// // idMetaproduct
// // // $acc = $api->request('metaproducts/island/1/1');
// var_dump($meta_product);
// die();

$cards = array_unique($cards);


$steps = array(
	// 'clear_database',
	// 'find_and_save_cards',
	'find_and_save_articles',
);

if ( in_array('clear_database', $steps) ) {
	echo 'clearing database...'."\n";
	clear_database();
	echo 'database cleared'."\n";
}

if ( in_array('find_and_save_cards', $steps) ) {
	echo 'finding cards...'."\n";
	find_and_save_cards($cards);
	echo 'cards found'."\n";
}

if ( in_array('find_and_save_articles', $steps) ) {
	echo 'finding articles...'."\n";

	// get all products that fit for me!
	$sql = '
		SELECT
			`mkm_Category_x_Product`.`idProduct`
		FROM
			`mkm_Category_x_Product`
			INNER JOIN `mkm_Product`
			ON `mkm_Product`.`idProduct` = `mkm_Category_x_Product`.`idProduct`
		WHERE
			`mkm_Category_x_Product`.`idCategory` = 1 
			AND
			`mkm_Product`.`rarity` <> "Special"
		;
	';
	$product_ids = array();
	foreach ( MCalcUtil::dbrows($sql) as $row ) {
		$product_ids[] = (int)$row->idProduct;
	}

	find_and_save_articles($product_ids);
	echo 'articles found'."\n";
}








// get all products and articles etc...
$sql = '
	select
		a.idArticle as idArticle,
		a.idProduct as idProduct,
		a.idSeller as idUser,
		a.idLanguage as idLanguage,
		a.price as price,
		a.`count` as `count`,
		a.`condition` as `condition`,
		p.idMetaProduct as idMetaproduct,
		mn.metaproductName as metaproductName,
		u.username as username,
		u.country as country,
		p.`expansion` as `expansion`,
		p.rarity as rarity

	from
		mkm_Article as a

		inner join mkm_Product as p
		on p.idProduct = a.idProduct

		inner join mkm_MetaproductName as mn
		on mn.idMetaproduct = p.idMetaproduct and mn.idLanguage = 1
		and mn.metaproductName in ("'.implode('", "', $cards).'")

		inner join mkm_Metaproduct as m
		on m.idMetaproduct = p.idMetaproduct

		inner join mkm_User as u
		on a.idSeller = u.idUser

	where 
		isAltered = 0
		and
		rarity <> "special"
		and 
		a.idLanguage in ( 1, 3 )
		and 
		u.country in ( "D", "AT" )
		and
		a.`condition` not in ("PO", "PL")
	ORDER BY
		`idMetaproduct` ASC,
		`price` ASC,
		`idSeller` ASC
	;
';
$res = MCalcUtil::dbrows($sql);

$__cards = array();

foreach ( $res as $row ) {

	$row->idMetaproduct = (int)$row->idMetaproduct;
	$row->idProduct = (int)$row->idProduct;

	$idx = $row->idMetaproduct;

	$__cards[$idx] = isset($__cards[$idx]) ? $__cards[$idx] : new stdClass;

	$__cards[$idx]->idMetaproduct = $row->idMetaproduct;
	$__cards[$idx]->productName = $row->metaproductName;

	$__cards[$idx]->product_ids = isset($__cards[$idx]->product_ids) ? $__cards[$idx]->product_ids : array();

	$__cards[$idx]->product_ids[] = $row->idProduct;


	$__cards[$idx]->sellers = isset($__cards[$idx]->sellers) ? $__cards[$idx]->sellers : array();

	$seller = new stdClass;
	$seller->cost = ($row->price/100.0);
	$seller->seller_id = (int)$row->idUser;
	$__cards[$idx]->sellers[] = $seller;

	$row = null;
	unset($row);

}













$calculator = new MCalcCalculatorV2;
$calculator->calculateBestPrices($__cards);

die();





