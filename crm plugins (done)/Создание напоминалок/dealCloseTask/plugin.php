<?php
/* ============================ */
/*		Larshin Oleg 			*/
/*		SalesMan CRM 2019		*/
/* ============================ */

//error_reporting(0);



require_once 'plug_func.php';
include "../../inc/class/Todo.php";
include "../../inc/class/Deal.php";
require_once "../../inc/config.php";
require_once "../../inc/dbconnector.php";
require_once "../../inc/auth_main.php";
require_once "../../inc/settings.php";
require_once "../../inc/func.php";

$identity = $GLOBALS['identity'];
$sqlname  = $GLOBALS['sqlname'];
$iduser1  = $GLOBALS['iduser1'];
$db       = $GLOBALS['db'];




//получим инфу о сделке:
$did = $_REQUEST['did'];
$Deal   = new \Salesman\Deal();
    $dealInfoRes = $Deal -> info($did);	//получаем информацию по сделке

	$status_close = $dealInfoRes['close']['status'];	//получаем статус закрытия сделки

	$isWin = isWinStatusClose($status_close); //true, если сделка завершилась успехом

if ($isWin) { //если статус закрытия сделки - успех, создадим напоминание

    $clientId = $dealInfoRes['client']['clid']; //id клиента

	//Кому назначить напоминание
	$userId = $dealInfoRes['iduser']; 

	//сдвиг даты в днях (+ X дней от текущей даты)
	$shift = 7; 
	$dateTask = dateShift($shift);


	//Время:
	//$time = "16:00";

	//Тема напоминания:
	$title = "Получить фидбэк по сделке";

	//Агенда напоминания:
	$desc = "";

	//Тип напоминания:
	$tip = "Качество / Рекоменда";

	//Параметры напоминания
	$taskParams = [
		"clid"		=>	$clientId,
		"did"		=>	$did,
		"datum"		=>	$dateTask,
	//	"totime"	=>	$time,
		"day"		=>	'yes',
		"title"		=>	$title,
	// "des"		=>	$desc,
		"tip"		=>	$tip,
		"priority"	=>	'2',
		"speed"		=>	'1',
	];

	$Task = new \Salesman\Todo();
		$taskAddRes = $Task -> add($userId, $taskParams);
}


exit();

?>