<?php
/* *
 *	Bixie Printshop
 *  Printshop.php
 *	Created on 15-5-2015 19:56
 *  
 *  @author Matthijs
 *  @copyright Copyright (C)2015 Bixie.nl
 *
 */

namespace BixieLime;

use \Lime;
use \Bixie\Authenticate;

class PrintshopApi extends Lime\Helper {

	/**
	 * @param array  $headers
	 * @param array  $params
	 * @param array $payload
	 * @return bool
	 * @throws ApiException
	 */
	public function authenticate ($headers, $params = [], $payload = []) {
		return (new Authenticate($this->app->db, $headers, $params, $payload))->authorize();
	}


}

