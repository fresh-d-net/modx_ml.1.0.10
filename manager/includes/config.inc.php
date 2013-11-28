<?php
/**
 * MODx Configuration file
 */
$database_type = 'mysql';
$database_server = 'localhost';
$database_user = 'u_berdoart';
$database_password = 'X8XZrmWK';
$database_connection_charset = 'utf8';
$database_connection_method = 'SET CHARACTER SET';
$dbase = '`berdoart_db`';
$table_prefix = 'modx_';
error_reporting(E_ALL & ~E_NOTICE);

$lastInstallTime = 1370617737;

$site_sessionname = 'SN51b1f785e2fdf';
$https_port = '443';

if(!defined("MGR_DIR")) define("MGR_DIR", "manager");

// automatically assign base_path and base_url
if(empty($base_path)||empty($base_url)||$_REQUEST['base_path']||$_REQUEST['base_url']) {
	$sapi= 'undefined';
	if (!strstr($_SERVER['PHP_SELF'], $_SERVER['SCRIPT_NAME']) && ($sapi= @ php_sapi_name()) == 'cgi') {
		$script_name= $_SERVER['PHP_SELF'];
	} else {
		$script_name= $_SERVER['SCRIPT_NAME'];
	}
	$a= explode("/manager", str_replace("\\", "/", dirname($script_name)));
	if (count($a) > 1)
		array_pop($a);
	$url= implode("manager", $a);
	reset($a);
	$a= explode("manager", str_replace("\\", "/", dirname(__FILE__)));
	if (count($a) > 1)
		array_pop($a);
	$pth= implode("manager", $a);
	unset ($a);
	$base_url= $url . (substr($url, -1) != "/" ? "/" : "");
	$base_path= $pth . (substr($pth, -1) != "/" && substr($pth, -1) != "\\" ? "/" : "");
}
// assign site_url
$site_url= ((isset ($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || $_SERVER['SERVER_PORT'] == $https_port) ? 'https://' : 'http://';
$site_url .= $_SERVER['HTTP_HOST'];
if ($_SERVER['SERVER_PORT'] != 80)
	$site_url= str_replace(':' . $_SERVER['SERVER_PORT'], '', $site_url); // remove port from HTTP_HOST  
$site_url .= ($_SERVER['SERVER_PORT'] == 80 || (isset ($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || $_SERVER['SERVER_PORT'] == $https_port) ? '' : ':' . $_SERVER['SERVER_PORT'];
$site_url .= $base_url;

if (!defined('MODX_BASE_PATH')) define('MODX_BASE_PATH', $base_path);
if (!defined('MODX_BASE_URL')) define('MODX_BASE_URL', $base_url);
if (!defined('MODX_SITE_URL')) define('MODX_SITE_URL', $site_url);
if (!defined('MODX_MANAGER_PATH')) define('MODX_MANAGER_PATH', $base_path.'manager/');
if (!defined('MODX_MANAGER_URL')) define('MODX_MANAGER_URL', $site_url.'manager/');
if (!defined('MODX_DEBUG')) define('MODX_DEBUG', true);

// start cms session
if(!function_exists('startCMSSession')) {
	function startCMSSession(){
		global $site_sessionname;
		session_name($site_sessionname);
		session_start();
		$cookieExpiration= 0;
		if (isset ($_SESSION['mgrValidated']) || isset ($_SESSION['webValidated'])) {
			$contextKey= isset ($_SESSION['mgrValidated']) ? 'mgr' : 'web';
			if (isset ($_SESSION['modx.' . $contextKey . '.session.cookie.lifetime']) && is_numeric($_SESSION['modx.' . $contextKey . '.session.cookie.lifetime'])) {
				$cookieLifetime= intval($_SESSION['modx.' . $contextKey . '.session.cookie.lifetime']);
			}
			if ($cookieLifetime) {
				$cookieExpiration= time() + $cookieLifetime;
			}
			if (!isset($_SESSION['modx.session.created.time'])) {
				$_SESSION['modx.session.created.time'] = time();
			}
		}
		setcookie(session_name(), session_id(), $cookieExpiration, MODX_BASE_URL);
	}
}


$domain_config = array();
/*$domain_config['mysite.com'] = array(
	'culture_key' => 'ru',
	'site_url' => 'mysite.com',
	'site_start' => 2,
	'site_root' =>  1,
	'error_page' =>  2
);*/

//Default multi-domain settings
if(!$domain_config){
	$domain_config[$_SERVER['HTTP_HOST']]= array(
		'culture_key' => 'ru',
		'site_url' => MODX_SITE_URL,
		'site_start' => 2,
		'site_root' =>  1,
		'error_page' =>  2
	);
}


//массив содержит список путей для автолоада классов
$class_import_path = array(
	MODX_MANAGER_PATH . 'includes/',
	MODX_MANAGER_PATH . 'includes/extenders/',
	MODX_MANAGER_PATH . 'includes/extenders/phpthumb',
);

/**
 * Автозагрузчик
 */
if(!function_exists('autoloader_init')) {
	function autoloader_init(){
		global $class_import_path;

		require_once MODX_MANAGER_PATH . 'includes/autoloader.php';
		spl_autoload_register(array('Autoloader' , 'load'));
		$o_autoloader = new Autoloader();

		foreach($class_import_path as $sItem){
			$o_autoloader->registerPath($sItem);
		}
	}
}

//Exception Handler
function default_exception_handler(Exception $e)
{

	$s_tpl = "
		Что-то случилось</h2>
		<p>[+message+]</p>
		<p><h2><pre>[+trace+]</pre></p>
	";
	$a_replacer = array(
		'[+message+]' => $e->getMessage(),
		'[+trace+]' => print_r($e->getTrace(), true)
	);

	echo str_replace(array_keys($a_replacer), $a_replacer, $s_tpl);
}

set_exception_handler("default_exception_handler");
?>
