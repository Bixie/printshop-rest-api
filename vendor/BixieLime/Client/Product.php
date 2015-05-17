<?php
/* *
 *	Bixie Printshop
 *  Product.php
 *	Created on 17-5-2015 00:03
 *  
 *  @author Matthijs
 *  @copyright Copyright (C)2015 Bixie.nl
 *
 */
 

namespace BixieLime\Client;

use \Lime;
use \Bixie\ApiClient;
use \BixieLime\ApiException;

/**
 * Class Product
 * @package BixieLime\Client
 */
class Product extends Lime\AppAware {
	/**
	 * @return array
	 */
	public function index () {
		return ['items'=> ['prod1', 'prodhjgjgh2']];
	}

	/**
	 * @param $productID
	 * @return array
	 * @throws ApiException
	 */
	public function item ($productID) {
		$item = [];
		$http = new ApiClient(
			$this->app->retrieve('client/endpoint', ''),
			$this->app->retrieve('client/publickey', ''),
			$this->app->retrieve('client/secret', ''),
			['debug' => $this->app->retrieve('debug', false)]
		);
		if ($request = $http->get('/product/item/', [$productID])) {
			$item = $request->data;
		}
		return ['item'=> $item];
	}

}