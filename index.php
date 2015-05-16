<?php
/* *
 *	Bixie Printshop REST API
 *  index.php
 *	Created on 16-5-2015 13:38
 *
 *  @author Matthijs
 *  @copyright Copyright (C)2015 Bixie.nl
 *
 */

spl_autoload_register(function($class){
	$class_path = __DIR__.'/vendor/'.str_replace('\\', '/', $class).'.php';
	if(file_exists($class_path)) include_once($class_path);
});

try {
	include __DIR__ . '/setup.php';

	$app = new BixieLime\App($config);

	//database
	$app->service('db', function() use($app) {
		$client = new \Bixie\SimpleSql(
			$app->retrieve('database/server'),
			$app->retrieve('database/user'),
			$app->retrieve('database/pass'),
			$app->retrieve('database/dbname'),
			$app->retrieve('database/prefix')
		);
		return $client;
	});

	// load modules
	$app->loadModules([
		BIXAPI_DIR.'/modules/core',  # core
		BIXAPI_DIR.'/modules/addons' # addons
	]);

	$app->bindNamespaceMethod('BixieLime\\Get', 'api', 'get');
	$app->bindNamespaceMethod('BixieLime\\Post', 'api', 'post');

} catch (\BixieLime\ApiException $e) {
	if (!isset($app)) {
		$app = new Lime\App();
	}
	$app->get("/*", function() use ($e) {
		$this->response->status = $e->getCode();
		return ["error" => $e->getMessage()];
	});
}

$app->run();

