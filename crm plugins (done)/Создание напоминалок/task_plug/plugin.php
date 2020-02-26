<?php
/* ============================ */
/*		Larshin Oleg 			*/
/*		SalesMan CRM 2019		*/
/* ============================ */

//error_reporting(0);



require_once 'plug_func.php';
include "../../inc/class/Todo.php";
include "../../inc/class/Client.php";
require_once "../../inc/config.php";
require_once "../../inc/dbconnector.php";
require_once "../../inc/auth_main.php";
require_once "../../inc/settings.php";
require_once "../../inc/func.php";

$identity = $GLOBALS['identity'];
$sqlname  = $GLOBALS['sqlname'];
$iduser1  = $GLOBALS['iduser1'];
$db       = $GLOBALS['db'];



//print_r($_REQUEST);
//my_log($_REQUEST,"\n POST: "); 


//получим инфу о клиенте:
$clientId = $_REQUEST['clid'];
//$clientId = 3567;//Тестовый ID клиента
$Client   = new \Salesman\Client();
    $clientInfoRes = $Client -> info($clientId);
//my_log($clientInfoRes,"\n Инфа по клиенту: ");

$clientDirect = $clientInfoRes['client']['input3'];//Направление клиента

//Кому назначить напоминание
$userId = '6'; //Артем Горлов

if ($clientDirect == "Внедрение Asterisk" && $clientInfoRes['client']['iduser']!=$userId) {


	//сдвиг даты в днях (+ X дней от текущей даты)
	$shift = 3; 
	$dateTask = dateShift($shift);


	//Время:
	$time = "16:00";

	//Тема напоминания:
	$title = "Получить фидбэк по клиенту";

	//Агенда напоминания:
	$desc = "На какой стадии работа с клиентом?";

	//Тип напоминания:
	$tip = "Задача";

	//Параметры напоминания
	$taskParams = [
		"clid"		=>	$clientId,
		"datum"		=>	$dateTask,
		"totime"	=>	$time,
		"title"		=>	$title,
		"des"		=>	$desc,
		"tip"		=>	$tip
	];


	$Task = new \Salesman\Todo();
		$taskAddRes = $Task -> add($userId, $taskParams);
}


exit();

?>