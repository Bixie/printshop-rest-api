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
 

namespace BixieLime\Post;

use \Lime;

class Product extends Lime\AppAware {

	public function index () {
		return ['items'=> ['prod1', 'prod2']];
	}

	public function item ($productID) {
		return ['item'=> ['id'=>$productID, 'name'=>'jaaj']];
	}

}