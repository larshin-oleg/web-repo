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
		file_put_contents('log.txt', "\n **************************** \n", FILE_APPEND);
	}else{
		file_put_contents('log.txt', $array, FILE_APPEND);
		file_put_contents('log.txt', "\n **************************** \n", FILE_APPEND);
	}
}


function dateShift($shift){ 
	$taskDate = date('Y-m-d', strtotime("+ $shift days")); //получаем сдвиг даты
	$taskWeekDay = date('w', strtotime("+ $shift days")); //получаем день недели сдвинутой даты
	/*echo "shifted1: ". $taskDate. "<br>";
	echo "day: ". $taskWeekDay. "<br>";
	echo "type: ". gettype($taskDate). "<br>";*/


	//Проверка на день недели:
	if ($taskWeekDay != 0 && $taskWeekDay != 6 ){
		return $taskDate;	//Попали в будни - оставляем как есть 
	}elseif ($taskWeekDay == 0) {
		$shift++;	//Попали в воскресенье - увеличиваем сдвиг на 1 день
		$taskDate = date('Y-m-d', strtotime("+ $shift days")); 
		//echo "shifted2: ". $taskDate. "<br>";
		return $taskDate;
	}else{	//Попали в субботу - увеличиваем сдвиг на 2 дня
		$shift+=2;
		$taskDate = date('Y-m-d', strtotime("+ $shift days")); 
		//echo "shifted3: ". $taskDate. "<br>";
		return $taskDate;
	}

	
}

function isWinStatusClose($status_close){	//функция возвращает true или false в зависимости от статуса закрытия сделки (win/lose), на вход подаем статус закрытия сделки
	require_once "../../inc/config.php";
	require_once "../../inc/dbconnector.php";
	require_once "../../inc/settings.php";
	require_once "../../inc/func.php";

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

    $sql = "SELECT result_close FROM `".$sqlname."dogstatus` WHERE title='".$status_close."' AND identity='".$identity."'";
    $closeStatus = $db -> getOne($sql); //получаем тип статуса закрытия win/lose
    
    my_log($closeStatus);

    if ($closeStatus == "win"){ 
		return true;
    }else{
    	return false;
	}
}


?>