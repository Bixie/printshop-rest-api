<?php
//base paths
if (!defined('BIXAPI_DIR'))   			define('BIXAPI_DIR' 			, str_replace(DIRECTORY_SEPARATOR, '/', __DIR__));
if (!defined('BIXAPI_CONFIG_PATH'))   	define('BIXAPI_CONFIG_PATH' 	, BIXAPI_DIR . '/local/config.php');
//merge config
$config = array_replace_recursive([

	'debug' => false,
	'app.name' => 'BixRestApi',
//	'base_url' => '',
//	'base_route' => '',
	'session.name' => md5(__DIR__),
	'sec-key' => 'dg57uhe4c-mgb54-67h7-a514-b4k54rt57e1',
	'database' => [
		"server" => 'localhost',
		"dbname" => '',
		"user" => '',
		"pass" => '',
		"prefix" => ''
	],
	'client' => [
		"endpoint" => '',
		"publickey" => '',
		"secret" => ''
	],
	'paths' => [
		'#root' => BIXAPI_DIR,
		'site' => ''
	],
	'helpers' => [
		'printshopapi' => 'BixieLime\\PrintshopApi'
	]

], file_exists(BIXAPI_CONFIG_PATH) ? include(BIXAPI_CONFIG_PATH) : []);

if ($config['debug']) {
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	setcookie('XDEBUG_SESSION', 'PHPSTORM', time() + (86400 * 30), "/");
}

//check joomla
if (!defined('BIXAPI_PRINTSHOP_ROOT'))  define('BIXAPI_PRINTSHOP_ROOT'  , $config['paths']['site']);
if (!file_exists(BIXAPI_PRINTSHOP_ROOT . '/administrator/components/com_bixprintshop/bixprintshop.php')) {
	throw new \BixieLime\ApiException("Geen printshop installatie gevonden!", 500);
}



