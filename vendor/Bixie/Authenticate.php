<?php
/* *
 *	Bixie Printshop
 *  Authenticate.php
 *	Created on 16-5-2015 13:14
 *  
 *  @author Matthijs
 *  @copyright Copyright (C)2015 Bixie.nl
 *
 */
 

namespace Bixie;

use BixieLime\ApiException;

/**
 * Class Authenticate
 * @package Bixie
 */
class Authenticate {
	/**
	 * @var \ArrayObject
	 */
	protected $headers;
	/**
	 * @var \ArrayObject
	 */
	protected $params;
	/**
	 * @var \ArrayObject
	 */
	protected $payload;
	/**
	 * @var \Bixie\SimpleSql
	 */
	protected $db;
	/**
	 * @var \Bixie\ApiUser
	 */
	protected $apiUser;

	/**
	 * @param \Bixie\SimpleSql $db
	 * @param array $headers
	 * @param array $params
	 * @param array $payload
	 */
	function __construct (SimpleSql $db, $headers, $params, $payload) {
		$this->db = $db;
		$this->headers = new \ArrayObject($headers);
		$this->params = new \ArrayObject($params);
		$this->payload = new \ArrayObject($payload);
	}

	/**
	 * @return bool
	 * @throws \BixieLime\ApiException
	 */
	public function authorize () {
		if (!isset($this->headers['Publickey'])) {
			throw new ApiException("Geen public key in headers!", 401);
		}
		if (!isset($this->headers['Seal'])) {
			throw new ApiException("Geen seal in headers!", 401);
		}
		$user = $this->db->fetchRow('SELECT * FROM #__users WHERE id = :publickey', [
			'publickey' => $this->headers['Publickey']
		]);
		if (!$user) {
			throw new ApiException("Public key niet bekend!", 401);
		}
		$user['publickey'] = $user['id'];
		$user['secret'] = $user['password'];
		$user['rules'] = [];
		$this->apiUser = new ApiUser($user);
		if ($this->headers['Seal'] !== $this->calculateSeal($this->headers['Publickey'], $this->apiUser->secret)) {
			throw new ApiException("Seal niet valide!", 401);
		}
		return true;
	}

	protected function calculateSeal ($public, $secret) {
		$this->params->ksort();
		$this->payload->ksort();
		return sha1($public . $this->params->serialize() . $this->payload->serialize() . $secret);
	}
}

/**
 * Class ApiUser
 * @package Bixie
 */
class ApiUser {
	/**
	 * @var string
	 */
	public $site = '';
	/**
	 * @var string
	 */
	public $name = '';
	/**
	 * @var string
	 */
	public $secret = '';
	/**
	 * @var string
	 */
	public $publickey = '';
	/**
	 * @var array
	 */
	protected $rules = [];

	function __construct ($data) {
		foreach ($data as $key => $value) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		}
	}


}