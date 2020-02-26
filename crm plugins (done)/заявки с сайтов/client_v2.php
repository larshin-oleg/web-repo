<?php
/* ============================ */
/*		Larshin Oleg 			*/
/*		SalesMan CRM 2018		*/
/* ============================ */

error_reporting(0);

require_once 'params.php';
require_once 'func.php';

my_log($_REQUEST, "\nФорма с сайта");

    $client_name = $_REQUEST['pers_name'];
    $client_phone = $_REQUEST['client_phone'];
    $client_mail = $_REQUEST['client_email']; 
    $company = $_REQUEST['urname'];
    $client_inn = $_REQUEST['inn'];
    $client_kpp = $_REQUEST['kpp'];
    $client_bik = $_REQUEST['bik'];
    $client_bank = $_REQUEST['bank_name'];
    $client_rs = $_REQUEST['account'];
    $client_ogrn = $_REQUEST['ogrn']; 
    $client_address = $_REQUEST['Legal_address'];
    $client_poth = $_REQUEST['The_position_of_the_head'];
    $client_head = $_REQUEST['name_head'];


/***************************************************************************************/
//Создание клиента:


$params = [
	"login"		=>	$user,
	"apikey"	=>	$apikey,
	"user"		=>	$user,
	"action"	=>	'add',
	"path"		=>	'Заказ с сайта',
	"type"		=>	'person',
	"title"		=>	$company,
	"mail_url"	=>	$client_mail,
	"phone"		=>	$client_phone
];

//Передача реквизитов:
$recv = [ 
	"title"		=>	$_REQUEST['urname'],
	"type"		=>	'client',
	"recv"		=>	[
		"castUrName"	=>	$company,
		"castInn"		=>	$client_inn,
		"castKpp"		=>	$client_kpp,
		"castBankBik"	=>	$client_bik,
		"castBank"		=>	$client_bank,
		"castBankRs"	=>	$client_rs,
		"castOgrn"		=>	$client_ogrn,
		"castUrAddr"	=>	$client_address,
		"castDirStatus"	=>	$client_poth,
		"castDirName"	=>	$client_head
	]	
];
$params = array_merge($params, $recv);




$clientresult = make_client($params, $clientbaseurl);


/***************************************************************************************/
//Создание контакта:


$persparams = [
	"login"		=>	$user,
	"apikey"	=>	$apikey,
	"user"		=>	$user,
	"action"	=>	'add',
	"clid"		=>	$clientresult['data'],
	"person"	=>	$client_name,
	"mail"		=>	$client_mail,
	"tel"		=>	$client_phone,
	"mperson"	=>	'yes',
];

my_log($persparams, "\nПараметры контакта");


$persresult = my_http_build($persbaseurl, $persparams);

/***************************************************************************************/
//Создание сделки:
$dealparams = [
	"login"		=>	$user,
	"apikey"	=>	$apikey,
	"user"		=>	$user,
	"action"	=>	'add',
//Параметры для создания сделки:
	"title"		=>	'Заявка Москлимат '.$clientresult['data'],
	"clid"		=>	$clientresult['data'],
	"step"		=>	'40', // Этап сделки
	"pid_list"	=>	$persresult['data'], //Прикрепление контакта к сделке
	"direction"	=>	'Продажа и монтаж',
	"mcid"		=>	"2"
];


$dealresult = my_http_build($dealbaseurl, $dealparams);



/***************************************************************************************/
//Создание напоминания:
$time = myGetTime(); //Время напоминания

$taskparams = [
	"login"		=>	$user,
	"apikey"	=>	$apikey,
	"user"		=>	$user,
	"action"	=>	'add',
	"did"		=>	$dealresult['data'],
	"clid"		=>	$clientresult['data'],
	"title"		=>	"Связаться с клиентом",
	"des"		=>	"Добавлен клиент. Уточнить детали заказа",
	"totime"	=>	$time	
];




$taskresult = my_http_build($taskbaseurl, $taskparams);

/***************************************************************************************/
exit();
?>