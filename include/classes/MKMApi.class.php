<?php

class MKMApi {


	const API_URL = 'https://www.mkmapi.eu/ws/v1.1/';

	const ID_LANGUAGE_ENGLISH = 1;
	const ID_LANGUAGE_FRENCH = 2;
	const ID_LANGUAGE_GERMAN = 3;
	const ID_LANGUAGE_SPANISH = 4;
	const ID_LANGUAGE_ITALIAN = 5;

	const ID_GAME_MTG = 1;
	const ID_GAME_YUGIOH = 2;
	const ID_GAME_WOW_TCG = 3;
	const ID_GAME_THE_SPOILS = 4;
	const ID_GAME_TELPERINQUAR = 5;

	const ORDER_SELLER = 1;
	const ORDER_BUYER = 2;

	const ORDER_STATUS_BOUGHT = 1;
	const ORDER_STATUS_PAID = 2;
	const ORDER_STATUS_SENT = 4;
	const ORDER_STATUS_RECEIVED = 8;
	const ORDER_STATUS_LOST = 32;
	const ORDER_STATUS_CANCELLED = 128;


	public function request( $request, $data = false, $method = 'GET' ) {
		if ( !defined('MKM_APP_TOKEN') ||
			 !defined('MKM_APP_SECRET') ||
			 !defined('MKM_ACCESS_TOKEN') ||
			 !defined('MKM_ACCESS_TOKEN_SECRET') ) {
			throw new Exception('Credentials not set');
		}


		$response = new stdClass;
		$response->error = false;
		$response->url = false;
		$response->body = false;
		$response->header = false;
		$response->http_code = false;

		$response->data = false;
		$response->start = false;
		$response->end = false;
		$response->count = false;
		$response->total = false;
		$response->next = false;


		$method             = $method;
		$url                = str_replace(' ', '%20', self::API_URL.$request);
		$appToken           = MKM_APP_TOKEN;
		$appSecret          = MKM_APP_SECRET;
		$accessToken        = MKM_ACCESS_TOKEN;
		$accessSecret       = MKM_ACCESS_TOKEN_SECRET;
		$nonce              = uniqid();
		$timestamp          = time();
		$signatureMethod    = "HMAC-SHA1";
		$version            = "1.0";

		$params = array(
			'realm'                     => $url,
			'oauth_consumer_key'        => $appToken,
			'oauth_token'               => $accessToken,
			'oauth_nonce'               => $nonce,
			'oauth_timestamp'           => $timestamp,
			'oauth_signature_method'    => $signatureMethod,
			'oauth_version'             => $version,
		);

		$baseString = strtoupper($method.'&');
		$baseString .= rawurlencode($url).'&';
		$encodedParams = array();
		foreach ($params as $key => $value) {
			if ("realm" != $key) {
				$encodedParams[rawurlencode($key)] = rawurlencode($value);
			}
		}
		ksort($encodedParams);


		/*
		* Expand the base string by the encoded parameter=value pairs
		*/
		$values = array();
		foreach ($encodedParams as $key => $value) {
			$values[] = $key . "=" . $value;
		}
		$paramsString = rawurlencode(implode("&", $values));
		$baseString .= $paramsString;

		/*
		* Create the signingKey
		*/
		$signatureKey = rawurlencode($appSecret) . "&" . rawurlencode($accessSecret);

		/**
		* Create the OAuth signature
		* Attention: Make sure to provide the binary data to the Base64 encoder
		*
		* @var $oAuthSignature string OAuth signature value
		*/
		$rawSignature = hash_hmac("sha1", $baseString, $signatureKey, true);

		$oAuthSignature = base64_encode($rawSignature);

		/*
		* Include the OAuth signature parameter in the header parameters array
		*/
		$params['oauth_signature'] = $oAuthSignature;

		/*
		* Construct the header string
		*/
		$header = "Authorization: OAuth ";
		$headerParams = array();
		foreach ($params as $key => $value) {
			$headerParams[] = $key . "=\"" . $value . "\"";
		}
		$header .= implode(", ", $headerParams);







		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch, CURLOPT_HEADER, 1);


		if ( $method === 'PUT' ) {
			curl_setopt($ch, CURLOPT_PUT, 1);
		} else if ( $method === 'POST' ) {
			curl_setopt($ch, CURLOPT_POST, 1);
		} else if ( $method === 'DELETE' ) {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		}

		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$result = curl_exec($ch);

		if ( $result ) {


			$info = curl_getinfo($ch);

			$response->body = substr($result, $info['header_size']);
			$response->data = json_decode(json_encode(simplexml_load_string($response->body)));

			$response->header = array();
			$header = substr($result, 0, $info['header_size']);
			$header = preg_split('%[\r\n]+%', $header);
			foreach ( $header as $line ) {
				$split = explode(':', $line, 2);
				$response->header[] = $split;

				if ( $split[0] === 'Range' && !empty($split[1]) ) {
					if ( preg_match('%(\d+)-(\d+)/(\d+)%', $split[1], $m) ) {
						$response->start = (int)$m[1];
						$response->end = (int)$m[2];
						$response->count = $response->end-$response->start+1;
						$response->total = (int)$m[3];
						$response->next = $response->end < $response->total ? $response->end+1 : false;
					}
				}
			}

			$response->http_code = !empty($info['http_code']) ? $info['http_code'] : $response->http_code;
			$response->url = !empty($info['url']) ? $info['url'] : $response->url;

		} else {
			$response->error = curl_error($ch);
		}

		curl_close($ch);

		return $response;

	}












	public function getGames() {


		$games = array();

		$requestResponse = $this->request('games');
		if ( !empty($requestResponse->data->error) || ($requestResponse->http_code<200 || $requestResponse->http_code>=300) ) {
			throw new Exception('HTTPCODE: '.$requestResponse->http_code .', ERROR: '.$requestResponse->data->error);
		}
		if ( !empty($requestResponse->data->game) ) {
			foreach ( $requestResponse->data->game as $game ) {
				$games[] = $game;
			}
		}
		return $games;

	}

	public function getMetaproductById( $idMetaproduct ) {


		$metaproduct = null;

		$requestResponse = $this->request('metaproduct/'.$idMetaproduct);
		if ( !empty($requestResponse->data->error) || ($requestResponse->http_code<200 || $requestResponse->http_code>=300) ) {
			throw new Exception('HTTPCODE: '.$requestResponse->http_code .', ERROR: '.$requestResponse->data->error);
		}
		if ( !empty($requestResponse->data->metaproduct) ) {
			$metaproduct = $requestResponse->data->metaproduct;
		}
		return $metaproduct;
	}

	/**
	 * https://www.mkmapi.eu/ws/documentation/API_1.1:Find_Metaproducts
	 * 
	 * @param $searchString Exakter Name des Metaprodukts
	 * @param $idLanguage   Sprache
	 * @param $idGame       Game
	 *
	 * @return null|stdClass Metaproduct oder null
	 */
	public function getMetaproductBySearch( $searchString, $idLanguage = self::ID_LANGUAGE_ENGLISH, $idGame = self::ID_GAME_MTG ) {


		$metaproduct = null;

		$requestResponse = $this->request('metaproducts/'.$searchString.'/'.$idGame.'/'.$idLanguage);
		if ( !empty($requestResponse->data->error) || ($requestResponse->http_code<200 || $requestResponse->http_code>=300) ) {
			throw new Exception('HTTPCODE: '.$requestResponse->http_code .', ERROR: '.$requestResponse->data->error);
		}
		if ( !empty($requestResponse->data->metaproduct) ) {
			$metaproduct = $requestResponse->data->metaproduct;
		}
		return $metaproduct;

	}


	public function getProductById( $idProduct ) {

		$product = null;

		$requestResponse = $this->request('product/'.$idProduct);
		if ( !empty($requestResponse->data->error) || ($requestResponse->http_code<200 || $requestResponse->http_code>=300) ) {
			throw new Exception('HTTPCODE: '.$requestResponse->http_code .', ERROR: '.$requestResponse->data->error);
		}
		if ( !empty($requestResponse->data->product) ) {
			$product = $requestResponse->data->product;
		}
		return $product;

	

}	public function getProductsBySearch( $searchString, $start = 1, $exact = true, $idLanguage = self::ID_LANGUAGE_ENGLISH, $idGame = self::ID_GAME_MTG ) {

		$products = array();

		do {
			$requestResponse = $this->request('products/'.$searchString.'/'.$idGame.'/'.$idLanguage.'/'.($exact?'true':'false').($start<=0?'':'/'.$start));
			if ( !empty($requestResponse->data->error) || ($requestResponse->http_code<200 || $requestResponse->http_code>=300) ) {
				throw new Exception('HTTPCODE: '.$requestResponse->http_code .', ERROR: '.$requestResponse->data->error);
			}
			$chk = (array)$requestResponse->data->product;
			if ( !empty($chk) ) {
				// wenn nur 1 produkt gefunden wurde, dann ist hier kein array sondern ein objekt -> in ein array packen
				if ( is_object($requestResponse->data->product) ) {
					$requestResponse->data->product = array($requestResponse->data->product);
				}
				foreach ( $requestResponse->data->product as $product ) {
					$products[] = $product;
				}
			}
			$start = $requestResponse->next;

		} while ( $requestResponse->http_code === 206 && $requestResponse->next !== false );

		return $products;

	}

	public function getArticlesByProductId( $idProduct, $start = 1 ) {

		$articles = array();

		do {

			$requestResponse = $this->request('articles/'.$idProduct.($start<=0?'':'/'.$start));
			if ( !empty($requestResponse->data->error) || ($requestResponse->http_code<200 || $requestResponse->http_code>=300) ) {
				throw new Exception('HTTPCODE: '.print_r($requestResponse->http_code, true) .', ERROR: '.print_r($requestResponse->data->error, true));
			}
			$chk = (array)$requestResponse->data->article;
			if ( !empty($chk) ) {
				// wenn nur 1 artikel gefunden wurde, dann ist hier kein array sondern ein objekt -> in ein array packen
				if ( is_object($requestResponse->data->article) ) {
					$requestResponse->data->article = array($requestResponse->data->article);
				}
				foreach ( $requestResponse->data->article as $article ) {
					$articles[] = $article;
				}
			}
			$start = $requestResponse->next;

		} while ( $requestResponse->http_code === 206 && $requestResponse->next !== false );

		return $articles;

	}

	public function getUserById( $idUser ) {

		$user = null;

		$requestResponse = $this->request('user/'.$idUser);
		if ( !empty($requestResponse->data->error) || ($requestResponse->http_code<200 || $requestResponse->http_code>=300) ) {
			throw new Exception('HTTPCODE: '.$requestResponse->http_code .', ERROR: '.$requestResponse->data->error);
		}
		if ( !empty($requestResponse->data->user) ) {
			$user = $requestResponse->data->user;
		}
		return $user;

	}



	public function getOrders( $actor, $status, $start = 1 ) {
		
		$orders = array();

		do {

			$requestResponse = $this->request('orders/'.$actor.'/'.$status.($start<=0?'':'/'.$start));
			if ( !empty($requestResponse->data->error) || ($requestResponse->http_code<200 || $requestResponse->http_code>=300) ) {
				throw new Exception('HTTPCODE: '.$requestResponse->http_code .', ERROR: '.$requestResponse->data->error);
			}
			if ( !empty($requestResponse->data->order) ) {
				foreach ( $requestResponse->data->order as $order ) {
					$orders[] = $order;
				}
			}
			$start = $requestResponse->next;

		} while ( $requestResponse->http_code === 206 && $requestResponse->next !== false );

		return $orders;

	}


	public function getOrderById( $idOrder ) {

		$order = null;

		$requestResponse = $this->request('order/'.$idOrder);
		if ( !empty($requestResponse->data->error) || ($requestResponse->http_code<200 || $requestResponse->http_code>=300) ) {
			throw new Exception('HTTPCODE: '.$requestResponse->http_code .', ERROR: '.$requestResponse->data->error);
		}
		if ( !empty($requestResponse->data->order) ) {
			$order = $requestResponse->data->order;
		}
		return $order;

	}


	// https://www.mkmapi.eu/ws/documentation/Order_Status
	public function setOrderStatus( $idOrder, $orderStatus, $reason = false, $relist = false ) {

		throw new Exception('NotImplementedException');
	}


	public function getShoppingCarts() {

		$shoppingCarts = array();
		$requestResponse = $this->request('shoppingcart');
		if ( !empty($requestResponse->data->error) || ($requestResponse->http_code<200 || $requestResponse->http_code>=300) ) {
			throw new Exception('HTTPCODE: '.$requestResponse->http_code .', ERROR: '.$requestResponse->data->error);
		}
		if ( !empty($requestResponse->data->shoppingCart) ) {
			foreach ( $requestResponse->data->shoppingCart as $shoppingCart ) {
				$shoppingCarts[] = $shoppingCart;
			}
		}
		return $shoppingCarts;

	}

	public function deleteShoppingCarts() {

		throw new Exception('NotImplementedException');

	}



	public function checkoutShoppingCarts() {

		$orders = array();
		$requestResponse = $this->request('shoppingcart/checkout');
		if ( !empty($requestResponse->data->error) || ($requestResponse->http_code<200 || $requestResponse->http_code>=300) ) {
			throw new Exception('HTTPCODE: '.$requestResponse->http_code .', ERROR: '.$requestResponse->data->error);
		}
		if ( !empty($requestResponse->data->order) ) {
			foreach ( $requestResponse->data->order as $order) {
				$orders[] = $order;
			}
		}
		return $orders;

	}


	public function removeArticleFromShoppingCart( $idArticle ) {

		throw new Exception('NotImplementedException');

	}


	public function addArticleToShoppingCart( $idArticle ) {

		throw new Exception('NotImplementedException');

	}

	public function getWantsLists() {

		$wantsLists = array();

		$requestResponse = $this->request('wantslist');
		if ( !empty($requestResponse->data->error) || ($requestResponse->http_code<200 || $requestResponse->http_code>=300) ) {
			throw new Exception('HTTPCODE: '.$requestResponse->http_code .', ERROR: '.$requestResponse->data->error);
		}
		if ( !empty($requestResponse->data->wantsList) ) {
			foreach ( $requestResponse->data->wantsList as $wantsList ) {
				$wantsLists[] = $wantsList;
			}
		}

		return $wantsLists;

	}

	public function getWantsListById( $idWantsList ) {

		$wants = array();

		$requestResponse = $this->request('wantslist/'.$idWantsList);
		if ( !empty($requestResponse->data->error) || ($requestResponse->http_code<200 || $requestResponse->http_code>=300) ) {
			throw new Exception('HTTPCODE: '.$requestResponse->http_code .', ERROR: '.$requestResponse->data->error);
		}
		if ( !empty($requestResponse->data->want) ) {
			foreach ( $requestResponse->data->want as $want ) {
				$wants[] = $want;
			}
		}

		return $wants;

	}

	public function createWantsList( $name, $idGame = self::ID_GAME_MTG ) {


		$wantsLists = array();

		$data = '<?xml version="1.0" encoding="UTF-8" ?>'.
			'<request>'.
				'<wantsList>'.
					'<idGame>'.$idGame.'</idGame>'.
					'<name>'.$name.'</name>'.
				'</wantsList>'.
			'</request>';
		$requestResponse = $this->request('wantslist', $data, 'POST');
		if ( !empty($requestResponse->data->error) || ($requestResponse->http_code<200 || $requestResponse->http_code>=300) ) {
			throw new Exception('HTTPCODE: '.$requestResponse->http_code .', ERROR: '.$requestResponse->data->error);
		}
		if ( !empty($requestResponse->data->wantsList) ) {
			foreach ( $requestResponse->data->wantsList as $wantsList ) {
				$wantsLists[] = $wantsList;
			}
		}
		return $wantsLists;

	}

	public function removeWantsList( $idWantsList ) {


		$removed = false;

		$requestResponse = $this->request('wantslist/'.$idWantsList, false, 'DELETE');
		if ( !empty($requestResponse->data->error) || ($requestResponse->http_code<200 || $requestResponse->http_code>=300) ) {
			throw new Exception('HTTPCODE: '.$requestResponse->http_code .', ERROR: '.$requestResponse->data->error);
		}

		if ( $requestResponse->http_code === 200 ) {
			$removed = true;
		}
		
		return $removed;

	}

	public function addMetaproductToWantsList( $idWantsList, $idMetaproduct, $data ) {

		throw new Exception('NotImplementedException');

	}

	public function addProductToWantsList( $idWantsList, $idProduct, $data ) {

		throw new Exception('NotImplementedException');
		
	}

	public function removeWantFromWantsList( $idWantsList, $idWant ) {

		throw new Exception('NotImplementedException');

	}

	public function editWantInWantsList( $idWantsList, $idWant, $data ) {

		throw new Exception('NotImplementedException');

	}


	public function getStock( $start = 1 ) {
		
		$articles = array();

		do {

			$requestResponse = $this->request('stock'.($start<=0?'':'/'.$start));
			if ( !empty($requestResponse->data->error) || ($requestResponse->http_code<200 || $requestResponse->http_code>=300) ) {
				throw new Exception('HTTPCODE: '.$requestResponse->http_code .', ERROR: '.$requestResponse->data->error);
			}
			if ( !empty($requestResponse->data->article) ) {
				foreach ( $requestResponse->data->article as $article ) {
					$articles[] = $article;
				}
			}
			$start = $requestResponse->next;

		} while ( $requestResponse->http_code === 206 && $requestResponse->next !== false );

		return $articles;

	}


	public function getStockArticleById( $idArticle ) {

		$article = null;

		$requestResponse = $this->request('stock/article/'.$idArticle);
		if ( !empty($requestResponse->data->error) || ($requestResponse->http_code<200 || $requestResponse->http_code>=300) ) {
			throw new Exception('HTTPCODE: '.$requestResponse->http_code .', ERROR: '.$requestResponse->data->error);
		}
		if ( !empty($requestResponse->data->article) ) {
			$article = $requestResponse->data->article;
		}

		return $article;

	}


	public function addStockArticle( $idProduct, $data ) {

		throw new Exception('NotImplementedException');

	}

	public function changeStockArticle( $idArticle, $data ) {

		throw new Exception('NotImplementedException');
	}


	public function increaseStockArticle( $idArticle, $amount ) {

		throw new Exception('NotImplementedException');

	}

	public function decreaseStockArticle( $idArticle, $amount ) {

		throw new Exception('NotImplementedException');

	}

	public function deleteStockArticle( $idArticle ) {

		throw new Exception('NotImplementedException');

	}


}






