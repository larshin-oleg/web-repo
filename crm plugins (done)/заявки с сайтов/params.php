<?php
/* ============================ */
/*		Larshin Oleg 			*/
/*		SalesMan CRM 2018		*/
/* ============================ */


$apikey = "jy4WiT5xTJHL2saFZcGJMORCtaHlyM";
//Пользователь, на кого будут создаваться клиенты:
$user = "alexey"; 
//$user = "sergey";

$crmurl = "http://crm.mskclimat.ru/developer/v2/";
$clientbaseurl = $crmurl."client.php";//URL для создания клиента
$persbaseurl = $crmurl."person.php";//URL для создания контакта
$dealbaseurl = $crmurl."deal.php";//URL для создания сделки
$taskbaseurl = $crmurl."task.php";//URL для создания напоминания
$docbaseurl = $crmurl."document.php";//URL для создания документов

?>