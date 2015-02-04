<?php


class ListsController extends Controller {


	public function calcAction() {


		$idList = $this->dispatcher->getVar(1);
		$user = Auth::user();
		if ( empty($user) ) {
			$this->dispatcher->redirect(Config::get('siteurl'));
		}
		
		$chosenList = null;


		$seller_countries = !empty($_POST['seller-countries']) ? array_keys($_POST['seller-countries']) : array();
		$minimal_condition = !empty($_POST['minimal-condition']) ? $_POST['minimal-condition'] : 'PO';
		$card_languages = !empty($_POST['card-languages']) ? $_POST['card-languages'] : array();
		$altered_art_ok = !empty($_POST['altered-art-ok']);

		$chosen_conditions = array();
		$conditions = array(
			'MT' => 'Mint',
			'NM' => 'Near Mint',
			'EX' => 'Excellent',
			'GD' => 'Good',
			'LP' => 'Light Played',
			'PL' => 'Played',
			'PO' => 'Poor',
		);

		foreach ( $conditions as $key => $condition ) {
			$chosen_conditions[] = $key;
			if ( $key == $minimal_condition ) {
				break;
			}
		}

		$lists = $user->getLists();
		foreach ( $lists as $list ) {
			if ( $list->idList = $idList ) {
				$chosenList = $list;
				break;
			}
		}

		if ( !empty($chosenList) ) {
			$metaproductIds = array();
			// alle metaproducts holen die in der liste sind
			$sql = '
				SELECT
					`idMetaproduct`
				FROM
					`user_List_x_Metaproduct`
				WHERE
					`user_List_x_Metaproduct`.`idList` = '.(int)$chosenList->idList.'
				;
			';
			$list_metaproducts = MCalcUtil::dbrows($sql);
			foreach ( $list_metaproducts as $list_metaproduct ) {
				$metaproductIds[] = (int)$list_metaproduct->idMetaproduct;
			}
			$productIds = array();
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
					`mkm_Product`.`rarity` NOT IN ("Special")
					AND
					`mkm_Product`.`expansion` NOT IN ("International Edition", "Collectors\' Edition")
					AND
					`mkm_Product`.`idMetaproduct` IN ('.implode(',', $metaproductIds).')
			';
			$results = MCalcUtil::dbrows($sql);
			foreach ( $results as $result ) {
				$productIds[] = (int)$result->idProduct;
			}
			find_and_save_articles($productIds);

			$where = array();
			$where[] = 'rarity <> "special"';
			if ( $altered_art_ok === false ) {
				$where[] = 'isAltered = 0';
			}
			if ( !empty($card_languages) ) {
				$where[] = 'a.idLanguage in ( '.implode(',', array_keys($card_languages)).' )';
			}
			if ( !empty($seller_countries) ) {
				$where[] = 'u.country in ( "'.implode('","', $seller_countries).'" )';
			}
			if ( !empty($chosen_conditions) ) {
				$where[] = 'a.`condition` in ("'.implode('","', $chosen_conditions).'")';
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
					and mn.idMetaproduct in ('.implode(', ', $metaproductIds).')

					inner join mkm_Metaproduct as m
					on m.idMetaproduct = p.idMetaproduct

					inner join mkm_User as u
					on a.idSeller = u.idUser

				where 
					'.implode(' AND ', $where).'
					
				ORDER BY
					`idMetaproduct` ASC,
					`price` ASC,
					`idSeller` ASC
				;
			';
			$res = MCalcUtil::dbrows($sql);

			$found_metaproductIds = array();

			$__cards = array();

			foreach ( $res as $row ) {

				$row->idMetaproduct = (int)$row->idMetaproduct;

				$found_metaproductIds[$row->idMetaproduct] = true;

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

			$notfound_metaproductIds = array();
			foreach ( $metaproductIds as $metaproductId ) {
				if ( isset($found_metaproductIds[$metaproductId]) ) {
					// good
				} else {
					$notfound_metaproductIds[$metaproductId] = true;
				}
			}


			$header_scripts = array();
			$header_inline_scripts = array();

			$header_scripts[] = '//ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js';
			ob_start();
			?>
			<script type="text/javascript">
			jQuery('document').ready(function() {
				jQuery('.toggle').each(function() {
					jQuery(this).on('click', function() {
						var id=jQuery(this).attr('for');
						var el=jQuery('#'+id);
						if ( el.is(':visible') ) {
							el.hide();
						} else {
							el.show();
						}
					});
				});
			});
			</script>
			<?php
			$header_inline_scripts[] = ob_get_clean();

			$header_styles = array();
			$header_styles[] = Config::get('siteurl').'/assets/css/style.css';

			$page_content = '';




			$menu_template = new Template('util/menu-loggedin');
			$menu_template->assign('action_url', Config::get('siteurl').'/');
			$menu_template->assign('username', $user->getName());

			$menu_template->assign('menuitems', array(
				array('link' => Config::get('siteurl').'/', 'linktext' => 'Wishlists')
			));
			$page_content .= $menu_template->render();

			// calculate form
			$tmpl = new Template('util/calc-form');
			$tmpl->assign('action_url', Config::get('siteurl').'/lists/'.$chosenList->idList.'/calculate/');
			$tmpl->assign('countrynames', MCalcUtil::CountryNames());
			$tmpl->assign('card_languages', MCalcUtil::CardLanguages());

			$tmpl->assign('countrynames_val', $seller_countries);
			$tmpl->assign('card_languages_val', array_keys($card_languages));
			$tmpl->assign('minimal_condition_val', $minimal_condition);
			$tmpl->assign('altered_art_ok_val', $altered_art_ok);

			$page_content .= $tmpl->render();

			if ( count($notfound_metaproductIds) > 0 ) {
				$sql = '
					SELECT
						name_en.idMetaproduct,
						name_en.metaproductName,
						name_de.metaproductName AS metaproductNameDe
					FROM
						`mkm_MetaproductName` AS name_en

						LEFT JOIN `mkm_MetaproductName` AS name_de
						ON `name_de`.`idMetaproduct` = `name_en`.`idMetaproduct` AND `name_de`.`idLanguage` = 3
					WHERE
						`name_en`.`idLanguage` = 1
						AND
						`name_en`.`idMetaproduct` IN ('.implode(',', array_keys($notfound_metaproductIds)).')
					ORDER BY
						`name_en`.`metaproductName` ASC
					;
				';
				$notfound_metaproducts = MCalcUtil::dbrows($sql);

				$tmpl = new Template('util/metaproducts-list-simple');
				$tmpl->assign('metaproducts', $notfound_metaproducts);
				$tmpl->assign('headline', 'Mit den gewählten Einstellungen wurden einige Karten nicht gefunden.');

				$page_content .= $tmpl->render();
			}

			if ( count($__cards) > 0 ) {
				$calculator = new MCalcCalculatorV2;
				$results = $calculator->calculateBestPrices($__cards);

				$tmpl = new Template('util/calc-result');
				$tmpl->assign('result', $results->best);
				$tmpl->assign('headline', 'Bestes Ergebnis');
				$tmpl->assign('id', 'best');
				$page_content .= '<div style="background: #90d797">'.$tmpl->render().'</div>';

				$best_results = '';
				foreach ( $results->best_list as $i => $result ) {
					$tmpl = new Template('util/calc-result');
					$tmpl->assign('result', $result);
					$tmpl->assign('headline', 'Result '.($i+1));
					$tmpl->assign('id', 'best_list_'.$i);
					$best_results .= $tmpl->render();
				}
				$page_content .= '<div style="background: #e0efa4">'.$best_results.'</div>';

				$all_results = '';
				foreach ( $results->list as $i => $result ) {
					$tmpl = new Template('util/calc-result');
					$tmpl->assign('result', $result);
					$tmpl->assign('headline', 'Result '.($i+1));
					$tmpl->assign('id', 'list_'.$i);
					$best_results .= $tmpl->render();
				}
				$page_content .= '<div>'.$all_results.'</div>';
			}

			$page_template = new Template('main');
			$page_template->assign('header_scripts', $header_scripts);
			$page_template->assign('header_inline_scripts', $header_inline_scripts);
			$page_template->assign('header_styles', $header_styles);
			$page_template->assign('page_content', $page_content);

			echo $page_template->render();
		}


	}//end calcAction

	public function indexAction() {

		$user = Auth::user();
		if ( empty($user) ) {
			$this->dispatcher->redirect(Config::get('siteurl'));
		}

		$idList = $this->dispatcher->getVar(1);

		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
		switch ( $action ) {
			case 'removeCards':
				$metaproductIds = !empty($_REQUEST['metaproductIds']) ? explode(',', $_REQUEST['metaproductIds']) : array();
				if ( !empty($metaproductIds) ) {
					$cleanMetaproductIds = array();
					foreach ( $metaproductIds as $metaproductId ) {
						$cleanMetaproductIds[] = (int)$metaproductId;
					}
					$sql = '
						DELETE FROM
							`user_List_x_Metaproduct`
						WHERE
							`idList` = '.(int)$idList.'
							AND
							`idMetaproduct` IN ('.implode(',', $cleanMetaproductIds).')
						;
					';
					MCalcUtil::dbquery($sql);
				}
				break;
			case 'addCards':
				$cards = !empty($_REQUEST['cards']) ? $_REQUEST['cards'] : '';

				$cards = MCalcUtil::ReadCardsFromString($cards);
				$cards = array_unique($cards);
				$retval = find_and_save_cards($cards);
				$insert_values = array();
				foreach ( $retval['metaproductIds'] as $idMetaproduct ) {
					$insert_values[] = '('.(int)$idList.', '.(int)$idMetaproduct.')';
				}
				if ( !empty($insert_values) ) {
					$sql = '
						INSERT IGNORE INTO `user_List_x_Metaproduct` (`idList`, `idMetaproduct`)
						VALUES '.implode(', ', $insert_values).'
						;
					';
					MCalcUtil::dbquery($sql);
				}

				break;
		}


		$header_styles = array();
		$header_styles[] = Config::get('siteurl').'/assets/css/style.css';

		$page_content = '';



		$menu_template = new Template('util/menu-loggedin');
		$menu_template->assign('action_url', Config::get('siteurl').'/');
		$menu_template->assign('username', $user->getName());

		$menu_template->assign('menuitems', array(
			array('link' => Config::get('siteurl').'/', 'linktext' => 'Wishlists')
		));
		$page_content .= $menu_template->render();


		$chosenList = null;

		$lists = $user->getLists();
		foreach ( $lists as $list ) {
			if ( $list->idList === $idList ) {
				$chosenList = $list;
				break;
			}
		}

		if ( !empty($chosenList) ) {

			// alle metaproducts holen die in der liste sind
			$sql = '
				SELECT
					name_en.idMetaproduct,
					name_en.metaproductName,
					name_de.metaproductName AS metaproductNameDe
				FROM
					`user_List_x_Metaproduct`

					INNER JOIN `mkm_MetaproductName` AS name_en
					ON `name_en`.`idMetaproduct` = `user_List_x_Metaproduct`.`idMetaproduct`

					LEFT JOIN `mkm_MetaproductName` AS name_de
					ON `name_de`.`idMetaproduct` = `user_List_x_Metaproduct`.`idMetaproduct` AND `name_de`.`idLanguage` = 3
				WHERE
					`name_en`.`idLanguage` = 1
					AND
					`user_List_x_Metaproduct`.`idList` = '.(int)$chosenList->idList.'
				ORDER BY
					`name_en`.`metaproductName` ASC
				;
			';
			$list_metaproducts = MCalcUtil::dbrows($sql);
			foreach ( $list_metaproducts as &$list_metaproduct ) {
				// get most recent product image:
				$sql = '
					SELECT
						`image`
					FROM
						`mkm_Product`
					WHERE
						`idMetaproduct` = '.(int)$list_metaproduct->idMetaproduct.'
						AND
						`mkm_Product`.`rarity` NOT IN ("Special")
						AND
						`mkm_Product`.`expansion` NOT IN ("International Edition", "Collectors\' Edition")
					ORDER BY
						`idProduct` DESC
					LIMIT
						0, 1
					;
				';
				$imageRow = MCalcUtil::dbgetrow($sql);

				$sql = '
					select
						a.price as price

					from
						mkm_Article as a

						inner join mkm_Product as p
						on p.idProduct = a.idProduct

						inner join mkm_User as u
						on a.idSeller = u.idUser

					where 
						a.isAltered = 0
						and
						p.rarity NOT IN ("Special")
						and 
						a.idLanguage in ( 1, 3 )
						and 
						u.country in ( "D", "AT" )
						and
						a.`condition` not in ("PO", "PL")
						and
						p.`idMetaproduct` = '.(int)$list_metaproduct->idMetaproduct.'
					ORDER BY
						`price` ASC
					LIMIT
						0, 1
					;
				';
				$bestpriceRow = MCalcUtil::dbgetrow($sql);
				$list_metaproduct->bestpriceRow = $bestpriceRow;
				$list_metaproduct->imageRow = $imageRow;

			}

			// list of products
			$tmpl = new Template('util/metaproducts-list');
			$tmpl->assign('metaproducts', $list_metaproducts);
			$tmpl->assign('headline', '"'.$chosenList->name.'"');
			$tmpl->assign('url', $this->dispatcher->getUrl());

			$page_content .= $tmpl->render();

			// calculate form
			$tmpl = new Template('util/calc-form');
			$tmpl->assign('action_url', Config::get('siteurl').'/lists/'.$chosenList->idList.'/calculate/');
			$tmpl->assign('countrynames', MCalcUtil::CountryNames());
			$tmpl->assign('card_languages', MCalcUtil::CardLanguages());

			$page_content .= $tmpl->render();





			// add cards form
			$tmpl = new Template('util/add-metaproducts-form');
			$tmpl->assign('action_url', $this->dispatcher->getUrl());

			$page_content .= $tmpl->render();




			$page_template = new Template('main');
			$page_template->assign('header_styles', $header_styles);
			$page_template->assign('page_content', $page_content);

			echo $page_template->render();
		}

	}//end listAction

}