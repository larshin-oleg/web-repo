#!/usr/bin/php -q
<?php
/* ============================ */
/*		Larshin Oleg 			*/
/*		SalesMan CRM 2019		*/
/* ============================ */

//error_reporting(0);



require_once 'plug_func.php';
require_once __DIR__."/../../inc/class/Todo.php";
require_once __DIR__."/../../inc/config.php";
require_once __DIR__."/../../inc/dbconnector.php";
require_once __DIR__."/../../inc/auth_main.php";
require_once __DIR__."/../../inc/settings.php";
require_once __DIR__."/../../inc/func.php";

$identity = $GLOBALS['identity'];
$sqlname  = $GLOBALS['sqlname'];
$iduser1  = $GLOBALS['iduser1'];
$db       = $GLOBALS['db'];



$date = curr_date(); //Текущая дата
//echo "$date <br>";

//Определим всех активных пользователей:
$activeUsers = $db -> getAll("select iduser, mid, title, email from {$sqlname}user where secrty='yes' and identity='$identity'");
//print_r($activeUsers);

//Выборка всех напоминаний по id пользователя и дате:
foreach ($activeUsers as $user) {
	$iduser = $user['iduser'];
	$email = $user['email'];
	$idboss = $user['mid'];
	/*echo "<br>iduser = ". $iduser. "<br>";
	echo "name = ". $user['title'] . "<br>";
	echo "email = ". $email . "<br>";
	echo "id_boss = ". $idboss . "<br>";*/

	//Получим список напоминаний:
	$tasklist = $db -> getAll("select title, speed, priority from {$sqlname}tasks where iduser=$iduser and active='yes' and datum='$date' and identity='$identity'");

	//Инициируем переменные приоритета:
	$hprior_hspeed = "";
	$hprior_lspeed = "";
	$lprior_hspeed = "";
	$lprior_lspeed = "";

	foreach ($tasklist as $task) {
		$prior = $task['priority'];
		$speed = $task['speed'];
		$title = $task['title'];

		if ($prior == '2' && $speed == '2') { //Важно и срочно
		 	$hprior_hspeed .= $title."; <br>";
		 } elseif ($prior == '2' && $speed == '1') { //Важно и несрочно
		 	$hprior_lspeed .= $title."; <br>";
		 } elseif ($prior == '1' && $speed == '2') { //Неважно и срочно
		 	$lprior_hspeed .= $title."; <br>";
		 } elseif ($prior == '1' && $speed == '1') { //Неважно и несрочно
		 	$lprior_lspeed .= $title."; <br>";
		 }
	}

	//Текст письма:
	$mail_txt = '<style>
	   		table {
			    border: 0px; /* Рамка вокруг таблицы */ 
			    border-collapse: separate; /* Способ отображения границы */ 
			    width: 50%; /* Ширина таблицы */ 
			    border-spacing: 0px; /* Расстояние между ячейками */ 
		    }
		</style>
		<table>
			<tr>
				<td bgcolor="#00BFFF"><h3 align="center">Срочное и важное</h3>
					<font color="white" align="left"> 
						'.$hprior_hspeed. '
					</font>
				</td>
				<td bgcolor="#98FB98"><h3 align="center">Несрочное и важное</h3>
					<font color="white" align="left"> 
						'.$hprior_lspeed.'				
					</font>
				</td>		
			</tr>
			<tr border=0px>
				<td bgcolor="#FFA500"><h3 align="center">Срочное и неважное</h3>
					<font color="white" align="left"> 
						'.$lprior_hspeed.'
					</font>
				</td>
				<td bgcolor="#696969"><h3 align="center">Несрочное и неважное</h3>
					<font color="white" align="left"> 
						'.$lprior_lspeed.'
					</font>
				</td>		
			</tr>
		</table>';

	//echo $mail_txt.'<br>';
	
	if ($hprior_hspeed != "" || $hprior_lspeed != "" || $lprior_hspeed != "" || $lprior_lspeed != "") {
		
		//Отправка email:
		//Формируем письмо:
		$subject = "Напоминания на сегодня";
		//Отправка сотруднику:
		$from     = 'no-replay@'.$_SERVER['HTTP_HOST'];
		$fromname = 'SalesMan CRM';
		mailer($email, $user['title'], $from, $fromname, $subject, $mail_txt);


		//Получим email руководителя:
		if ($idboss != 0) {
			$boss_email = $db -> getOne("select email from {$sqlname}user where secrty='yes' and iduser='$idboss' and identity='$identity'");
			//echo "boss_email = ". $boss_email . "<br>";
			//Отправка начальнику:
			$mail_txt = "<H1>Напоминания сотрудника ". $user['title']."</H1>". $mail_txt;
			mailer($boss_email, $user['title'], $from, $fromname, $subject, $mail_txt);
		}
		//echo "<br><br>***********************<br><br>";
		
	}

}



exit();

?>