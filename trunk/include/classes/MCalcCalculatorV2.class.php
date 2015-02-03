<?php



class MCalcCalculatorV2 {

	private $__seller_names = array();
	private $__seller_countries = array();

	public static function ShippingCostData() {

		// 1-4 Karten

		// DE: 0.92
		// DK: 2.24
		// ES: 1.77
		// FR: 1.25
		// IT: 1.25
		// AT: 2.00

		// ab 5 Karten:
		// kostet mehr

		// ab 30 euro:
		// kostet mehr (versicherter versand)

		return array(
			'__default__' => array(2, 4, 8),
			'D' => array( 0.92, 2, 6, ),
			'DK' => array( 2.24, 4, 6, ),
			'ES' => array( 1.77, 3, 6, ),
			'IT' => array( 1.25, 3, 6, ),
			'AT' => array( 2.00, 3, 6, ),
			'FR' => array( 1.25, 3, 6, ),
		);
	}

	private function getPseudoShippingCost( $seller, $args = array(), $ignore_empty = false ) {

		$shippingCostData = MCalcCalculatorV2::ShippingCostData();
		$v = isset($shippingCostData[$this->__seller_countries[$seller->seller_id]])
			? $shippingCostData[$this->__seller_countries[$seller->seller_id]]
			: $shippingCostData['__default__'];

		$cost_total = 0;
		$cardcount = 0;
		if ( !empty($seller->cards) ) {
			foreach ( $seller->cards as $card ) {
				if ( $card->sellers[$card->chosen_seller]->seller_id === $seller->seller_id ) {
					$cardcount++;
					$cost_total += $card->sellers[$card->chosen_seller]->cost;
				}
			}
		}
		if ( !empty($args['minus']) ) {
			$cardcount--;
			foreach ( $args['minus']->sellers as $i => $__seller ) {
				if ( $__seller->seller_id === $seller->seller_id ) {
					$cost_total -= $args['minus']->sellers[$i]->cost;
					break;
				}
			}
		}
		if ( !empty($args['plus']) ) {
			$cardcount++;
			foreach ( $args['plus']->sellers as $i => $__seller ) {
				if ( $__seller->seller_id === $seller->seller_id ) {
					$cost_total += $args['plus']->sellers[$i]->cost;
					break;
				}
			}
		}

		if ( empty($cardcount) ) {
			if ( $ignore_empty ) {
				return 0;
			} else {
				return $v[0];
			}
		} else {

			if ( $cost_total >= 30 ) {
				return $v[2];
			} else if ( $cardcount < 5 ) {
				return $v[0];
			} else {
				return $v[1];
			}
		}
	}

	private function makeSolution($solution) {
		$__sellers = array();
		$seller_costs = array();
		$cost = 0;
		foreach ( $solution as $card ) {
			// mcalc_debug(str_pad($card->productName, 32). '  '.str_pad($card->sellers[$card->chosen_seller]->seller_id, 6).'   '.$card->sellers[$card->chosen_seller]->cost);
			if ( empty($__sellers[$card->sellers[$card->chosen_seller]->seller_id]) ) {
				$__sellers[$card->sellers[$card->chosen_seller]->seller_id] = $card->sellers[$card->chosen_seller];
				$__sellers[$card->sellers[$card->chosen_seller]->seller_id]->cards = array();
			}
			$__sellers[$card->sellers[$card->chosen_seller]->seller_id]->cards[] = $card;

			$cost+=$card->sellers[$card->chosen_seller]->cost;
		}

		foreach ( $__sellers as $seller ) {
			$seller_costs[$seller->seller_id] = $this->getPseudoShippingCost($seller, array(), true);
		}

		foreach ( $__sellers as &$__seller ) {
			mcalc_debug($this->__seller_names[$__seller->seller_id]);
			$__seller->name = $this->__seller_names[$__seller->seller_id];
			$__seller->country = $this->__seller_countries[$__seller->seller_id];
			$__seller->shipping_cost = $seller_costs[$__seller->seller_id];
		}

		// Versandkosten berechnen
		$total_shipping_cost = array_sum($seller_costs);
		mcalc_debug('-------------------------------------------');
		mcalc_debug(str_pad('Karten:', 38). '     '.$cost);
		mcalc_debug(str_pad('Versand:', 38). '     '.$total_shipping_cost);
		$cost += $total_shipping_cost;
		mcalc_debug(str_pad('TOTAL:', 38). '     '.$cost);



		$sol = new stdClass;
		$sol->sellers = $__sellers;
		$sol->total_cost = $cost;
		return $sol;
	}

	private function _loadSellerInfos( array $cards = array() ) {

		$this->__seller_names = array();
		$this->__seller_countries = array();

		// fetch all seller infos for all cards
		$all_seller_ids = array();
		foreach ( $cards as $i => $card ) {
			foreach ( $card->sellers as $j => $seller ) {
				$all_seller_ids[$seller->seller_id] = true;
			}
		}

		$seller_rows = MCalcUtil::dbrows('
			SELECT
				`mkm_User`.`idUser` as `seller_id`,
				`mkm_User`.`username` as `name`,
				`mkm_User`.`country` as `country`
			FROM
				`mkm_User`
			WHERE
				`mkm_User`.`idUser` IN ('.implode(',', array_keys($all_seller_ids)).')
			;
		');
		foreach ( $seller_rows as $seller_row ) {
			$this->__seller_names[$seller_row->seller_id] = $seller_row->name;
			$this->__seller_countries[$seller_row->seller_id] = $seller_row->country;
		}
		unset($seller_rows);
		unset($all_seller_ids);

	}
	public function calculateBestPrices( array $cards = array() ) {

		$this->_loadSellerInfos($cards);

		$sols = array();
		$best_sols = array();


		$best_solution = false;
		$best_solution_cost = false;


		// 0. remove all sellers that do not help in finding a solution 

		// - fuer jede karte werden alle verkaeufer durchgegangen
		// - die verkaeufer werden "global" gespeichert, mit infos
		//   => anzahl der karten, die der verkaeufer anbietet
		//   => gesamtpreis fuer alle karten
		// - ausserdem wird fuer jede karte ein array festgehalten, welches fuer jeden verkaeufer die kosten festhaelt
		$sellers = array();
		foreach ( $cards as $i => $card ) {

			// hier werden die preise aller hersteller fuer die aktuelle karte festgehalten
			$cheapers = array();

			// WICHTIG: die seller sind wenn sie hier reinkommen bereits nach dem preis AUFsteigend sortiert!
			foreach ( $card->sellers as $j => $seller ) {

				// wenn es den verkaeufer noch nicht gibt, dann wird er angelegt im globalen array
				if ( !isset($sellers[$seller->seller_id]) ) {
					// initial hat der verkaeufer keine karten und somit keine kosten
					// ausserdem ist kein anderer verkaeufer billiger
					$sellers[$seller->seller_id] = new stdClass;
					$sellers[$seller->seller_id]->seller_id = $seller->seller_id;
					$sellers[$seller->seller_id]->cheapers = array();
					$sellers[$seller->seller_id]->cardcount = 0;
					$sellers[$seller->seller_id]->cardcount_above_shipping_cost = 0;
					$sellers[$seller->seller_id]->cardcount_below_shipping_cost = 0;
					$sellers[$seller->seller_id]->totalcost = 0;
				}

				// der verkaeufer verkauft die aktuelle karte zum aktuellen preis
				// kartenanzahl geht also hoch, sowie auch der preis
				$sellers[$seller->seller_id]->cardcount++;
				$sellers[$seller->seller_id]->totalcost+=$seller->cost;

				if ( $seller->cost > $this->getPseudoShippingCost($seller) ) {
					$sellers[$seller->seller_id]->cardcount_above_shipping_cost++;
				} else {
					$sellers[$seller->seller_id]->cardcount_below_shipping_cost++;
				}

				// alle vorherigen verkaeufer waren fuer diese karte entweder billiger oder gleich billig wie der aktuelle
				// aufgrund von aufsteigender sortierung der verkaeufer nach preis
				foreach ( $cheapers as $seller_id => $seller_cost ) {
					// $sellers[$seller->seller_id]->cheapers enthaelt fuer jeden anderen verkaeufer die anzahl karten, die bei ihm billiger sind
					// $sellers[VK1]->cheapers[VK2] = 5
					// => VK2 hat 5 karten, die bei ihm billiger sind als bei VK1, wobei beide die karten anbieten
					$sellers[$seller->seller_id]->cheapers[$seller_id] = 
						isset($sellers[$seller->seller_id]->cheapers[$seller_id])
						? $sellers[$seller->seller_id]->cheapers[$seller_id]
						: 0;
					// ist beim aktuellen verkaeufer der preis hoeher, dann ist der gerade gepruefte verkaeufer billiger
					if ( $seller->cost > $seller_cost ) {
						$sellers[$seller->seller_id]->cheapers[$seller_id]++;
					}
				}

				$cheapers[$seller->seller_id] = $seller->cost;
			}
		}

		// wir wissen jetzt fuer jeden verkaeufer:
		//   wieviele karten, die wir haben wollen, er verkauft
		//   welche anderen verkaeufer es gibt, die gleiche karten verkaufen, und wieviele davon billiger sind

		$sellers_to_remove = array();

		// jeden verkauefer durchgehen
		foreach ( $sellers as $__seller_id => $__seller ) {

			// jeden billigeren verkaeufer durchgehen
			foreach ( $__seller->cheapers as $cheaper_seller_id => $cheapercount ) {
				// pruefen, ob die anzahl der billiger verkauften karten >= der gesamtzahl der karten des aktuellen verkaeufers entspricht
				// ( = pruefen, ob ALLE karten des aktuellen verkaeufers bei einem anderen verkaeufer billiger zu haben sind )
				if ( $cheapercount >= $__seller->cardcount ) {
					// wenn das der fall ist, kann der verkaeufer entfernt werden
					$sellers_to_remove[$__seller_id] = true;

					// weitermachen mit dem naechsten verkaeufer
					continue 2;
				}
			}
		}
		mcalc_debug(count($sellers_to_remove).'/'.count($sellers).' verkaeufer werden entfernt.');

		// bei allen karten die zu entfernenden verkaeufer entfernen, da nicht relevant
		// gleichzeitig das relevant_sellers array aufbauen
		foreach ( $cards as $card ) {
			$removed = false;
			foreach ( $card->sellers as $i => $seller ) {
				if ( isset($sellers_to_remove[$seller->seller_id]) ) {
					unset($card->sellers[$i]);
					unset($sellers[$seller->seller_id]);
					$removed = true;
				}
			}
			// wenn etwas entfernt wurde, dann die werte neu zuweisen (wegen indexen in dem array)
			if ( $removed ) {
				$card->sellers = array_values($card->sellers);
			}
		}
		unset($sellers_to_remove); 
		// $sellers enthaelt jetzt alle noch relevanten verkaeufer


		$_really_best_solution = false;
		$_really_best_solution_cost = false;

		// MP steht fuer multiplier. 
		// damit werden verschiedene ausgangspositionen geschaffen.
		for ( $MP = 0; $MP < 20; $MP++ ) {


			$__seller_cardcounts = array();
			$__seller_costs = array(); // reine versandkosten
			$__cost = 0;
			$__cards = unserialize(serialize($cards));

			$__sellers = array();

			// 1. find initial by sellers with most cards
			foreach ( $__cards as $__card ) {
				
				$__card->chosen_seller = 0;
				for ( $i = 0; $i < count($__card->sellers); $i++ ) {

					if ( !isset($__seller_cardcounts[$__card->sellers[$i]->seller_id]) ) {
						$__seller_cardcounts[$__card->sellers[$i]->seller_id] = 0;
					}

					if ( false ) {


						$extra_i = 2.0 / $sellers[$__card->sellers[$i]->seller_id]->cardcount * $MP;
						$extra_chosen = 2.0 / $sellers[$__card->sellers[$__card->chosen_seller]->seller_id]->cardcount * $MP;
						if ( $__card->sellers[$i]->cost+$extra_i < $__card->sellers[$__card->chosen_seller]->cost + $extra_chosen ) {
							$__card->chosen_seller = $i;
						}
						
					} else if ( false ) {
						$cc1 = $sellers[$__card->sellers[$i]->seller_id]->cardcount;
						$cc2 = $sellers[$__card->sellers[$__card->chosen_seller]->seller_id]->cardcount;
						$tc1 = $sellers[$__card->sellers[$i]->seller_id]->totalcost;
						$tc2 = $sellers[$__card->sellers[$__card->chosen_seller]->seller_id]->totalcost;


						if ( $cc1 / $cc2 > 10000 ) {
							mcalc_debug($__card->sellers[$i]->seller_id.' hat mehr karten als '.$__card->sellers[$__card->chosen_seller]->seller_id.' ('.
								$cc1 .' > '. $cc2.
								') ');
							$__card->chosen_seller = $i;
						} else if ( $cc1 / $cc2 > 0.3 ) {

							if ( $tc1 < $tc2 ) {
								mcalc_debug($__card->sellers[$i]->seller_id.' hat gleich viele karten wie '.$__card->sellers[$__card->chosen_seller]->seller_id.' ('.
									$cc1 .' == '. $cc2.
									' aber ist billiger... '.
									$tc1 .' < '. $tc2.
									') ');
								$__card->chosen_seller = $i;
							} else {
								// mcalc_debug($__card->sellers[$i]->seller_id.' hat gleich viele karten wie '.$__card->sellers[$__card->chosen_seller]->seller_id.' ('.
								// 	$cc1 .' == '. $cc2.
								// 	' und ist nicht billiger ... '.
								// 	$tc1 .' >= '. $tc2.
								// 	') ');
							}
						}


					} else {

						$cc1 = $sellers[$__card->sellers[$i]->seller_id]->cardcount;
						$cc2 = $sellers[$__card->sellers[$__card->chosen_seller]->seller_id]->cardcount;
						$tc1 = $sellers[$__card->sellers[$i]->seller_id]->totalcost;
						$tc2 = $sellers[$__card->sellers[$__card->chosen_seller]->seller_id]->totalcost;


						// TOOD: wieso 2.0 ?
						$extra_i = $cc1 > 0 ? 2.0 / $cc1 * $MP : 0;
						$extra_chosen = $cc2 > 0 ? 2.0 / $cc2 * $MP : 0;

						
						// der verkaeufer muss mind. ein fuenftel der karten des aktuell gewaehlten verkaeufers haben
						// danach muss er den multiplier check bestehen
						// TOOD: das sieht mir ziemlich random aus... wieso gerade 1/5?

						if ( $cc1 / $cc2 > 0.2 ) {

							if ( $__card->sellers[$i]->cost+$extra_i < $__card->sellers[$__card->chosen_seller]->cost + $extra_chosen ) {
								$__card->chosen_seller = $i;
							}
						}

					}


				}
				$__seller_cardcounts[$__card->sellers[$__card->chosen_seller]->seller_id]++;
				$__cost+=$__card->sellers[$__card->chosen_seller]->cost;

				if ( empty($__sellers[$__card->sellers[$__card->chosen_seller]->seller_id]) ) {
					$__sellers[$__card->sellers[$__card->chosen_seller]->seller_id] = $__card->sellers[$__card->chosen_seller];
					$__sellers[$__card->sellers[$__card->chosen_seller]->seller_id]->cards = array();
				}
				$__sellers[$__card->sellers[$__card->chosen_seller]->seller_id]->cards[] = $__card;

				//mcalc_debug('chose seller: '.$__card->sellers[$__card->chosen_seller]->seller_id);
			}
			foreach ( $__sellers as $seller ) {
				$__seller_costs[$seller->seller_id] = $this->getPseudoShippingCost($seller, array(), true);
			}

			$__cost += array_sum($__seller_costs);
			$best_solution = unserialize(serialize($__cards));
			$best_solution_cost = $__cost;
			$best_seller_cardcounts = unserialize(serialize($__seller_cardcounts));






			// 2. try to make some changes with sellers that have only $x card
			$x = 1;
			$retries = 0;
			do {
				$_cards = unserialize(serialize($best_solution));

				$_cost = 0;
				$_seller_cardcounts = unserialize(serialize($best_seller_cardcounts));
				$_seller_costs = array();
				$_sellers = array();

				foreach ( $_cards as $i => $_card ) {
					
					if ( $_seller_cardcounts[$_card->sellers[$_card->chosen_seller]->seller_id] === $x ) {


						$_bestchange = false;
						$_bestchange_index = false;
						for ( $j = 0; $j < count($_card->sellers); $j++ ) {
							if ( $j === $_card->chosen_seller ) continue;

							$change = $_card->sellers[$j]->cost - $_card->sellers[$_card->chosen_seller]->cost;

							// vorherige versandkosten bei den beiden verkaeufern berechnen:
							$shipping_cost_before = 
								$this->getPseudoShippingCost($_card->sellers[$_card->chosen_seller], array(), true) +
								$this->getPseudoShippingCost($_card->sellers[$j], array(), true);

							// neue versandkosten berechnen:
							$shipping_cost_after =
								$this->getPseudoShippingCost($_card->sellers[$_card->chosen_seller], array('minus' => $_card), true) +
								$this->getPseudoShippingCost($_card->sellers[$j], array('plus' => $_card), true);

							$change += ($shipping_cost_after - $shipping_cost_before);

							if ( $change < 0 ) {
								if ( $_bestchange === false || $change < $_bestchange ) {
									$_bestchange = $change;
									$_bestchange_index = $j;
								}
							}
						}
						if ( $_bestchange_index !== false ) {
							// mcalc_debug('exchanging... CARD #'.$i.', ... '.$_card->chosen_seller .' ('.$_card->sellers[$_card->chosen_seller]->cost.' + '.$this->getPseudoShippingCost().')'.' ===> '. $_bestchange_index.' ('.$_card->sellers[$_bestchange_index]->cost.')');
							$_seller_cardcounts[$_card->sellers[$_card->chosen_seller]->seller_id]--;
							$_seller_cardcounts[$_card->sellers[$_bestchange_index]->seller_id]++;
							$_card->chosen_seller = $_bestchange_index;
						}
					}
					$_cost+=$_card->sellers[$_card->chosen_seller]->cost;


					if ( empty($_sellers[$_card->sellers[$_card->chosen_seller]->seller_id]) ) {
						$_sellers[$_card->sellers[$_card->chosen_seller]->seller_id] = $_card->sellers[$_card->chosen_seller];
						$_sellers[$_card->sellers[$_card->chosen_seller]->seller_id]->cards = array();
					}
					$_sellers[$_card->sellers[$_card->chosen_seller]->seller_id]->cards[] = $_card;
				}
				foreach ( $_sellers as $seller ) {
					$_seller_costs[$seller->seller_id] = $this->getPseudoShippingCost($seller, array(), true);
				}
				$_cost += array_sum($_seller_costs);






				$_cost = 0;
				//$_cards = unserialize(serialize($best_solution));
				//$_seller_cardcounts = unserialize(serialize($best_seller_cardcounts));
				$_seller_costs = array();
				$_sellers = array();
				foreach ( $_cards as $i => $_card ) {

					$_bestchange = false;
					$_bestchange_index = false;
					for ( $j = 0; $j < count($_card->sellers); $j++ ) {
						if ( $j === $_card->chosen_seller ) continue;

						$change = $_card->sellers[$j]->cost - $_card->sellers[$_card->chosen_seller]->cost;


						// vorherige versandkosten bei den beiden verkaeufern berechnen:
						$shipping_cost_before = 
							$this->getPseudoShippingCost($_card->sellers[$_card->chosen_seller], array(), true) +
							$this->getPseudoShippingCost($_card->sellers[$j], array(), true);

						// neue versandkosten berechnen:
						$shipping_cost_after =
							$this->getPseudoShippingCost($_card->sellers[$_card->chosen_seller], array('minus' => $_card), true) +
							$this->getPseudoShippingCost($_card->sellers[$j], array('plus' => $_card), true);

						$change += ($shipping_cost_after - $shipping_cost_before);

						if ( $change < 0 ) {
							if ( $_bestchange === false || $change < $_bestchange ) {
								$_bestchange = $change;
								$_bestchange_index = $j;
							}
						}
					}
					if ( $_bestchange_index !== false ) {
						// mcalc_debug('exchanging... CARD #'.$i.', ... '.$_card->chosen_seller .' ('.$_card->sellers[$_card->chosen_seller]->cost.' + '.$this->getPseudoShippingCost().')'.' ===> '. $_bestchange_index.' ('.$_card->sellers[$_bestchange_index]->cost.')');
						$_seller_cardcounts[$_card->sellers[$_card->chosen_seller]->seller_id]--;
						$_seller_cardcounts[$_card->sellers[$_bestchange_index]->seller_id]++;
						$_card->chosen_seller = $_bestchange_index;
					}
					$_cost+=$_card->sellers[$_card->chosen_seller]->cost;

					if ( empty($_sellers[$_card->sellers[$_card->chosen_seller]->seller_id]) ) {
						$_sellers[$_card->sellers[$_card->chosen_seller]->seller_id] = $_card->sellers[$_card->chosen_seller];
						$_sellers[$_card->sellers[$_card->chosen_seller]->seller_id]->cards = array();
					}
					$_sellers[$_card->sellers[$_card->chosen_seller]->seller_id]->cards[] = $_card;
				}
				foreach ( $_sellers as $seller ) {
					$_seller_costs[$seller->seller_id] = $this->getPseudoShippingCost($seller, array(), true);
				}
				$_cost += array_sum($_seller_costs);






				if ( $_cost < $best_solution_cost ) {
					$best_solution_cost = $_cost;
					$best_solution = unserialize(serialize($_cards));
					$best_seller_cardcounts = unserialize(serialize($_seller_cardcounts));
					$retries = 0;

					$sols[] = $this->makeSolution($best_solution);
					mcalc_debug('current best solution: ' . $best_solution_cost.' EUR');
				} else {
					$retries++;
					mcalc_debug('discarded solution: ' . $_cost.' EUR');
					break;
				}

			} while ( $retries < 60 );

			mcalc_debug('final best solution: ' . $best_solution_cost.' EUR');


			if ( $_really_best_solution_cost === false || $best_solution_cost < $_really_best_solution_cost ) {
				$_really_best_solution_cost = $best_solution_cost;
				$_really_best_solution = $best_solution;

				$best_sols[] = $this->makeSolution($_really_best_solution);
			}



		}


		$sol = $this->makeSolution($_really_best_solution);


		$result = new stdClass;


		$result->list = $sols;
		$result->best = $sol;
		$result->best_list = $best_sols;

		return $result;
	}


}