<?php
/* ============================ */
/*		Larshin Oleg 			*/
/*		SalesMan CRM 2018		*/
/* ============================ */


//require_once 'ticket.php';
function Send($url, $POST){ //работа с CURL'ом
	$ch = curl_init();// Устанавливаем соединение
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $POST);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_URL, $url);

	$result = curl_exec($ch);

	if($result === false) print $err = curl_error($ch);

	return $result;
}



function my_log($array,$name){ //Запись массива в файл
	file_put_contents('log.txt', $name."\n", FILE_APPEND);
	if (is_array($array)){
		file_put_contents('log.txt', print_r($array, true), FILE_APPEND);
	}else{
		file_put_contents('log.txt', $array, FILE_APPEND);
	}
}



function my_http_build($baseurl, $params){ //Отправка http-запроса. На входе: $baseurl - URL файла, куда отправляем, $params - массив элементов. Возвращает ассоциативный массив с ответом от API
	$urlparams = http_build_query($params);
	$res = Send($baseurl, $urlparams);
	$result = json_decode($res, true);
	//my_log($result, "\n Ответ: ");
	if($result['result'] == 'Error') {
		my_log($result, "\n Ошибка:");
		//exit();

	}

	return $result;
}

function myGetTime(){ //Получение времени в формате H:i
// забирает текущее время в массив
$timestamp = time();
$time_array = getdate($timestamp);

$hours = $time_array['hours'];
$minutes = $time_array['minutes'];
$step = 15; 				//сдвиг времени в минутах
$time = mktime($hours, $minutes + $step); 
$time = strftime('%H:%M', $time);
//echo $time;
return $time;
}

function getDealInfo($dealparams, $dealbaseurl){
	$dealresult = my_http_build($dealbaseurl, $dealparams);

	return $dealresult;
}

function getDocList($did, $idtype){ // на вход подается id сделки в которой ищем документы и id типа документа
	require_once 'params.php';

	$docparams = [
		"login"		=> $user,
		"apikey"	=> $apikey,
		"action"	=> 'list',
		"did"		=> $did,
		"idtype"	=> $idtype
	];

	$docresult = my_http_build($docparams, $docbaseurl);


	return $docresult;

}

//Получение clid из строки ответа при коде ошибки 406. На вход подается строка с текстом ответа от API. На выходе получаем clid
function make_clid($str){
	/*$st_pos = strpos($str, $needle);
	$first_ss = substr($str, $st_pos);
	$end_pos = strpos($first_ss, ')');
	$last_ss = substr($first_ss, 0, strlen($first_ss) - $end_pos);
	trim($last_ss);
	return $last_ss;*/

	$str1=stristr($str,"=");
	$str2=stristr($str1,')',true);
	$str_result=str_replace('= ','',$str2);

	return $str_result;
}


function make_client($params, $clientbaseurl){
	

	$clientresult = my_http_build($clientbaseurl, $params);

	if ($clientresult['error']['code'] == '406') {
		//$needle = "clid =";
		$error_text = $clientresult['error']['text'];	
		$clid = make_clid($error_text);

		$clientresult['data'] = $clid;

		$params['action'] = 'update';
		$params['clid'] = $clid;
		
		arrayToFile($params,"Params client update: "); //Проверка отправленных параметров
		$clientresult = my_http_build($clientbaseurl, $params);

		//return $clientresult;
	}

	return $clientresult;
}

?>