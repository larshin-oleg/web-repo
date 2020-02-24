<?php
require_once "func.php";
require_once "../../inc/config.php";
require_once "../../inc/dbconnector.php";
require_once "../../inc/settings.php";
require_once "../../inc/func.php";

$identity = $_REQUEST['identity'];
$sqlname  = $GLOBALS['sqlname'];
$iduser1  = $_REQUEST['iduser1'];
$db       = $GLOBALS['db'];

$clid = $_REQUEST['clid'];
$did = $_REQUEST['did'];
$pid = $_REQUEST['pid'];


echo '<pre>'.print_r($_REQUEST,true).'</pre>';
my_log($_REQUEST, "\n Webhook request:");

/*$Client   = new \Salesman\Client();
	$clientInfo = $Client -> info($clid);
my_log($clientInfo, "\n Client info:");*/

//Получим информацию о сделке:
$Deal   = new \Salesman\Deal();
	$dealInfo = $Deal -> info($did);
my_log($dealInfo, "\n Deal info:");


/*$Person   = new \Salesman\Person();
	$personInfo = $Person -> info($pid);
my_log($personInfo, "\n Person info:");*/

//Формируем массив параметров для отправки на страницу с анкетой клиента
$arrPost = [
	'clid'		=>	$clid,
	'did'		=>	$did,
	'pid'		=>	$pid,
	'pname'		=>	$dealInfo['person'][0]['title'],
	'ptel'		=>	$dealInfo['person'][0]['phone'],
	'pmob'		=>	$dealInfo['person'][0]['mob'],
	'pmail'		=>	$dealInfo['person'][0]['email'],
	'cname'		=>	$dealInfo['client']['title'],
	'ctype'		=>	$dealInfo['client']['type'],
	'recv'		=> 	$dealInfo['client']['recv'],
];

my_log($arrPost, "\n arrPost:");
/*if ($arrPost == "client"){
	$arrPost['recv'] = $dealInfo['client']['recv']; //Если клиент - Юр. лицо, добавляем в массив реквизиты
	//Текст письма для юр лица
	$email_text = "Ссылка на заполнение анкеты: <a href='http://testasterconf.sm-crm.ru/plugins/registerCustomer/ur.php?".$urlparams."'>Заполните анкету юр. лица</a>"; 
}elseif ($arrPost == "person") {
	//Текст письма для физ лица
	$email_text = "Ссылка на заполнение анкеты: <a href='http://testasterconf.sm-crm.ru/plugins/registerCustomer/fiz.php?".$urlparams."'>Заполните анкету физ. лица</a>"; 
}*/

$urlparams = http_build_query($arrPost);// параметры URL для страницы анкеты


//Пареметры отправки письма:
$email_text = "Ссылка на заполнение анкеты: <a href='http://testasterconf.sm-crm.ru/plugins/Asterconf_plugins/anketa.php?".$urlparams."'>Заполните анкету на участие в конференции.</a>"; 
$email_theme = "Анкета участников Asterconf";	//Тема письма
$to = $arrPost['pmail'];	//Получатель
$toname = '<'.$to.'>';
$from = 'i.shavulskaya@voxlink.ru';	//Отправитель
$fromname = 'Команда Asterconf';

$result = mailer($to, $toname, $from, $fromname, $email_theme, $email_text); //отправка письма



?>