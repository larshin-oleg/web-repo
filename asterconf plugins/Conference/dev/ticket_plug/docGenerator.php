<?php

$rootpath = realpath(__DIR__.'/../../');

//Класс для работы с документов
require_once $rootpath."/modules/contract/Docgen.php";
require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";
require_once 'params.php';
require_once 'func.php';

//my_log("Вошел в скрипт");

If ($_POST['stepOld'] < 80 && $_POST['stepNew'] >= 80){	//Проверка на этап сделки

	//my_log($_POST,"\n Request: "); 
	$did = $_POST['did'];


	$dealparams = [
		"login"		=>	$user,
		"apikey"	=>	$apikey,
		"action"	=>	"info",
		"did"		=>	$did,
		"client"	=>	'yes',
		"payer"		=>	'yes',	
	];

	//my_log($dealparams,"\n Параметры сделки: ");

	$dealresult = getDealInfo($dealparams, $dealbaseurl);	//получаем инфу по сделке (deal.info)
	//my_log($dealresult, "\n Инфа по сделке:");
	//Переменные :

	$template = 24;

	$clid = $dealresult['data']['client']['clid'];	
	$mcid = $dealresult['data']['mcid'];
	$iduser1 = $user;
	

	//id шаблона документа
	$params['template'] = $template;

	//id типа документа
	$idtype = 13;
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
		my_log($p, "\n массив данных по контакту");
		//телефоны
		$t = yexplode(",", $p['tel']);
		$m = yexplode(",", $p['mob']);
		//my_log($pid, "\nID контакта в итерации:");
		

		//сводный массив тел + мобильный
		$tel = array_merge($t, $m);



		//добавим новый документ
		$document = new \Salesman\Docsgen();
		$arg      = array(
			"did"    => $did,
			//Тип документа
			"idtype" => $idtype,
			// Прикрепляем к документу
			"append" => true,
			// Генерируем PDF
			"getPDF" => "yes",
			"user"	 =>	$user	
		);
		$response = $document -> edit(0, $arg);

		/**
		 * Получаем массив тэгов
		 * id - идентификатор документа
		 * did - идентификатор сделки
		 * clid - идентификатор клиента
		 * mcid - идентификатор компании (наши реквизиты)
		 */
		$id = $response['id'];

		$params['tags'] = getNewTag($id, $did, $clid, $mcid);

		//Заменим существующие тэги на собственные значения
		$params['tags']['personFperson'] = $p['person'];
		$params['tags']['personFmail']   = yexplode(",", $p['mail'], 0);
		$params['tags']['personFtel']    = $tel[0];
		$params['tags']['personFinput6'] = $p['input6'];
		//my_log($params['tags'], "\n Массив params[tags]:");

		//сгенерируем документ с реквизитами конкретного контакта
		$params['append'] = true;
		$file = $document -> generate($response['id'], $params);
		//my_log("\n Сгенерили документ!");

		//отправляем документ по Email
		$marg = array(
			//id сотрудника, от имени которого делаем отправку
			"iduser"	=> $iduser1,
			"email"		=> $params['tags']['personFmail'],
			"template"	=> $template,	
			//можно удалить, т.к. есть текст по-умолчанию
			"theme"		=> "Билет Asterconf 2018",
			"email"		=> array("pid:".$pid),
			//можно удалить, т.к. есть текст по-умолчанию
			"content"	=> $ticket_mail
		);
		$send = $document -> mail($response['id'], $marg);
		my_log($send, "\n отправили документ!");
	}
}