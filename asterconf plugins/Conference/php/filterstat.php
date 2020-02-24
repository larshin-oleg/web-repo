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
	"День 1 (оплачено)"             => [
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
	"День 2 (оплачено)"             => [
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
	"Оба дня (оплачено)"            => [
		"tip"     => [$settings['tips']],
		"main"    => [
			"pay"     => true,
			"artikul" => ["3"]
		],
		// дополнительное условие через оператор AND
		"artikul" => []
	],
	"Оплачено Всего"                      => [
		"tip"     => [$settings['tips']],
		"main" => [
			"tip"     => [$settings['tips']],
			"pay" => true
		]
	],
	"День 1 (забронированно)"       => [
		// тип сделки
		"tip"     => [$settings['tips']],
		"main"    => [
			// статус оплаты
			"pay"     => false,
			// артикулы
			"artikul" => [
				"1",
				"3"
			]
		],
		// дополнительное условие через оператор AND
		"artikul" => []
	],
	"День 2 (забронированно)"       => [
		"tip"  => [$settings['tips']],
		"main" => [
			"pay"     => false,
			"artikul" => [
				"2",
				"3"
			]
		]
	],
	"Оба дня (забронированно)"      => [
		"tip"  => [$settings['tips']],
		"main" => [
			"pay"     => false,
			"artikul" => ["3"]
		]
	],
	"Забронировано Всего"       => [
		// тип сделки
		"tip"     => [$settings['tips']],
		"main"    => [
			// статус оплаты
			"pay"     => false,
			// артикулы
			"artikul" => [
				"1",
				"3",
				"2"
			]
		],
		// дополнительное условие через оператор OR
		"artikul" => []
	],
	"Все контакты"                  => [
		"tip"     => [$settings['tips']],
	],

	
	/*"Онлайн (забронированно)"       => [
		"tip"  => [$settings['online']],
		"main" => [
			"pay"     => false,
			"artikul" => ["УО"]
		]
	],
	"Онлайн (оплачено)"             => [
		"tip"     => [$settings['online']],
		"main"    => [
			"pay"     => true,
			"artikul" => ["УО"]
		],
		"artikul" => ["УОБ"]
	],*/
	"Обеды день 1 (оплачено)"       => [
		"tip"     => [$settings['tips']],
		"main"    => [
			"pay"     => true,
			"artikul" => [
				"1",
				"3"
			]
		],
		// дополнительное условие через оператор AND
		"artikul" => [
			"5"
		]
	],
	"Обеды день 2 (оплачено)"       => [
		"tip"     => [$settings['tips']],
		"main"    => [
			"pay"     => true,
			"artikul" => [
				"2",
				"3"
			]
		],
		// дополнительное условие через оператор AND
		"artikul" => [
			"5"
		]
	],
	"Обеды день 1 (забронированно)" => [
		"tip"  => [$settings['tips']],
		"main" => [
			"pay"     => false,
			"artikul" => [
				"1",
				"3"
			]
		],
		// дополнительное условие через оператор AND
		"artikul" => [
			"5"
		]
	],
	
	"Обеды день 2 (забронированно)" => [
		"tip"  => [$settings['tips']],
		"main" => [
			"pay"     => false,
			"artikul" => [
				"2",
				"3"
			]
		],
		// дополнительное условие через оператор AND
		"artikul" => [
			"5"
		]
	],
	
	/*"Банкет (забронированно)"       => [
		"tip"  => [$settings['tips']],
		"main" => [
			"pay"     => false,
			"artikul" => ["Б2"]
		]
	],
	"Банкет (оплачено)"             => [
		"tip"     => [$settings['tips']],
		"main"    => [
			"pay"     => true,
			"artikul" => ["Б2"]
		],
		"artikul" => ["Б2Б"]
	],
	"Гостиница (забронированно)"    => [
		"tip"  => [$settings['tips']],
		"main" => [
			"pay"     => false,
			"artikul" => [
				"Г1",
				"Г2"
			]
		]
	],
	"Гостиница (оплачено)"          => [
		"tip"  => [$settings['tips']],
		"main" => [
			"pay"     => true,
			"artikul" => [
				"Г1",
				"Г2"
			]
		]
	],*/
];





//require_once $rootpath."/opensource/class/php-excel.class.php";

//print_r($_REQUEST);





$email_text = "";


foreach ($filters as $key => $filter) { //Проход по Всему массиву фильтров
	$sort = [];
	
	$i = 0; //В каждой итерации обнуляем счетчик контактов
	

	if ($key == "Оба дня (оплачено)") {
		$email_text .= "<br> Подробная статистика: <br>";
	} elseif ($key == "Обеды день 1 (оплачено)") {
		$email_text .= "<br> Статистика по обедам: <br>";
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

	print $query;
	print "<br> ------------------- <br>";
	//exit();

	$res = $db -> query($query); //Выполняем запрос к БД 
	
	while($da = $db -> fetch($res)){

		$client = current_client($da['clid']);

		$pids = yexplode(";", $da['pid_list']);
		$i += count($pids);
		print_r($pids);
		print "счетчик: ".$i;
		print "<br> *************** <br>";
		

	}

	

	$email_text .= $key.' : '.$i."<br />"; //Дописываем тело письма
	//echo $key.' : <br> '.print_r($filter,true)."<br /><br />*****************<br /><br />";
	

}



//Пареметры отправки письма:
$email_text .= "<br> $_SERVER[HTTP_HOST] в ". date("Y.m.d H:i")." <br>"; //Футер тела письма
$email_theme = "Статистика оплат $_SERVER[HTTP_HOST]";	//Тема письма
$to = "sergey@voxlink.ru";	//Получатель
//$to = "oleg@salesman.pro";	//Получатель
$toname = '<'.$to.'>';
$from = 'i.shavulskaya@voxlink.ru';	//Отправитель
$fromname = 'Автооповещение mikrotik-training';

mailer($to, $toname, $from, $fromname, $email_theme, $email_text); //отправка письма

echo $email_text;

//print PHP_BINDIR; //Путь к исполняемому файлу php
exit();



