<?php
/* ============================ */
/*		Larshin Oleg 			*/
/*		SalesMan CRM 2018		*/
/* ============================ */


$apikey = "x5af26JFNRGJT1Hkh7NodsLhtnUDKP";
//Пользователь, на кого будут создаваться клиенты:
//$user = "irina"; //Ирина 
$user = "j.anishina"; //Юлия
//$user = "sergey";

$crmurl = "https://crm.asterconf.ru/developer/v2/";
//$crmurl = "http://test.sm-crm.ru/developer/v2/"; //Для теста
$clientbaseurl = $crmurl."client.php";//URL для создания клиента
$persbaseurl = $crmurl."person.php";//URL для создания контакта
$dealbaseurl = $crmurl."deal.php";//URL для создания сделки
$taskbaseurl = $crmurl."task.php";//URL для создания напоминания
$docbaseurl = $crmurl."document.php";//URL для создания документов

//Текст письма для отправки счета:
$inv_mail = "Здравствуйте, {{person}}! 
Вы оставили заявку на участие в конференции Asterconf. Оплатить участие можно по счету в приложении.
==============================
С уважением, 
{{mName}} 
Тел.: {{mPhone}} 
Email.: {{mMail}}
==============================";


//Текст письма для отправки договора:
$doc_mail = "Здравствуйте, {{person}}! 
Вы оставили заявку на участие в конференции Asterconf. Договор и акт находятся в приложении.
==============================
С уважением, 
{{mName}} 
Тел.: {{mPhone}} 
Email.: {{mMail}}
==============================";

$doc_mail_speka = "Здравствуйте, {{person}}! 
Вам было отправлено 2 письма с договорами и письмо со счетом. Так как кому-то необходима полная спецификация по предстоящему мероприятию, а кому-то нужна только общая сумма.
Для Вашего удобства Мы присылаем и тот, и другой вариант.
==============================
С уважением, 
{{mName}} 
Тел.: {{mPhone}} 
Email.: {{mMail}}
==============================";

$doc_mail_fiz ="Здравствуйте, {{person}}! 
Вы оставили заявку на участие в конференции Asterconf. Реквизиты для различных способов оплаты участия находятся в приложении.
==============================
С уважением, 
{{mName}} 
Тел.: {{mPhone}} 
Email.: {{mMail}}
==============================";

?>