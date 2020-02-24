<?php
error_reporting(E_ERROR);

$rootpath = realpath(__DIR__.'/../../../');

//Класс для работы с документов
require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth_main.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";

require_once $rootpath."/modules/contract/Docgen.php";
require_once $rootpath."/inc/class/Deal.php";

echo "Conference. Ok";

ob_flush();
flush();

$settings = json_decode(file_get_contents($rootpath."/plugins/Conference/data/settings.json"), true);

function logIt($array, $name) { //Запись массива в файл

	$string = is_array($array) ? array2string($array) : $array;
	file_put_contents($GLOBALS['rootpath'].'/cash/Conference.log', current_datumtime()."$name\n$string\n\n", FILE_APPEND);

}

$did      = $_REQUEST['did'];
$stepOld  = $_REQUEST['stepOld'];
$stepNew  = $_REQUEST['stepNew'];

//Проверка на этап сделки
If ($stepOld < 80 && $stepNew >= 80) {

	$Deal       = new \Salesman\Deal();
	$dealresult = $Deal -> info($did);

	//logIt($dealresult, "\n Инфа по сделке:");

	//Переменные :

	$clid = $dealresult['client']['clid'];
	$mcid = $dealresult['mcid'];

	//id шаблона документа
	$params['template'] = $settings['template'];

	//id типа документа
	$idtype   = 13;
	$identity = 1;
	//logIt("\n".$idtype);

	/**
	 * Получаем массив контактов по сделке
	 */
	$persons = yexplode(";", getDogData($did, "pid_list"));

	//logIt($persons, "\npersons:");

	foreach ($persons as $pid) {

		//массив данных по контакту
		$p = get_person_info($pid, "yes");
		//logIt($p, "\n массив данных по контакту");

		//телефоны
		$t = yexplode(",", $p['tel']);
		$m = yexplode(",", $p['mob']);
		//logIt($pid, "\nID контакта в итерации:");

		//сводный массив тел + мобильный
		$tel = array_merge($t, $m);

		//добавим новый документ
		$document = new \Salesman\Docsgen();
		$arg      = [
			"did"    => $did,
			//Тип документа
			"idtype" => $idtype,
			// Прикрепляем к документу
			"append" => true,
			// Генерируем PDF
			"getPDF" => "yes"
		];
		$response = $document -> edit(0, $arg);
		//id созданного документа (билета)
		$id = $response['id'];

		/**
		 * Получаем массив тэгов
		 * id - идентификатор документа
		 * did - идентификатор сделки
		 * clid - идентификатор клиента
		 * mcid - идентификатор компании (наши реквизиты)
		 */

		$params['tags'] = getNewTag($id, $did, $clid, $mcid);

		//Заменим существующие тэги на собственные значения
		$params['tags']['personFperson'] = $p['person'];
		$params['tags']['personFmail']   = yexplode(",", $p['mail'], 0);
		$params['tags']['personFtel']    = $tel[0];
		$params['tags']['personFinput6'] = $p['input6'];
		//logIt($params['tags'], "\n Массив params[tags]:");

		//сгенерируем документ с реквизитами конкретного контакта
		$params['append'] = true;
		$file             = $document -> generate($response['id'], $params);
		//logIt("\n Сгенерили документ!");

		$ticket_info = $document -> info($id);
		//logIt($ticket_info, "Данные по нновому доку: ");

		//Запишем в БД для таблицы salesman_conference_tickets значения: pid = $pid, ticket = $ticket_info[number], deid = $ticket_info[deid]
		$queryDB = $db -> query("INSERT INTO conference_tickets SET ?u", [
			"pid"    => $pid,
			"conf"   => $settings['direction'],
			"ticket" => $ticket_info['number'],
			"deid"   => $ticket_info['deid']
		]);
		//logIt($db -> lastQuery);

		//отправляем документ по Email
		$marg = [
			//id сотрудника, от имени которого делаем отправку
			"iduser"   => $settings['iduser'],
			//"email"    => $params['tags']['personFmail'],
			"template" => $settings['template'],
			//можно удалить, т.к. есть текст по-умолчанию
			"theme"    => $settings['theme'],//"Билет Asterconf 2018",
			"email"    => ["pid:".$pid],
			//можно удалить, т.к. есть текст по-умолчанию
			"content"  => $settings['ticket']
		];

		$send = $document -> mail($response['id'], $marg);
		//logIt($send, "\n отправили документ!");

	}

}