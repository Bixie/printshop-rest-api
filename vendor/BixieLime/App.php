<?php

/*
 * Lime.
 *
 * Copyright (c) 2014 Artur Heinze
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace BixieLime;

use Lime;


class App extends Lime\App {

	/**
	 * Bind namespace to routes
	 * @param  string $namespace
	 * @param  string $alias
	 * @param  string $method
	 * @param bool    $authorize
	 */
	public function bindNamespaceMethod($namespace, $alias = '', $method = 'bind', $authorize = true) {

		$self  = $this;
		$clean = $alias ? $alias : trim(strtolower(str_replace("\\", "/", $namespace)), "\\");

		if (is_callable([$this, $method])) {
			call_user_func_array([$this, $method], [
				'/'.$clean.'/*',
				function() use($self, $namespace, $clean, $authorize) {
					$this->response->mime = 'json';
					try {
						//get action and params
						$parts = explode('/', trim(str_replace($clean, "", $self["route"]), '/'));
						$class = $namespace . '\\' . $parts[0];
						$action = isset($parts[1]) ? $parts[1] : "index";
						$params = count($parts) > 2 ? array_slice($parts, 2) : [];
						//check route
						if (!class_exists($class)) {
							throw new ApiException('Pagina niet gevonden', 404);
						}
						//authorize
						if ($authorize) {
							$this->helper('printshopapi')->authenticate(
								$this->getAllHeaders(),
								$params,
								$this->getPayload()
							);
						}
						//call the controller
						return $self->invoke($class, $action, $params);

					} catch (ApiException $e) {
						$this->response->status = $e->getCode();
						return ["error" => $e->getMessage()];

					} catch (\Exception $e) {
						$this->response->status = 500;
						return ["error" => $e->getMessage()];

					}
				}
			]);
		}

	}

	/**
	 * @return array
	 */
	protected function getAllHeaders () {
		$headers = [];
		foreach($_SERVER as $key => $value) {
			if (substr($key, 0, 5) <> 'HTTP_') {
				continue;
			}
			$header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
			$headers[$header] = $value;
		}
		return $headers;
	}

	/**
	 * @return array
	 */
	protected function getPayload () {
		$inputJSON = file_get_contents('php://input');
		return $inputJSON ? json_decode( $inputJSON, TRUE ) : []; //convert JSON into array
	}
}

