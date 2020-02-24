<?php
	require_once 'params.php';
	require_once 'func.php';

	require_once "../../inc/config.php";
	require_once "../../inc/dbconnector.php";
	require_once "../../inc/settings.php";
	require_once "../../inc/func.php";

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$iduser1  = $GLOBALS['iduser1'];
	$db       = $GLOBALS['db'];

	//echo '<pre>'.print_r($_REQUEST,true).'</pre>';

	//my_log($_REQUEST, "\n Из формы Физ:");
		
	//Получим список контактов:
	$sql = "SELECT `pid`, `person`, `tel`, `mob`, `mail` FROM `{$sqlname}personcat` WHERE clid='".$_REQUEST['clid']."' AND identity='$identity'";
	//echo "Запрос: ".$sql."<br>";
	$personList = $db -> getAll($sql);

	//echo 'Контакты из ЦРМ: <br><pre>'.print_r($personList,true).'</pre>';
	//my_log($personList, "\n Список контактов:");

	//Обработка массива POST: переписываем массив из формы в многомерный массив по контактам
	$participantList = array();
	$countpers = 0; //счетчик контактов из формы
	while (isset($_REQUEST['name_'.$countpers])) {
		//Создадим многомерный массив с распределением данных по контактам
		$participantList[$countpers] = [
			"clid"			=>	$_REQUEST['clid'],
			"person"		=>	$_REQUEST['name_'.$countpers],
			"tel"			=>	$_REQUEST['phone_'.$countpers],
			"mail"			=>	$_REQUEST['mail_'.$countpers],
			"did"			=>	$_REQUEST['did'],
			"participation"	=>	$_REQUEST['participation_'.$countpers],
			"lunch"			=>	$_REQUEST['lunch_'.$countpers],
			"banket"		=>	$_REQUEST['banket_'.$countpers],
		];

		//Если отмечена гостиница, допишем данные в массив контакта:
		if (isset($_REQUEST['yhotel_'.$countpers]) && $_REQUEST['yhotel_'.$countpers] == 'yes') {
			$participantList[$countpers]["nights"] = $_REQUEST['nights_'.$countpers];
			$participantList[$countpers]["room"] = $_REQUEST['room_'.$countpers];	
		}

		$countpers ++;
	}

	echo 'Контакты с формы: <br><pre>'.print_r($participantList,true).'</pre>';


	//*************************************************************************************
	

	//Проверка на дубли:
	foreach ($participantList as $key => &$value) {
		foreach ($personList as $key_crm => $value_crm) {
			if ($value_crm['tel'] == $value['tel'] || $value_crm['mail'] == $value['mail']) { //Если совпадает номер телефона или email, передаем pid в инфо о контакте
				$value['pid'] = $value_crm['pid'];
				unset($value_crm['pid']);
			}
		}
		//Если в массив передан pid - редактируем контакт, иначе - создаем новый
		if ($value['pid']) {
			$params = array_slice($value, 0, 4); //Отрежем лишние данные, оставим: clid, person, tel, mail
			$Person = new \Salesman\Person(); //Создадим экземпляр класса Person
				$person = $Person -> edit($value['pid'], $params);
			//echo 'Контакт (ред): <br><pre>'.print_r($params,true).'</pre>';
		} else {
			$params = array_slice($value, 0, 4);
			$Person = new \Salesman\Person(); //Создадим экземпляр класса Person
				$person = $Person -> edit(0, $params);
			//echo 'Контакт (нов): <br><pre>'.print_r($params,true).'</pre>';
		}
	}
	//echo 'Контакты из ЦРМ (ред): <br><pre>'.print_r($personList,true).'</pre>';
	


	


	//*************************************************************************************
	
	//Редактируем реквизиты клиента:
	if (isset($_REQUEST['castUrName']) && $_REQUEST['castUrName'] !='') {
	
		$clid = $_REQUEST['clid'];
		$clientparams = [
			"recv"	=>	[
				"castUrName"  	=> $_REQUEST['castUrName'],
		        "castInn"     	=> $_REQUEST['castInn'],
		        "castKpp"     	=> $_REQUEST['castKpp'],
		        "castBank"    	=> $_REQUEST['castBank'],
		        "castBankKs"  	=> $_REQUEST['castBankKs'],
		        "castBankRs"  	=> $_REQUEST['castBankRs'],
		        "castBankBik" 	=> $_REQUEST['castBankBik'],
		        "castDirName" 	=> $_REQUEST['castDirName'],
		        "castDirStatus"	=> $_REQUEST['castDirStatus'],
		        "castUrAddr"	=> $_REQUEST['castUrAddr'],
			],
		];

		$Client = new \Salesman\Client(); //Создадим экземпляр класса Person
					$client = $Client -> update($clid, $clientparams);
	}

	//*************************************************************************************

	//Редактирование сделки:
	$did = $_REQUEST['did']; //id сделки
	//Массив параметров сделки:
	$dealparams = [
		"title_dog"	=> 'Asterconf 2019 '.$clientparams['recv']['castUrName'],
		"pid_list"	=> arraySubSearch($participantList, "pid"),
		"calculate"	=> 'yes',
		"speka"		=>	'',
	];
	
	//Редактируем сделку:
	$Deal = new \Salesman\Deal();
		$dealeditres = $Deal -> update($did, $dealparams);
	
	//echo 'Контакты с формы (1): <br><pre>'.print_r($participantList,true).'</pre>';
	
	//*************************************************************************************
	
	//Добавление спецификации:
	$speka = [];
	$i = 0; //Индекс для спеки
	
	//Пройдемся по списку контактов для заполнения спеки:
	foreach ($participantList as $pvalue) {
		//Выберем формат участия:
		
		switch ($pvalue['participation']) {
		    case "dall":
		        $speka[$i] = [
		        	"prid"		=> '1',
		        	"artikul" 	=> 'У3',
					"title" 	=> "Двухдневное участие в конференции",
					"kol" 		=> '1',
					"comment"	=> $pvalue['person'],
					//"price"		=> '4500',
					//"dop"		=> '1',
					//"price_in"	=>	'0',
					//"edizm"		=>	'ед.',
		        ];
		        break;
		    case "d1":
		        $speka[$i] = [
		        	"prid"		=> '2',
		        	"artikul" 	=> 'У1',
					"title" 	=> "Однодневное участие в конференции (первый день)",
					"kol" 		=> '1',
					"comment"	=> $pvalue['person'],
					//"price"		=> '2900',
					//"dop"		=> '1',
					//"price_in"	=>	'0',
					//"edizm"		=>	'ед.',
		        ];
		        break;
		    case "d2":
		        $speka[$i] = [
		        	"prid"		=> '3',
		        	"artikul" 	=> 'У2',
					"title" 	=> "Однодневное участие в конференции (второй день)",
					"kol" 		=> '1',
					"comment"	=> $pvalue['person'],
					//"price"		=> '2900',
					//"dop"		=> '1',
					//"price_in"	=>	'0',
					//"edizm"		=>	'ед.',
		        ];
		        break;
		    case "online":
		    	$speka[$i] = [
		        	"prid"		=> '4',
		        	"artikul" 	=> 'УО',
					"title" 	=> "Двухдневное участие в конференции (онлайн-трансляция)",
					"kol" 		=> '1',
					"comment"	=> $pvalue['person'],
					//"price"		=> '2900',
					//"dop"		=> '1',
					//"price_in"	=>	'0',
					//"edizm"		=>	'ед.',
		        ];
		    break;
		}
		$i++;
		
		//Если не онлайн-участник
		if ($pvalue['participation'] != "online") {
					//Нужен ли обед?
			switch ($pvalue['lunch']) {
			    case "dall":
			        $speka[$i] = [
			        	"prid"		=> '18',
			        	"artikul" 	=> 'БЛ3',
						"title" 	=> "Бизнес-ланч (оба дня)",
						"kol" 		=> '1',
						"comment"	=> $pvalue['person'],
						//"price"		=> '700',
						//"dop"		=> '1',
						//"price_in"	=>	'0',
						//"edizm"		=>	'ед.',
			        ];
			        $i++;
			        break;
			    case "d1":
			        $speka[$i] = [
			        	"prid"		=> '6',
			        	"artikul" 	=> 'БЛ1',
						"title" 	=> "Бизнес-ланч (1 день)",
						"kol" 		=> '1',
						"comment"	=> $pvalue['person'],
						//"price"		=> '350',
						//"dop"		=> '1',
						//"price_in"	=>	'0',
						//"edizm"		=>	'ед.',
			        ];
			        $i++;
			        break;
			    case "d2":
			        $speka[$i] = [
			        	"prid"		=> '17',
			        	"artikul" 	=> 'БЛ2',
						"title" 	=> "Бизнес-ланч (2 день)",
						"kol" 		=> '1',
						"comment"	=> $pvalue['person'],
						//"price"		=> '350',
						//"dop"		=> '1',
						//"price_in"	=>	'0',
						//"edizm"		=>	'ед.',
			        ];
			        $i++;
			        break;
			    case "no":
			    break;
			}

			//Нужен ли банкет?
			if ($pvalue['banket'] == "yes") {
				$speka[$i] = [
			        	"prid"		=> '5',
			        	"artikul" 	=> 'Б2',
						"title" 	=> "Банкет на второй день",
						"kol" 		=> '1',
						"comment"	=> $pvalue['person'],
						//"price"		=> '3000',
						//"dop"		=> '1',
						//"price_in"	=>	'0',
						//"edizm"		=>	'ед.',
			        ];
			    $i++;

			}

			//Гостиница:
			if ((isset($pvalue['nights']) && $pvalue['nights'] > 0) && $pvalue['room'] == "single") {
				$speka[$i] = [
			        	"prid"		=> '8',
			        	"artikul" 	=> 'Г1',
						"title" 	=> "Место в одноместном номере",
						"kol" 		=> $pvalue['nights'],
						"comment"	=> $pvalue['person'],
						//"price"		=> '2700',
						//"dop"		=> '1',
						//"price_in"	=>	'0',
						//"edizm"		=>	'дней',
			        ];
			    $i++;
			} elseif ((isset($pvalue['nights']) && $pvalue['nights'] > 0) && $pvalue['room'] == "double") {
				$speka[$i] = [
			        	"prid"		=> '7',
			        	"artikul" 	=> 'Г2',
						"title" 	=> "Место в двухместном номере",
						"kol" 		=> $pvalue['nights'],
						"comment"	=> $pvalue['person'],
						//"price"		=> '1700',
						//"dop"		=> '1',
						//"price_in"	=>	'0',
						//"edizm"		=>	'дней',
			        ];
			    $i++;
			}
		}
	}
	$Speka = new \Salesman\Speka();
	
	//Проверка на существование спеки
	$dealinfo = $Deal -> info($did); 
	if (!$dealinfo['speca']) {
		$spekares = $Speka -> mass($did, $speka, true);
		$dealinfo = $Deal -> info($did);
	}
		
	//*************************************************************************************
	//Формирование документов
	//!!!НУЖНА ПРОВЕРКА НАЛИЧИЯ ДОКОВ!!! 
	//if (!isset($dealinfo['contract']) || $dealinfo['contract'] = [] || $dealinfo['contract']['deid'] = '') {
		//Переменные для создания документа:
		$clid = $dealinfo['client']['clid'];	
		$mcid = $dealinfo['mcid'];
		
		//Проверка типа клиента:
		if ($dealinfo['client']['recv']['castType'] == 'client') { //юр лицо:
			$idtype = 1; //id типа документа (договор)
			$docparams['template'] = 22; //id шаблона договора
			$docarg['title '] = "Договор";
		} else { //физ лицо:
			$idtype = 12; //id типа документа (способы оплаты)
			$docparams['template'] = 20; //id шаблона документа
			$docarg['getPDF'] = 'yes';
		}
		
		$document = new \Salesman\Document();
			$docarg = [
				"did"    => $did,
				//Тип документа
				"idtype" => $idtype,
				// Прикрепляем к документу
				//"append" => true,
				// Генерируем PDF
				//"getPDF" => "yes",
				"user"	 =>	$dealinfo['iduser'],	
			];
		$docresponse = $document -> edit(0, $docarg);
		$id = $docresponse['id']; //id текущего документа
		//Генерация файла:
		$docparams['append'] = true;
		$docparams['tags'] = getNewTag($id, $did, $clid, $mcid);
		$docfile = $document -> generate($id, $docparams);
		
		$document_info = $document ->  info($id); 
		$dog_link = $document -> getFiles($document_info['deid']); //Путь к файлу договора
	//}	

	//*************************************************************************************
	//Создание счета:
	$Invoice = new \Salesman\Invoice();
	$invoice_params = [
		"tip"		=> 'print',
		//"download"	=> 'yes',
	];
	$invoice = $dealinfo['invoice'];
	if (!isset($invoice) || $invoice == []) {
		if ($dealinfo['client']['recv']['castType'] == 'client') { //юр лицо:
			$invparams = [
				"igen"		=>	'yes',
				"date"		=>	date('Y-m-d'),
				"date_plan"	=>	dateShift(5),
				"summa"		=>	$dealinfo['summa'],
				"invoice_chek"	=>	$document_info['number'],
				"tip"		=>	'По договору',
				"user"		=>	$dealinfo['iduser'],
			];
		} else { //физ лицо:
			$invparams = [
				"igen"		=>	'yes',
				"date"		=>	date('Y-m-d'),
				"date_plan"	=>	dateShift(5),
				"summa"		=>	$dealinfo['summa'],
				"tip"		=>	'Счет-договор',
				"user"		=>	$dealinfo['iduser'],
			];
		}

		
			$invoice = $Invoice -> add($did, $invparams);
			
	} 

	$invoice_link = $Invoice -> link($invoice[0]['id']); // Путь к файлу счета

	//*************************************************************************************
	
	//Отправка документов на почту:

	$email_text = "Во вложении документы на конференцию"; 
	$email_theme = "Документы Asterconf";	//Тема письма
	$to = $participantList[0]['mail'];	//Получатель
	$toname = '<'.$to.'>';
	$from = 'i.shavulskaya@voxlink.ru';	//Отправитель
	$fromname = 'Команда Asterconf';
	$mail_files = [
		[
			"file" => $invoice_link['result']['file'],
			"name" => $invoice_link['result']['name'],
		],
		[
			"file" => $dog_link['data'][0]['file'],
			"name" => $dog_link['data'][0]['name'],
		],
	];
	mailer($to, $toname, $from, $fromname, $email_theme, $email_text, $mail_files);

	/*echo 'mail_files: <br><pre>'.print_r($mail_files,true).'</pre>';
	echo 'dog_link: <br><pre>'.print_r($dog_link,true).'</pre>';
	
	echo 'id счета: <br><pre>'.print_r($invoice_link,true).'</pre>';*/

	//echo "OK!";


	//exit();
?>

<div>
	<h3 align="center">Спасибо за заявку!</h3>

	<p>Ваша анкета была отправлена. Вам на почту придет письмо с пакетом документов.</p>
	<p>По любым вопросам Вы можете связаться с нашими менеджерами:<br>
		
			<ul>
			email: info@asterconf.ru
			</ul>
			<ul>
			Тел: +7 (495) 373-64-23
			звонки по Москве
			</ul>
			<ul>
			Тел: 8 (800) 333-75-33
			звонки по России
			</ul>
		
	</p>
</div>