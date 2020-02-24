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

$setFile  = "../data/settings.json";
$settings = json_decode(file_get_contents($setFile), true);

//print_r($settings);
//exit();

// Фильтры для выгрузки
$filters = [
	"Забронировано Оффлайн"       => [
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
		// дополнительное условие через оператор OR
		"artikul" => []
	],
	"День 1 (оплачено)"             => [
		"tip"     => [$settings['tips']],
		"main"    => [
			"pay"     => true,
			"artikul" => [
				"1",
				"3"
			]
		],
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
	"День 2 (оплачено)"             => [
		"tip"     => [$settings['tips']],
		"main"    => [
			"pay"     => true,
			"artikul" => [
				"2",
				"3"
			]
		],
		"artikul" => []
	],
	"Оба дня (забронированно)"      => [
		"tip"  => [$settings['tips']],
		"main" => [
			"pay"     => false,
			"artikul" => ["3"]
		]
	],
	"Оба дня (оплачено)"            => [
		"tip"     => [$settings['tips']],
		"main"    => [
			"pay"     => true,
			"artikul" => ["3"]
		],
		"artikul" => []
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
	"Обеды день 1 (забронированно)" => [
		"tip"  => [$settings['tips']],
		"main" => [
			"pay"     => false,
			"artikul" => [
				"1",
				"3"
			]
		],
		"artikul" => [
			"5"
		]
	],
	"Обеды день 1 (оплачено)"       => [
		"tip"     => [$settings['tips']],
		"main"    => [
			"pay"     => true,
			"artikul" => [
				"1",
				"3"
			]
		],
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
	"Все контакты"                  => [
		"tip"     => [$settings['tips']],
	],
	"Оплачено"                      => [
		"tip"     => [$settings['tips']],
		"main" => [
			"tip"     => [$settings['tips']],
			"pay" => true
		]
	],
];

if ($action == "export.do") {

	$sort = [];
	$list = [];
	$exist = [];

	require_once $rootpath."/opensource/class/php-excel.class.php";

	//print_r($_REQUEST);

	// имя фильтра
	$filterName = $_REQUEST['filter'];

	// направление
	$settings['direction'] = $_REQUEST['direction'];

	// данные фильтра
	$filter = $filters[$filterName];

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

	$query = "
		SELECT *
		FROM
			{$sqlname}dogovor
		WHERE
			{$sqlname}dogovor.direction = '$settings[direction]' AND
			{$sqlname}dogovor.close != 'yes' AND
			$sort AND
			{$sqlname}dogovor.identity = '$identity'
	";

	//print $query;
	//exit();

	$res = $db -> query($query);
	while($da = $db -> fetch($res)){

		$client = current_client($da['clid']);

		$pids = yexplode(";", $da['pid_list']);

		foreach ($pids as $pid) {

			// защита от дублей
			if(!in_array($pid, $exist)) {

				$person = get_person_info($pid, "yes");

				//если контакт удален
				if($person['person'] != '') {

					$tel = yexplode(",", $person['tel']);
					$mob = yexplode(",", $person['mob']);

					$phone = array_merge($mob, $tel);

					$list[] = [
						$pid,
						$person['person'],
						trim(str_replace("+", "", $phone[0])),
						$person['mail'],
						$client
					];

					$exist[] = $pid;

				}

			}

		}

	}

	//print $filterName;
	//print translit(untag($filterName));
	//exit();

	$xls = new Excel_XML('UTF-8', true, 'Data');
	$xls -> addArray($list);
	$xls -> generateXML(translit(untag($filterName)));

	exit();

}

if ($action == "export") {

	require_once $rootpath."/inc/class/Elements.php";
	$element = new \Salesman\Elements();
	?>
	<DIV class="zagolovok"><B>Настройка</B></DIV>
	<form action="php/export.php" method="post" enctype="multipart/form-data" name="sForm" id="sForm">
		<input type="hidden" id="action" name="action" value="export.do">

		<div id="formtabs" style="overflow-y: auto; overflow-x: hidden; max-height: 80vh" class="p5">

			<div class="divider mt10 mb10">Выбор фильтра</div>

			<div class="flex-container box--child pl10 pr10">

				<div class="flex-string wp100">

					<?php
					$string = '';
					foreach ($filters as $index => $filter)
						$string .= '<option value="'.$index.'">'.$index.'</option>';

					print '<select id="filter" name="filter" class="wp100">'.$string.'</select>';
					?>

				</div>

			</div>

			<div class="divider mt10 mb10">Выбор конференции</div>

			<div class="flex-container box--child pl10 pr10">

				<div class="flex-string wp100">

					<?php
					print $element -> DirectionSelect("direction", [
						"noempty"  => true,
						"multiple" => false,
						"class"    => "wp100",
						"sel"      => $settings['direction']
					]);
					?>

				</div>

			</div>

			<div class="space-10"></div>

		</div>

		<hr>

		<div class="text-right button--pane">

			<A href="javascript:void(0)" onClick="exportList()" class="button">Получить</A>&nbsp;
			<A href="javascript:void(0)" onClick="DClose()" class="button">Отмена</A>

		</div>

	</form>

	<script>

		$(document).ready(function () {

			$('#dialog').css('width', '700px').center();

			$(".connected-list").css('height', "120px");
			$(".multiselect").multiselect({sortable: true, searchable: true});

		});

		function exportList() {

			window.open('plugins/Conference/php/export.php?action=export.do&direction=' + $('#direction').val() + '&filter=' + urlEncodeData($('#filter').val()));

		}

	</script>
	<?php

	exit();

}