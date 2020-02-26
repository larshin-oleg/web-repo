<?php
/* ============================ */
/*		Larshin Oleg 			*/
/*		SalesMan CRM 2018		*/
/* ============================ */

error_reporting(0);

require_once 'func.php';
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


//print_r($_POST);
//my_log($_POST,"\n Request: "); 



//получим инфу о клиенте:
$clientId = $_POST['newparam']['clid'];




$Client   = new \Salesman\Client();
    $clientInfoRes = $Client -> info($clientId);

//my_log($clientInfoRes, "Info client: ");

//получим список закрытых сделок клиента:
$dealCount = 0; //счетчик сделок
$dealClose = $db -> getOne("select count(did) from {$sqlname}dogovor where clid='$clientId' and close='yes' and identity='$identity'");
$dealCount += $dealClose; //кол-во закрытых сделок


//my_log($dealCount, '\n Количество закрытых сделок:');


$dealOpen = $db -> getOne("select count(did) from {$sqlname}dogovor where clid='$clientId' and close='no' and identity='$identity'");
$dealCount += $dealOpen; //кол-во всех сделок

//my_log($dealOpen, '\n Количество открытых сделок:');
//my_log($dealCount, '\n Счетчик сделок:');

if ($dealCount > 1) {
	$clientUpdate = [
		"tip_cmr"	=>	'Старый клиент',
	];


	//my_log($clientUpdate, '\n массив на update:');

	$clientUpdateRes = $Client -> update($clientId, $clientUpdate);
}

exit();

?>