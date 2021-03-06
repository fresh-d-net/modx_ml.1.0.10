<?php

/**
 * Файл релизует доступ к некоторой части API modx для ajax запросов
 */

//Проверка революционой бдительности
if ($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
	die("Голодный кролик атакует.");
}
header('Content-Type: text/html; charset=utf-8');
// harden it
require_once('./manager/includes/protect.inc.php');

// initialize the variables prior to grabbing the config file
$database_type = "";
$database_server = "";
$database_user = "";
$database_password = "";
$dbase = "";
$table_prefix = "";
$base_url = "";
$base_path = "";

// get the required includes
if ($database_user == '') {
	if (!$rt = @include_once "manager/includes/config.inc.php") {
		exit('Could not load MODx configuration file!');
	}
}
//стартуем сессию
startCMSSession();

//инклудим парсер
include_once(MODX_MANAGER_PATH.'/includes/document.parser.class.inc.php');

//Ожидаемые данные JSON: {snippet:'action_name', data: {params_name: params_val}}
if(MODX_DEBUG)
	ini_set("display_errors", 1);
else{
	error_reporting(0);
	ini_set("display_errors", 0);
}
//Подобным массивом я исключаю возможность загрузки любого сниппета

$a_res = array();
$a_config = array();

if($a_data = $_REQUEST){//из данной строки следует, что данные должны содержатся в array('data' => );

	if(isset($a_data['snippet'])){

		//Настройки
		$a_config['snippets_available'] = array('feedback', 'getPublications', 'order');//TODO: вынести список в системные настройки
		//проверяем установлено ли разрешение исполдьзовать сниппет
		if(in_array($a_data['snippet'], $a_config['snippets_available'])){
			$a_data['data'] = isset($a_data['data']) ? $a_data['data'] : array();
			//Initial autoloader
			autoloader_init();//this function is contained in  /includes/config.inc.php

			//initiate a new document parser
			$modx = ModExt::app($_is_ajax = true);//replaced to advanced class ModExt(modx+extentions). This class extends DocumentParser and place in /manager/includes/extenders/
			$etomite = &$modx; // for backward compatibility

			// execute the parser if index.php was not included
			$modx->executeParser();

			if(!$a_res[] = $modx->runSnippet($a_data['snippet'], $a_data['data'])){
				$a_res[] = 'Сниппет вернул пустой результат';
			}

		}else{
			$a_res[] = 'Сниппет не принадлежит к списку разрешеных';
		}

	}else{
		$a_res[] = 'Сниппет не указан';
	}

}else{
	$a_res[] = 'Пустой запрос';
}

echo $s_res = implode("\n", $a_res);
?>
