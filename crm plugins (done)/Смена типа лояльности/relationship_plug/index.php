<?php
/* ============================ */
/*		Larshin Oleg 			*/
/*		SalesMan CRM 2018		*/
/* ============================ */

error_reporting(0);

require_once 'params.php';
require_once 'func.php';

//my_log($_POST,"\n Request: "); 


//getClientInfo();
//получим инфу о клиенте:
$clientId = $_POST['newparam']['clid'];


$clientInfo = [
	"login"		=>	$user,
	"apikey"	=>	$apikey,
	"action"	=>	'info',
	"clid"		=>	$clientId,
];


$clientInfoRes = my_http_build($clientbaseurl, $clientInfo);

my_log($clientInfoRes, '\n Инфо клиента:');

//получим список закрытых сделок клиента:
$dealCount = 0; //счетчик сделок


$dealList = [
	"login"		=>	$user,
	"apikey"	=>	$apikey,
	"action"	=>	'list',
	"order" 	=>	'datum',
	//"active" 	=> 	'no',
	"filter"	=>	[
		"clid"		=>	$clientId,
		"close"		=>	'yes'
	],	
];

$dealListRes = my_http_build($dealbaseurl, $dealList);

$dealCount += $dealListRes['count']; //кол-во закрытых сделок

my_log($dealListRes['count'], '\n Количество закрытых сделок:');
my_log($dealListRes, '\n Перечень закрытых сделок:');

//получим список отрытых сделок клиента:
$dealList = [
	"login"		=>	$user,
	"apikey"	=>	$apikey,
	"action"	=>	'list',
	"order" 	=>	'datum',
	"active" 	=> 	'yes',
	"filter"	=>	[
		"clid"		=>	$clientId,
	],	
];

$dealListRes = my_http_build($dealbaseurl, $dealList);
$dealCount += $dealListRes['count']; //кол-во всех сделок

my_log($dealListRes['count'], '\n Количество открытых сделок:');
my_log($dealListRes, '\n Перечень отрытых сделок:');

my_log($dealCount, '\n Счетчик сделок:');
$user = $clientInfoRes['data']['iduser'];

if ($dealCount > 1) {
	$clientUpdate = [
		"login"		=>	$user,
		"apikey"	=>	$apikey,
		"action"	=>	'update',
		"clid"		=>	$clientId,
		"user"		=>	$user,
		"tip_cmr"	=>	'Старый клиент',
	];

	//$clientUpdate = array_merge($clientUpdate, $clientInfoRes['data']);
	my_log($clientUpdate, '\n массив на update:');

	$clientInfoRes = my_http_build($clientbaseurl, $clientUpdate);
}

?>