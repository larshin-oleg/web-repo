<?php
/* ============================ */
/*		Larshin Oleg 			*/
/*		SalesMan CRM 2018		*/
/* ============================ */

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

function my_http_build($baseurl, $params){ //Отправка http-запроса. На входе: $baseurl - URL файла, куда отправляем, $params - массив элементов. Возвращает ассоциативный массив с ответом от API
	$urlparams = http_build_query($params);
	$res = Send($baseurl, $urlparams);
	$result = json_decode($res, true);
	//my_log($result, "\n Ответ: ");
	if($result['result'] == 'Error') {
		//my_log($result, "\n Ошибка:");
		//exit();

	}

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



?>