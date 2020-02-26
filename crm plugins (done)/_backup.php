<?php
ini_set('error_reporting', E_ERROR);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once ($_SERVER['DOCUMENT_ROOT']."/inc/config.php");

$path = $_SERVER['DOCUMENT_ROOT']."/admin/backup/";

$file = 'stand_backup.sql';


exec('mysqldump -u '.$dbusername.' -p'.$dbpassword.' -h '.$dbhostname.' '.$database.' > '.$path.$file, $output, $exit); //Делаем дамп БД


$exit = ($exit == 0) ? "Ok" : $exit;
//print BKP_USR.', '. BKP_PSWD.', '. $dbhostname.', '.$database.'<br>';
print "Создан дамп: " . $exit."<br>";
//echo "File:     ". $path.$file;


exit();
?>
 