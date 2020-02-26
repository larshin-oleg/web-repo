<?php
/* ============================ */
/*		Larshin Oleg 			*/
/*		SalesMan CRM 2018		*/
/* ============================ */

error_reporting(0);

require_once 'params.php';
require_once 'func.php';


arrayToFile($_POST,"Request: "); //Пишем в лог запрос с формы

$participation = $_POST['participation'];
$tickets = $_POST['tickets'];
$nights = $_POST['nights'];

//***************************************************************************************
//Создание клиента:
$params = [
	"login"		=>	$user,
	"apikey"	=>	$apikey,
	"user"		=>	$user,
	"action"	=>	'add',
	"path"		=>	'Заказ с сайта',
	"type"		=>	'person',
	"title"		=>	$_POST['company'],
	"mail_url"	=>	$_POST['email'],
	"phone"		=>	$_POST['phone']
];
//Передача реквизитов:
if(isset($_POST['urname']) && $_POST['urname'] != ""){ //Если Юр лицо, то Название клиента = Юр. названию
	unset($params['title']);
	
	$recv = [ 
		"title"		=>	$_POST['urname'],
		"type"		=>	'client',
		"recv"		=>	[
			"castUrName"	=>	$_POST['urname'],
			"castInn"		=>	$_POST['inn'],
			"castKpp"		=>	$_POST['kpp'],
			"castBankBik"	=>	$_POST['bik'],
			"castBank"		=>	$_POST['bank_name'],
			"castBankRs"	=>	$_POST['account'],
			"castOgrn"		=>	$_POST['ogrn'],
			"castUrAddr"	=>	$_POST['Legal_address'],
			"castDirStatus"	=>	$_POST['The_position_of_the_head'],
			"castDirName"	=>	$_POST['name_head']
		]	
	];
	$params = array_merge($params, $recv);
}

//arrayToFile($params,"Params client: "); //Проверка отправленных параметров

if(isset($desc)){
	$params['description'] = $desc;
}

$clientresult = make_client($params, $clientbaseurl);


//***************************************************************************************
//Создание контакта:


$persparams = [
	"login"		=>	$user,
	"apikey"	=>	$apikey,
	"user"		=>	$user,
	"action"	=>	'add',
	"clid"		=>	$clientresult['data'],
	"person"	=>	$_POST['name'],
	"mail"		=>	$_POST['email'],
	"tel"		=>	$_POST['phone'],
	"mperson"	=>	'yes',
	"input7"	=>	'Забронировано',
	"input8"	=>	'Asterconf 2018'
];

//доп поле "Участие в конференции"
switch ($participation) {
    case "two days":
    	$persparams['input6'] = "Вживую (оба дня)";
        break;
    case "first day":
    	$persparams['input6'] = "Вживую (1 день)";
        break;
    case "second day":
    	$persparams['input6'] = "Вживую (2 день)";
        break;
    case "online":
    	$persparams['input6'] = "Онлайн";
    break;
}

//Бизнес-ланчи и Банкет:
if(isset($_POST['lunch']) && $_POST['lunch'] == "on" ){
	//Если участие = два дня, в контакте ставим бизнес ланчи на 2 дня, если 1-й или 2-й день - 1 день

	if ($participation == "two days") { 
		$persparams['input3'] = "2 дня";
	} elseif ($participation == "first day" || $participation == "second day") {
		$persparams['input3'] = "1 день";
	}
}


if(isset($_POST['banket']) && $_POST['banket'] == "on" ){
	$persparams['input4'] = "Да";
} else {
	$persparams['input4'] = "Нет";
}

//Гостиница:
if(isset($_POST['hotel']) && $_POST['hotel'] == "on" ){
	$persparams['input5'] = $nights;	
}

//arrayToFile($persparams,"Params person: "); //Проверка отправленных параметров

$persresult = my_http_build($persbaseurl, $persparams);

//***************************************************************************************
//Создание сделки:
$dealparams = [
	"login"		=>	$user,
	"apikey"	=>	$apikey,
	"user"		=>	$user,
	"action"	=>	'add',
//Параметры для создания сделки:
	"title"		=>	'Конференция Asterconf '.$result['data'],
	"clid"		=>	$clientresult['data'],
	"step"		=>	'40', // Этап сделки
	"pid_list"	=>	$persresult['data'], //Прикрепление контакта к сделке
	"direction"	=>	'Основное',
	"mcid"		=>	"2"
];
//Спецификация:

//Участие:
$i = 0;
switch ($participation) {
    case "two days":
    	$dealparams['tip'] = 'Asterconf';
        $dealparams['speka'][$i] = [
        	"artikul" => 1,
			"title" => "Двухдневное участие в конференции",
			"kol" => $tickets,
			"dop" => "1",//доп.множитель, если не используется = 1
			"price" => "2900"
        ];
        break;
    case "first day":
    	$dealparams['tip'] = 'Asterconf';
        $dealparams['speka'][$i] = [
        	"artikul" => 2,
			"title" => "Однодневное участие в конференции (первый день)",
			"kol" => $tickets,
			"dop" => "1",//доп.множитель, если не используется = 1
			"price" => "1900"
        ];
        break;
    case "second day":
    	$dealparams['tip'] = 'Asterconf';
        $dealparams['speka'][$i] = [
        	"artikul" => 3,
			"title" => "Однодневное участие в конференции (второй день)",
			"kol" => $tickets,
			"dop" => "1",//доп.множитель, если не используется = 1
			"price" => "1900"
        ];
        break;
    case "online":
    	$dealparams['tip'] = 'Asterconf - online';
    	$dealparams['speka'][$i] = [
        	"artikul" => 4,
			"title" => "Двухдневное участие в конференции (онлайн-трансляция)",
			"kol" => $tickets,
			"dop" => "1",//доп.множитель, если не используется = 1
			"price" => "1900"
        ];
    break;
}

$i++;

//Бизнес-ланчи и Банкет:
if(isset($_POST['lunch']) && $_POST['lunch'] == "on" ){
	$tickets_lunch = $tickets;
	if ($participation == "two days") { //если двухдневное участие в конференции, то обеды умножаем на 2
		$tickets_lunch = $tickets*2;
	}

	$dealparams['speka'][$i] = [
    	"artikul" => "6",
		"title" => "Бизнес-ланч",
		"kol" => $tickets_lunch,
		"dop" => "1",//доп.множитель, если не используется = 1
		"price" => "300"
    ];
	
	$i++;
}


if(isset($_POST['banket']) && $_POST['banket'] == "on" ){
	$dealparams['speka'][$i] = [
    	"artikul" => "5",
		"title" => "Банкет на второй день",
		"kol" => $tickets,
		"dop" => "1",//доп.множитель, если не используется = 1
		"price" => "3000"
    ];
	
	$i++;
}

//Гостиница:

//количество билетов умножаем на количество ночей
$tickets_hotel = $tickets*$nights;
$hotel_type = $_POST['hotel'];
arrayToFile($_POST,"Request: "); //Пишем в лог запрос с формы
arrayToFile($hotel_type,"Тип отеля: ");

switch ($hotel_type) {
	case "double":
		$dealparams['speka'][$i] = [
	    	"artikul" => 7,
			"title" => "Место в двухместном номере",
			"kol" => $tickets_hotel,
			"dop" => 1,//доп.множитель, если не используется = 1
			"price" => "1500"
	    ];
		$i++;
		break;
	
	case "single":
		$dealparams['speka'][$i] = [
	    	"artikul" => 8,
			"title" => "Место в одноместном номере",
			"kol" => $tickets_hotel,
			"dop" => "1",//доп.множитель, если не используется = 1
			"price" => "2500"
	    ];
		$i++;
		break;
}




arrayToFile($dealparams['speka'],"Спецификация: "); //Проверка отправленных параметров


$dealresult = my_http_build($dealbaseurl, $dealparams);



//***************************************************************************************
//Создание напоминания:
$time = myGetTime(); //Время напоминания

$taskparams = [
	"login"		=>	$user,
	"apikey"	=>	$apikey,
	"user"		=>	$user,
	"action"	=>	'add',
	"did"		=>	$dealresult['data'],
	"clid"		=>	$clientresult['data'],
	"title"		=>	"Связаться с клиентом",
	"des"		=>	"Отправлен счет клиенту; уточнить получил ли он и все ли верно",
	"totime"	=>	$time	
];

//arrayToFile($taskparams,"Params task: "); //Проверка отправленных параметров


$taskresult = my_http_build($taskbaseurl, $taskparams);


//***************************************************************************************



//Создание договора:

if ($params['type'] == 'person') {
	$docparams = [
		"login"		=>	$user,
		"apikey"	=>	$apikey,
		"user"		=>	$user,
		"action"	=>	'add',
		"did"		=>	$dealresult['data'],
		"clid"		=>	$clientresult['data'],
		"idtype"	=>	12,
		"template"	=>	20

	];
}else{
	$docparams = [
		"login"		=>	$user,
		"apikey"	=>	$apikey,
		"user"		=>	$user,
		"action"	=>	'add',
		"did"		=>	$dealresult['data'],
		"clid"		=>	$clientresult['data'],
		"idtype"	=>	1,
		"template"	=>	18

	];
}
//arrayToFile($docparams, "Параметры создания доков: ");
$docresult_gen = my_http_build($docbaseurl, $docparams);



//Отправка договора:

if ($params['type'] == 'person') {
	//Отправка шаблона со способами оплаты:
	$docparams = [
		"login"		=>	$user,
		"apikey"  	=>	$apikey,
		// указываем метод
		"action"  	=>	'mail',
		//id документа
		"id"		=>	$docresult_gen['data'],
		//id сделки
		"did"		=>	$dealresult['data'],
		//форма отправки - в оригинале или в виде PDF
		"pdf"		=>	"yes",
		//тема сообщения
		"theme"		=>	"Способы оплаты Asterconf",
		//содержание сообщения
		"content"	=>	$doc_mail_fiz,
		//id шаблона, если документ еще не сгенерирован
		"template"	=>	20
	];

	$docresult = my_http_build($docbaseurl, $docparams);

}else{
	//отправка договора без спеки:
	$docparams = [
		"login"		=>	$user,
		"apikey"  	=>	$apikey,
		// указываем метод
		"action"  	=>	'mail',
		//id документа
		"id"		=>	$docresult_gen['data'],
		//id сделки
		"did"		=>	$dealresult['data'],
		//форма отправки - в оригинале или в виде PDF
		"pdf"		=>	"yes",
		//тема сообщения
		"theme"		=>	"Договор-акт Asterconf",
		//содержание сообщения
		"content"	=>	$doc_mail,
		//id шаблона, если документ еще не сгенерирован
		"template"	=>	18
	];
	
	$docresult = my_http_build($docbaseurl, $docparams);
}

if ($params['type'] == 'client') {
	$docparams = [
		"login"		=>	$user,
		"apikey"  	=>	$apikey,
		// указываем метод
		"action"  	=>	'update',
		//id документа
		"id"		=>	$docresult_gen['data'],
		//id сделки
		"did"		=>	$dealresult['data'],
		
		"idtype"      => 1,
		"template"	=>	21,
		"pdf"		=>	"yes",
	];

	$docresult_gen = my_http_build($docbaseurl, $docparams);

	//отправка договора со спекой:
	$docparams = [
		"login"		=>	$user,
		"apikey"  	=>	$apikey,
		// указываем метод
		"action"  	=>	'mail',
		//id документа
		"id"		=>	$docresult_gen['data'],
		//id сделки
		"did"		=>	$dealresult['data'],
		//форма отправки - в оригинале или в виде PDF
		"pdf"		=>	"yes",
		//тема сообщения
		"theme"		=>	"Договор-акт Asterconf",
		//содержание сообщения
		"content"	=>	$doc_mail_speka,
		//id шаблона, если документ еще не сгенерирован
		"template"	=>	21
	];

	$docresult = my_http_build($docbaseurl, $docparams);
}

$docparams = [
	"login"		=>	$user,
	"apikey"  	=>	$apikey,
	// указываем метод
	"action"  	=>	'update',
	//id документа
	"id"		=>	$docresult_gen['data'],
	//id сделки
	"did"		=>	$dealresult['data'],
	"idtype"      => 1,
	"template"	=>	23,
	"pdf"		=>	"yes",
];

//arrayToFile($docparams, "Параметры отправки доков: ");
$docresult = my_http_build($docbaseurl, $docparams);





//***************************************************************************************

//Создание счета:


//arrayToFile($invparams,"Params invoice: "); //Проверка отправленных параметров
$invparams = [
	"login" 	=>	$user,
	"apikey" 	=>	$apikey,
	"action" 	=>  'invoice.add',
	"did"		=>	$dealresult['data'],
	"invoice"	=>	'auto',
	"tip"		=>	'По договору',
	"do"		=>	'no'
];

$invresult = my_http_build($dealbaseurl, $invparams);

//Отправка счета:


//== для отправки счета в виде pdf == invoice.mail
$invparams = [
	"login"   => $user,
	"apikey"  => $apikey,
	// указываем метод
	"action"  => 'invoice.mail',
	//id счета
	"id"      => $invresult['data']['id'],
	//номер счета
	"invoice" => $invresult['data']['invoice'],
	//тема сообщения
	"theme"   => "Счет на оплату Asterconf",
	//содержание сообщения
	"content" => $inv_mail
];
//arrayToFile($invparams,"параметры счета при отправке email: ");
$invresult_mail = my_http_build($dealbaseurl, $invparams);

//***************************************************************************************/
exit();
?>