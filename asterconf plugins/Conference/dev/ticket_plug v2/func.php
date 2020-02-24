<?php
/* ============================ */
/*		Larshin Oleg 			*/
/*		SalesMan CRM 2018		*/
/* ============================ */




function my_log($array,$name){ //Запись массива в файл
	file_put_contents('log.txt', $name."\n", FILE_APPEND);
	if (is_array($array)){
		file_put_contents('log.txt', print_r($array, true), FILE_APPEND);
	}else{
		file_put_contents('log.txt', $array, FILE_APPEND);
	}
}



?>