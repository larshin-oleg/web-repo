<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*      SalesMan Project        */
/*        www.isaler.ru         */
/*          ver. 2019.2         */
/* ============================ */


use Salesman\Elements;

error_reporting(E_ERROR);

$rootpath = realpath(__DIR__.'/../../../');

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";

$action = $_REQUEST['action'];

$setFile  = "/var/www/crm.mikrotik-training.ru/plugins/Conference/data/settings.json";
$settings = json_decode(file_get_contents($setFile), true);

//print_r($settings);


//exit();

// Фильтры для выгрузки
$filters = [
	"day1"             => [
		"tip"     => [$settings['tips']],
		"main"    => [
			"pay"     => true,
			"artikul" => [
				"1",
				"3"
			]
		],
		// дополнительное условие через оператор AND
		"artikul" => []
	],
	"day2"             => [
		"tip"     => [$settings['tips']],
		"main"    => [
			"pay"     => true,
			"artikul" => [
				"2",
				"3"
			]
		],
		// дополнительное условие через оператор AND
		"artikul" => []
	],

];




$filter = array(); //параметры фильтра
$participant_id = array(); //массив участников


if ($_REQUEST['day'] == "day1") { //выбираем дату розыгрыша
	$filter = $filters['day1'];
} else {
	$filter = $filters['day2'];
}
	
	
	
// направление
$settings['direction'] = '1';


// если указан тип сделки
if($filter['tip'] != '')
	$sort[] = " {$sqlname}dogovor.tip IN (".yimplode(",", $filter['tip']).") ";

// только оплаченные
if(isset($filter['main']['pay']) && $filter['main']['pay'])
	$sort[] = " IFNULL((SELECT SUM(summa_credit) FROM {$sqlname}credit WHERE {$sqlname}credit.did = {$sqlname}dogovor.did AND do = 'on'), 0) = {$sqlname}dogovor.kol ";

// только не оплаченные
if(isset($filter['main']['pay']) && !$filter['main']['pay'])
	$sort[] = " IFNULL((SELECT SUM(summa_credit) FROM {$sqlname}credit WHERE {$sqlname}credit.did = {$sqlname}dogovor.did AND do = 'on'), 0) < {$sqlname}dogovor.kol ";

// только содержащие артикулы
if(isset($filter['main']['artikul']) && !empty($filter['main']['artikul']))
	$sort[] = " (SELECT COUNT(*) FROM {$sqlname}speca WHERE {$sqlname}speca.did = {$sqlname}dogovor.did AND artikul IN (".yimplode(",", $filter['main']['artikul'],"'").")) > 0 ";

$sort = yimplode(" AND ", $sort);

// И содержащие артикулы
if(isset($filter['artikul']) && !empty($filter['artikul']))
	$sort = " ($sort AND (SELECT COUNT(*) FROM {$sqlname}speca WHERE {$sqlname}speca.did = {$sqlname}dogovor.did AND artikul IN (".yimplode(",", $filter['artikul'],"'").")) > 0) ";

//Вытаскиваем инфу о сделках (строим запрос)
$query = "SELECT * FROM {$sqlname}dogovor	WHERE {$sqlname}dogovor.direction = '".$settings['direction']."' AND {$sqlname}dogovor.close != 'yes' AND	$sort AND {$sqlname}dogovor.identity = '".$identity."'"; 


$res = $db -> query($query); //Выполняем запрос к БД 


while($da = $db -> fetch($res)){ //Проход по всем элементам результата запроса (контактам)

	$client = current_client($da['clid']);

	$pids = yexplode(";", $da['pid_list']);
	
	foreach ($pids as $pid) { //Добавляем каждый контакт в массив:
		$participant_id[] = $pid;
		
	}
}

$num = rand(0, count($participant_id) - 1); //ключ массива участников лотереи
$winner_id = $participant_id[$num];

//Получим имя победителя:
$winner = new Salesman\Person();
$winner_info = $winner -> info($winner_id);


$winner_name = "";
$winner_name = $winner_info['person']['input10'].' '.$winner_info['person']['input11'];

if ($winner_name != '' && !empty($_REQUEST)) print "<div id='winner' style='display:none;'><h1>".$winner_name."</h1></div>";

//exit();



