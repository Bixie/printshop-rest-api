<?php
/* *
 *	Bixie Printshop
 *  bootstrap.php
 *	Created on 16-5-2015 17:31
 *  
 *  @author Matthijs
 *  @copyright Copyright (C)2015 Bixie.nl
 *
 */

$this->module("product")->extend([
	"findOne" => function ($productID) {
		$item = $this->app->db->fetchRow('SELECT * FROM #__bps_product WHERE productID = :productID', [
			'productID' => $productID
		]);
		if (!$item) {
			throw new \BixieLime\ApiException("Product niet gevonden", 404);
		}
		return $item;
	}
]);