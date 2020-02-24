<?php

$rootpath = realpath(__DIR__.'/../../');

//Класс для работы с документов
//require_once $rootpath."/modules/contract/Docgen.php";
/*require_once $rootpath."/inc/class/Deal.php";
require_once $rootpath."/inc/class/Document.php";*/
require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth_main.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";
require_once 'params.php';
require_once 'func.php';

//Класс для генерации штрих-кода:
require_once 'barcode_lib/BarcodeGenerator.php';
require_once 'barcode_lib/BarcodeGeneratorJPG.php';

$identity = $GLOBALS['identity'];
$sqlname  = $GLOBALS['sqlname'];
$iduser1  = $GLOBALS['iduser1'];
$db       = $GLOBALS['db'];



If ($_REQUEST['stepOld'] < 80 && $_REQUEST['stepNew'] >= 80){	//Проверка на этап сделки
	
	//my_log($_REQUEST, "\n Инфа из вебхука:"); 
	$did = $_POST['did'];

	$Deal = new \Salesman\Deal();
		$dealresult = $Deal -> info($did);
	
	//my_log($dealresult, "\n Инфа по сделке:");
	

	//Переменные :

	$template = 13;


	
	$clid = $dealresult['client']['clid'];	
	//$mcid = $dealresult['mcid'];
	$mcid = $dealresult['company']['id'];

	//id шаблона документа
	$params['template'] = $template;

	//id типа документа
	$idtype = 12;
	$identity = 1;
	//my_log("\n".$idtype);

	/**
	 * Получаем массив контактов по сделке
	 */
	$persons = yexplode(";", getDogData($did, "pid_list"));
	
	//my_log($persons, "\npersons:");

	foreach ($persons as $pid) {

		//массив данных по контакту
		$p = get_person_info($pid, "yes");
		//my_log($p, "\n массив данных по контакту");
		//телефоны
		$t = yexplode(",", $p['tel']);
		$m = yexplode(",", $p['mob']);
		//my_log($pid, "\nID контакта в итерации:");
		

		//сводный массив тел + мобильный
		$tel = array_merge($t, $m);


		//добавим новый документ
		$document = new \Salesman\Document();
		$arg      = array(
			"did"    => $did,
			//Тип документа
			"idtype" => $idtype,
			// Прикрепляем к документу
			//"append" => true,
			// Генерируем PDF
			//"getPDF" => "yes",
			//"user"	 =>	$user	
			"iduser" => $iduser1,
		);
		//my_log($arg, "\nАргументы для создания дока:");
		$id = 0;
		$response = $document -> edit($id, $arg);
		//my_log($response, "\nResponce:");
		/**
		 * Получаем массив тэгов
		 * id - идентификатор документа
		 * did - идентификатор сделки
		 * clid - идентификатор клиента
		 * mcid - идентификатор компании (наши реквизиты)
		 */
		$id = $response['id']; //id созданного документа (билета)
		
		
		$params['getPDF'] = 'yes'; //генерируем pdf
		$params['tags'] = getNewTag($id, $did, $clid, $mcid);

		//Создадим штрих-код:
		$barcodeName = $id.'.jpg'; //имя создаваемого файла
		$generator = new \Picqer\Barcode\BarcodeGeneratorJPG();
		$generator -> getBarcode($barcodeName, $params['tags']['docNum'], $generator::TYPE_CODE_128); //генерируем файл штрих-кода, записываем его в папку files
		
		//Заменим существующие тэги на собственные значения
		$params['tags']['personFperson'] = $p['person'];
		$params['tags']['personFmail']   = yexplode(",", $p['mail'], 0);
		$params['tags']['personFtel']    = $tel[0];
		$params['tags']['personFinput6'] = $p['input6'];
		$params['tagsAttached']['images'] = [
			'barcode' => $barcodeName,
		];
		//my_log($params, "\n Массив params:");

		//сгенерируем документ с реквизитами конкретного контакта
		$params['append'] = true;
		$file = $document -> generate($response['id'], $params);
		//my_log("\n Сгенерили документ!");

		$ticket_info = $document ->  info($id);
		//my_log($ticket_info, "Данные по нновому доку: ");
		
		//Запишем в БД для таблицы salesman_conference_tickets значения: pid = $pid, ticket = $ticket_info[number], deid = $ticket_info[deid]
		$sql = "INSERT INTO `conference_tickets` SET `pid`='".$pid."', `ticket`='".$ticket_info['number']."', `deid`='".$ticket_info['deid']."';";
		//my_log($sql);
		$queryDB = $db -> query($sql);

		//отправляем документ по Email
		$marg = [
			//id сотрудника, от имени которого делаем отправку
			"iduser"	=> $iduser1,
			"email"		=> $params['tags']['personFmail'],
			"template"	=> $template,	
			//можно удалить, т.к. есть текст по-умолчанию
			"theme"		=> "Билет MikroTik",
			"email"		=> array("pid:".$pid),
			//можно удалить, т.к. есть текст по-умолчанию
			"content"	=> $ticket_mail,
			"pdf"		=> 'yes',
		];
		$send = $document -> mail($response['id'], $marg);
		//my_log($send, "\n отправили документ!");
	}

	exit();
}