<?php
/* ============================ */
/*		Larshin Oleg 			*/
/*		SalesMan CRM 2019		*/
/* ============================ */

//error_reporting(0);



require_once 'plug_func.php';
include "../../inc/class/Todo.php";
include "../../inc/class/Client.php";
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



//print_r($_REQUEST);
//my_log($_REQUEST,"\n POST: "); 

print 'date("d.m.Y h:i:sa") Request status OK!';

//Параметры:
$iduser = '13'; //Доступ для Дубининой - подставляем id пользователя из БД 
$datePlan = dateShift(30); //Плановая дата смещение в днях указано в скобках


//print $userLogin;

//Рез-тат Webhook:
$dealId = $_REQUEST['did'];

if ($_REQUEST['status'] == 'Победа полная') {
	
	//Получим инфу по сделке:
	$Deal = new \Salesman\Deal();
		$dealInfo = $Deal -> info($dealId);

		//my_log($dealInfo, "\n Сделка: ");


	//Откроем доступ к клиенту:
	$clientId = $dealInfo['client']['clid']; //Получаем id клиента
	$params['userlist'][] = $iduser;

	$Client   = new \Salesman\Client();
    	$clientAccess = $Client -> changeDostup($clientId, $params);

    //my_log($clientAccess, "\n Доступ: ");

    //Создание сделки:
    $dealTitle = "Сделка сопровождения ".$dealInfo['title'];

    $dealParams = [
    	"title"			=>	$dealTitle,
		"clid"			=>	$clientId,
		"idcategory"	=>	'16', //id Этапа 1%
		"datum_plan"	=>	$datePlan, 
		"direction"		=>	$dealInfo['direction'],
		"mcid"			=>	$dealInfo['company']['id'], //ИД компании
		"iduser"		=>	$iduser, //ответственный
		"tip"			=>	'9', //Тип - Сопровождение клиента
    ];

    $newDeal = $Deal -> add($dealParams);

}





exit();

?>