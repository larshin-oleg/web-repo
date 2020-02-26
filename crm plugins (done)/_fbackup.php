<?php
	ini_set('error_reporting', E_ERROR);
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);

	require_once ($_SERVER['DOCUMENT_ROOT']."/inc/config.php");

	$path = $_SERVER['DOCUMENT_ROOT']."/backup/";

	$file = 'stand_backup.sql';


	exec('mysqldump -u '.$dbusername.' -p'.$dbpassword.' -h '.$dbhostname.' '.$database.' > '.$path.$file, $output, $exit); //Делаем дамп БД
	//$dbdump = `mysqldump -u $dbusername -p$dbpassword -h $dbhostname $database > $path.$file`;
	//echo $dbdump;
	//$exit = ($exit == 0) ? "Ok" : $exit;
	//print BKP_USR.', '. BKP_PSWD.', '. $dbhostname.', '.$database.'<br>';
	print "Создан дамп: " . $exit."<br>";
	//echo "File:     ". $path.$file;




	$backup_folder = $_SERVER['DOCUMENT_ROOT'].'/backup';    // куда будут сохранятся файлы
	$backup_name = 'stand_backup_' . date("Y-m-d");    // имя архива
	$dir = $_SERVER['DOCUMENT_ROOT'].'/cash/ '.$_SERVER['DOCUMENT_ROOT'].'/inc/config.php '.$_SERVER['DOCUMENT_ROOT'].'/files/';    // что бэкапим
	$delay_delete = 30 * 24 * 3600;    // время жизни архива (в секундах)


	$mail_to = 'ironaman9115@yandex.ru';
	$mail_subject = 'CRM backup';
	$mail_message = '';
	$mail_headers = 'MIME-Version: 1.0' . "\r\n";
	$mail_headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$mail_headers .= 'To: me <ironaman9115@yandex.ru>' . "\r\n";
	$mail_headers .= 'From: my_site <o.larshin@salesmzn.pro>' . "\r\n";

	function backupFiles($backup_folder, $backup_name, $dir)
	{
	    $fullFileName = $backup_folder . '/' . $backup_name . '.tar.gz';
	    shell_exec("tar -cvf " . $fullFileName . " " . $dir);
	    return $fullFileName;
	}

	function deleteOldArchives($backup_folder, $delay_delete)
	{
	    $this_time = time();
	    $files = glob($backup_folder . "/*.tar.gz*");
	    $deleted = array();
	    foreach ($files as $file) {
	        if ($this_time - filemtime($file) > $delay_delete) {
	            array_push($deleted, $file);
	            unlink($file);
	        }
	    }
	    return $deleted;
	}

	$start = microtime(true);    // запускаем таймер

	$deleteOld = deleteOldArchives($backup_folder, $delay_delete);    // удаляем старые архивы
	$doBackupFiles = backupFiles($backup_folder, $backup_name, $dir);    // делаем бэкап файлов
	

	// добавляем в письмо отчеты
	if ($doBackupFiles) {
	    $mail_message .= 'site backuped successfully<br/>';
	    $mail_message .= 'Files: ' . $doBackupFiles . '<br/>';
	}

	if ($doBackupDB) {
	    $mail_message .= 'DB: ' . $doBackupDB . '<br/>';
	}

	if ($deleteOld) {
	    foreach ($deleteOld as $val) {
	        $mail_message .= 'File deleted: ' . $val . '<br/>';
	    }
	}

	$time = microtime(true) - $start;     // считаем время, потраченое на выполнение скрипта
	$mail_message .= 'script time: ' . $time . '<br/>';

	mail($mail_to, $mail_subject, $mail_message, $mail_headers);    // и отправляем письмо

	exit();
?>