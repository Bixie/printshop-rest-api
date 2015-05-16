<?php
/* *
 *	Bixie Printshop
 *  Product.php
 *	Created on 16-5-2015 13:38
 *  
 *  @author Matthijs
 *  @copyright Copyright (C)2015 Bixie.nl
 *
 */
 

namespace BixieLime\Get;

use \Lime;

/**
 * Class Product
 * @package BixieLime\Get
 */
class Product extends Lime\AppAware {

	/**
	 * @return array
	 */
	public function index () {
		return ['items'=> ['prod1', 'prod2']];
	}

	/**
	 * @param $productID
	 * @return array
	 */
	public function item ($productID) {
		return $this->module('product')->findOne($productID);
	}

}