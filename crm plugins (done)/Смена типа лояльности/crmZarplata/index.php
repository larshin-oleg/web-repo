<?php
/* ============================ */
/* (C) 2015 Vladislav Andreev   */
/*        100crm Project        */
/*        www.100crm.ru         */
/*           ver. 8.30          */
/* ============================ */
?>
<?php
set_time_limit(0);

error_reporting(E_ERROR);

require_once "../../inc/config.php";
require_once "../../inc/dbconnector.php";
require_once "../../inc/auth_main.php";
require_once "../../inc/settings.php";
require_once "../../inc/func.php";

$action = $_REQUEST['action'];
$year   = $_REQUEST['year'];
$mon    = $_REQUEST['mon'];

if(!$mon)  $mon = date('n');
if(!$year) $year = date('Y');

$baseKPI = array(
	"0" => array("oborotMin" => 0,  "oborotMax" => 50,  "oborotK" => 0,   "margaMin" => 0,   "margaMax" => 50,  "margaK" => 0),
	"1" => array("oborotMin" => 51, "oborotMax" => 80,  "oborotK" => 0.8, "margaMin" => 51,  "margaMax" => 80,  "margaK" => 0.8),
	"2" => array("oborotMin" => 81, "oborotMax" => 120, "oborotK" => 1.0, "margaMin" => 81,  "margaMax" => 100, "margaK" => 1.0),
	"3" => array("oborotMin" => 121, "oborotMax" => "", "oborotK" => 1.2, "margaMin" => 121, "margaMax" => "",  "margaK" => 1.2)
);

$other    = $GLOBALS['other'];
$identity = $GLOBALS['identity'];
$iduser1  = $GLOBALS['iduser1'];

$access = array();

$fpath = '';

if($isCloud == true) {

	//создаем папки хранения файлов
	if (!file_exists("files/".$identity)) {

		mkdir("files/".$identity, 0777);
		chmod("files/".$identity, 0777);

	}
	if (!file_exists("data/".$identity)) {

		mkdir("data/".$identity, 0777);
		chmod("data/".$identity, 0777);

	}

	$fpath = $identity.'/';

}

//загружаем настройки доступа
$file = 'data/'.$fpath.'access.json';

//если настройки произведены, то загружаем их
if(file_exists($file) && $action != 'access.do'){

	$access = json_decode(file_get_contents($file),true);

}
else{

	$access = $db->getCol("SELECT iduser FROM ".$sqlname."user WHERE (tip LIKE '%Руководитель%' or isadmin = 'on') and secrty = 'yes' and identity = '$identity' ORDER BY title");

}

if(!in_array($iduser1, $access) and $isadmin != 'on') {

	print '
		<TITLE>Предупреждение - CRM</TITLE>
		<LINK rel="stylesheet" type="text/css" href="../../css/style.crm.css">
		<LINK rel="stylesheet" href="../../css/fontello.css">
		<div class="warning" align="left" style="width:600px; margin:0 auto;">
			<span><i class="icon-attention red icon-5x pull-left"></i></span>
			<b class="red uppercase">Предупреждение:</b>
			<br><br>
			У вас нет доступа<br><br><br>
		</div>
		';
	exit();

}

//если таблицы нет, то создаем её
$da = $db->getCol("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '".$sqlname."ezarplata'");
if ( $da[0] == 0) {

	$kpi = json_encode_cyr($baseKPI);

	try {

		$db->query("CREATE TABLE `" . $sqlname . "ezarplata` (`id` INT(20) NOT NULL AUTO_INCREMENT,`name` VARCHAR(200) NOT NULL  COMMENT 'ФИО сотрудника',`iduser` INT(20) NOT NULL COMMENT 'Идентификатор из таблицы _user.iduser',`oklad` double(20,2) NOT NULL COMMENT 'Оклад сотрудника',`k` TEXT NOT NULL, `k1` double(5,2) DEFAULT '5.00' NOT NULL COMMENT 'Коэффициент за стаж',`dateStart` DATE NULL DEFAULT NULL COMMENT 'Дата приема на работу', PRIMARY KEY (`id`), UNIQUE INDEX `id` (`id`), INDEX `name` (`name`)) COMMENT='Список сотрудников для расчета показателей' COLLATE='utf8_general_ci'");

		$users = array();

		$data = $db->getAll("select title, CompStart, iduser from " . $sqlname . "user where secrty = 'yes' and acs_plan = 'on' and identity = '$identity'");
		for ($i = 0; $i < count($data); $i++) {

			$users[] = "(null, '" . $data[ $i ]['title'] . "', '" . $data[ $i ]['iduser'] . "', '$kpi', '" . $data[ $i ]['CompStart'] . "')";

		}

		if (count($users) > 0) {

			$db->query("INSERT INTO `" . $sqlname . "ezarplata` (id, name, iduser, k, dateStart) VALUES " . implode(",", $users));
			//echo "Импортировано " . $db->affectedRows() . " записей";

		}

	}
	catch (Exception $e) {

		$mes = 'Ошибка'. $e-> getMessage(). ' в строке '. $e->getCode();

	}

}

if($action == 'edit.do'){

	$id       = $_REQUEST['id'];
	$oklad    = pre_format($_REQUEST['oklad']);
	$k1       = pre_format($_REQUEST['k1']);

	$KPI = array();

	for($i=0; $i < 4; $i++){

		$KPI[$i] = array(
			"oborotMin" => pre_format($_REQUEST['oborotMin'][$i]),
			"oborotMax" => pre_format($_REQUEST['oborotMax'][$i]),
			"oborotK"   => pre_format($_REQUEST['oborotK'][$i]),
			"margaMin"  => pre_format($_REQUEST['margaMin'][$i]),
			"margaMax"  => pre_format($_REQUEST['margaMax'][$i]),
			"margaK"    => pre_format($_REQUEST['margaK'][$i])
		);
	}

	$mes = '';

	try {

		$db->query("UPDATE `" . $sqlname . "ezarplata` SET oklad = '$oklad', k1 = '$k1', k = '".json_encode_cyr($KPI)."' WHERE id = '$id'");
		$mes = 'Готово';

	}
	catch (Exception $e) {

		$mes = 'Ошибка'. $e-> getMessage(). ' в строке '. $e->getCode();

	}

	print $mes;

	exit();
}
if($action == 'sync'){

	//массив актуальных сотрудников
	$usersNow = $db->getCol("select iduser from " . $sqlname . "user where secrty = 'yes' and acs_plan = 'on' and identity = '$identity'");

	//массив сотрудников в базе расчета
	$usersIs = $db->getCol("select iduser from " . $sqlname . "ezarplata");

	$mes = array();

	foreach($usersIs as $users){

		//если сотрудник выбыл, то удалим его из базы расчета
		if(!in_array($users, $usersNow)){

			$db->query("delete from " . $sqlname . "ezarplata where iduser = '".$users."'");

			$mes[] = "Из списка исключен сотрудник ".current_user($users);

		}

	}

	foreach($usersNow as $users){

		//если сотрудник добавлен, то добавим его в базу расчета
		if(!in_array($users, $usersIs)){

			$user = $db->getRow("select title, CompStart, iduser from " . $sqlname . "user where iduser = '".$users."'");

			$db->query("INSERT INTO `" . $sqlname . "ezarplata` (id, name, iduser, k, dateStart) VALUES (null, '".$user['title']."', '".$user['iduser']."', '".json_encode_cyr($baseKPI)."', '".$user['CompStart']."')");

			$mes[] = "В список добавлен сотрудник ".$user['title'];

		}
		else{

			$user = $db->getRow("select title, CompStart from " . $sqlname . "user where iduser = '".$users."'");

			$db->query("UPDATE `" . $sqlname . "ezarplata` SET name = '".$user['title']."', dateStart = '".$user['CompStart']."' WHERE iduser = '".$users."'");

			$mes[] = "Обновлен - ".$user['title'];

		}

	}

	if(count($mes) > 0) $res = implode("\n\r", $mes);
	else $res = "Списки актуальны";

	print $res;

	exit();
}
if($action == 'fdelete'){

	$file = $_REQUEST['file'];

	if(file_exists('files/'.$fpath.$file)) {
		unlink('files/'.$file);

		print 'Файл удален';
	}
	else print 'Файл не найден';

	exit();
}
if($action == 'access.do'){

	$preusers     = $_REQUEST['preusers'];

	$params = json_encode_cyr($preusers);

	$f = 'data/'.$fpath.'access.json';
	$file = fopen($f, "w");

	if(!$file) $rez = 'Не могу открыть файл';
	else{

		if(fputs($file, $params) === false){
			$rez = 'Ошибка записи';
		}
		else $rez = 'Записано';

		fclose($file);

	}

	print $rez;

	exit();

}

if($action == "edit"){

	$id = $_REQUEST['id'];

	$data = $db->getRow("select * from " . $sqlname . "ezarplata WHERE id = '$id'");

	$KPI = json_decode($data['k'], true);

	if(count($KPI) == 0) $KPI = $baseKPI;

	$title = $db->getOne("select title from " . $sqlname . "user WHERE iduser = '".$data['iduser']."'");
?>
<DIV class="zagolovok"><B>Редактор показателей - <?=$title?>:</B></DIV>
<form action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
<input type="hidden" id="action" name="action" value="edit.do">
<input name="id" type="hidden" id="id" value="<?=$id?>">
<input name="name" type="hidden" id="name" value="<?=$title?>">

	<div class="paddleft20 paddright20" style="overflow-y: auto; max-height: 350px">

		<div class="row margtop10">
			<div class="column grid-2 right-text"><label for="name" class="paddtop2">Оклад:</label></div>
			<div class="column grid-8"><input name="oklad" type="text" id="oklad" style="width:150px" value='<?=num_format($data['oklad'])?>' /></div>
		</div>

		<div class="row">
			<div class="column grid-2 right-text paddtop5"><label for="percent1" class="paddtop2">Процент:</label></div>
			<div class="column grid-8"><input name="k1" type="number" step="1" id="k1" style="width:100px" value='<?=$data['k1']?>' /> %</div>
		</div>

		<hr>

		<div class="row">
			<div class="column grid-10 paddtop5 Bold">Оборот:</div>
		</div>
		<?php
		for($i=0; $i < count($KPI); $i++){
		?>
		<div class="row ha">
			<div class="column grid-4">
				От
				<input name="oborotMin[<?=$i?>]" type="number" step="1" id="oborotMin[<?=$i?>]" style="width:60%" value="<?=$KPI[$i]['oborotMin']?>" /> %
			</div>
			<div class="column grid-4">
				до
				<input name="oborotMax[<?=$i?>]" type="number" step="1" id="oborotMax[<?=$i?>]" style="width:60%" value="<?=$KPI[$i]['oborotMax']?>" /> %
			</div>
			<div class="column grid-2">
				Коэф.
				<input name="oborotK[<?=$i?>]" type="number" step="0.1" id="oborotK[<?=$i?>]" style="width:50%" value="<?=$KPI[$i]['oborotK']?>" />
			</div>
		</div>
		<?php } ?>

		<hr>

		<div class="row">
			<div class="column grid-10 paddtop5 Bold">Прибыль:</div>
		</div>
		<?php
		for($i=0; $i < count($KPI); $i++){
		?>
		<div class="row ha">
			<div class="column grid-4">
				От
				<input name="margaMin[<?=$i?>]" type="number" step="1" id="margaMin[<?=$i?>]" style="width:60%" value="<?=$KPI[$i]['margaMin']?>" /> %
			</div>
			<div class="column grid-4">
				до
				<input name="margaMax[<?=$i?>]" type="number" step="1" id="margaMax[<?=$i?>]" style="width:60%" value="<?=$KPI[$i]['margaMax']?>" /> %
			</div>
			<div class="column grid-2">
				Коэф.
				<input name="margaK[<?=$i?>]" type="number" step="0.1" id="margaK[<?=$i?>]" style="width:50%" value="<?=$KPI[$i]['margaK']?>" />
			</div>
		</div>
		<?php } ?>
	</div>

	<hr>

	<div align="right">
		<A href="javascript:void(0)" onClick="saveUser()" class="button">Сохранить</A>&nbsp;
		<A href="javascript:void(0)" onClick="DClose()" class="button">Отмена</A>
	</div>
</form>
<script>

	$('#dialog').css('width','700px');

	function saveUser(){

		var str = $('#Form').serialize();

		$('#dialog_container').css('display', 'none');

		$.post("index.php", str, function(data){

			yNotifyMe("CRM. Результат,"+data+",signal.png");
			loadUsers();
			//$('#dtabs li[data-id="1"]').trigger('click');

			DClose();

		});
	}
</script>
<?php

	exit();
}
if($action == "access"){
	?>
	<DIV class="zagolovok"><B>Доступы пользователей:</B></DIV>
	<form action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="access.do">

		<div class="row" style="overflow-y: auto; max-height: 350px">
		<?php
		$da = $db->getAll("SELECT * FROM ".$sqlname."user WHERE secrty = 'yes' and identity = '$identity' ORDER BY title");
		foreach ($da as $data) {
		?>
		<label style="display: inline-block; width: 50%; box-sizing: border-box; float: left; padding-left: 20px">
			<div class="column grid-1">
				<input name="preusers[]" type="checkbox" id="preusers[]" value="<?=$data['iduser']?>" <?php if(in_array($data['iduser'], $access)) print 'checked';?>>
			</div>
			<div class="column grid-9">
				<?=$data['title']?>
			</div>
		</label>
		<?php
		}
		?>
		</div>

		<hr>

		<div align="right">
			<A href="javascript:void(0)" onClick="saveAccess()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onClick="DClose()" class="button">Отмена</A>
		</div>
	</form>
	<script>

		$('#dialog').css('width','700px');

		function saveAccess(){

			var str = $('#Form').serialize();

			$('#dialog_container').css('display', 'none');

			$.post("index.php", str, function(data){

				yNotifyMe("CRM. Результат,"+data+",signal.png");

				DClose();

			});
		}
	</script>
	<?php

	exit();
}

#todo:закончить импорт на перспективу
if($action == "import"){
?>
<FORM action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
	<INPUT type="hidden" name="action" id="action" value="import.on">
	<DIV class="zagolovok">Импорт из Excel</DIV>
	<TABLE width="100%" border="0" cellpadding="2" cellspacing="3">
		<TR>
			<TD width="100" align="right"><B>Из файла:</B></TD>
			<TD><input name="file" type="file" class="file" id="file" style="width:98%" /></TD>
		</TR>
	</TABLE>
	<div class="infodiv">
		Поддерживаются форматы XLS.<br>
	</div>
	<hr>
	<div align="right">
		<A href="#" onClick="importDo()" class="button">Выполнить</A>&nbsp;
		<A href="#" onClick="DClose()" class="button">Закрыть</A>
	</div>
</FORM>
<?php
}

if($action == 'loaddeals'){

	$iduser = $_REQUEST['user'];

	$User = $db -> getRow("select title, acs_import from " . $sqlname . "user WHERE iduser = '$iduser'");
	$uset = yexplode(";", $User['acs_import']);

	//print_r($User);

	//массив имен сотрудников
	$ulist = $db -> getAll("select title, iduser from " . $sqlname . "user WHERE identity = '$identity'");
	foreach($ulist as $u){

		$userlist[$u['iduser']] = $u['title'];

	}

	if($other[2]!='yes'){

		//учитываем всех подчиненных текущего пользователя
		if ($uset[19] != 'on') {
			$sd = get_people($iduser, 'yes');
			if (count($sd) > 0) $sort = " and " . $sqlname . "dogovor.iduser IN (" . implode(",", $sd) . ")";
		}
		else $sort = " and iduser = '" . $da['iduser'] . "'";

		//Обходим закрытые сделки, чтобы посчитать по ним маржу
		$sort2 = $sord . $sort . " and " . $sqlname . "dogovor.close='yes' and DATE_FORMAT(" . $sqlname . "dogovor.datum_close, '%Y-%c') = '" . $year . '-' . $mon . "'";

		$q2 = "
		SELECT
			DATE_FORMAT(" . $sqlname . "dogovor.datum, '%d.%m.%Y') as dcreate,
			DATE_FORMAT(" . $sqlname . "dogovor.datum, '%Y-%c') as dcreate2,
			DATE_FORMAT(" . $sqlname . "dogovor.datum_plan, '%Y-%c') as dplan,
			DATE_FORMAT(" . $sqlname . "dogovor.datum_close, '%Y-%c') as dfact,
			" . $sqlname . "dogovor.did as did,
			" . $sqlname . "dogovor.title as dogovor,
			" . $sqlname . "dogovor.close as close,
			" . $sqlname . "dogovor.kol as summa,
			" . $sqlname . "dogovor.kol_fact as fsumma,
			" . $sqlname . "dogovor.marga as marga,
			" . $sqlname . "dogovor.iduser as iduser,
			" . $sqlname . "dogcategory.title as step
		FROM " . $sqlname . "dogovor
			LEFT JOIN " . $sqlname . "dogcategory ON " . $sqlname . "dogovor.idcategory = " . $sqlname . "dogcategory.idcategory
		WHERE
			" . $sqlname . "dogovor.did > 0
			" . $sort2 . "
			and " . $sqlname . "dogovor.identity = '$identity'
		ORDER BY " . $sqlname . "dogovor.datum
		";

		//перебираем сделки и считаем показатели
		$da = $db -> getAll($q2);
		foreach ($da as $daz) {

			$daz['step'] = 'Закрыта';

			if ($daz['payer'] < 1 and $daz['clid'] > 0) $daz['payer'] = $daz['clid'];

			$list[] = array(
				"did"     => $daz['did'],
				"dcreate" => $daz['dcreate'],
				"dfact"   => $daz['dfact'],
				"deal"    => $daz['dogovor'],
				"user"    => $userlist[$daz['iduser']],
				"summa"   => $daz['summa'],
				"fsumma"  => $daz['fsumma'],
				"marga"   => $daz['marga'],
				"clid"    => $daz['payer'],
				"client"  => current_client($daz['payer']),
				"close"   => $daz['close'],
				"step"    => $daz['step']
			);

		}

	}
	if($other[2]=='yes'){

		//учитываем всех подчиненных текущего пользователя
		if ($uset[19] != 'on') {
			$sd = get_people($iduser, 'yes');
			if (count($sd) > 0) $sort = " and " . $sqlname . "dogovor.iduser IN (" . implode(",", $sd) . ")";
		}
		else $sort = " and " . $sqlname . "dogovor.iduser = '" . $iduser . "'";

		//выполнение планов по оплатам
		if($other[18] != 'yes'){
			$q = "
			SELECT
				".$sqlname."credit.did as did,
				".$sqlname."credit.iduser as iduser,
				".$sqlname."credit.summa_credit as summa,
				".$sqlname."credit.datum_credit as dplan,
				".$sqlname."credit.invoice_date as dfact,
				".$sqlname."credit.invoice as invoice,
				".$sqlname."dogovor.title as dogovor,
				".$sqlname."dogovor.kol as dsumma,
				".$sqlname."dogovor.marga as dmarga,
				".$sqlname."dogovor.iduser as diduser,
				".$sqlname."dogovor.close as close,
				".$sqlname."dogovor.clid as clid
			FROM ".$sqlname."credit
			LEFT JOIN ".$sqlname."dogovor ON ".$sqlname."credit.did = ".$sqlname."dogovor.did
			WHERE
				".$sqlname."credit.do = 'on'
				".str_replace("dogovor", "credit", $sort)." and
				DATE_FORMAT(" . $sqlname . "credit.invoice_date, '%Y-%c') = '" . $year . '-' . $mon . "' and
				".$sqlname."credit.did IN (SELECT did FROM ".$sqlname."dogovor WHERE did > 0 ".$sort.") and
				".$sqlname."credit.identity = '$identity'
			ORDER by ".$sqlname."credit.invoice_date";
		}

		//выполнение учет только оплат по закрытым сделкам в указанном периоде
		if($other[18] == 'yes'){
			$q = "
			SELECT
				".$sqlname."credit.did as did,
				".$sqlname."credit.iduser as iduser,
				".$sqlname."credit.summa_credit as summa,
				".$sqlname."credit.datum_credit as dplan,
				".$sqlname."credit.invoice_date as dfact,
				".$sqlname."credit.invoice as invoice,
				".$sqlname."dogovor.title as dogovor,
				".$sqlname."dogovor.kol as dsumma,
				".$sqlname."dogovor.marga as dmarga,
				".$sqlname."dogovor.iduser as diduser,
				".$sqlname."dogovor.close as close,
				".$sqlname."dogovor.clid as clid
			FROM ".$sqlname."credit
			LEFT JOIN ".$sqlname."dogovor ON ".$sqlname."credit.did = ".$sqlname."dogovor.did
			WHERE
				".$sqlname."credit.do='on'
				".str_replace("dogovor", "credit", $sort)." and
				".$sqlname."credit.did IN (
					SELECT did
					FROM ".$sqlname."dogovor
					WHERE
						did > 0 ".$sort." and
						close = 'yes' and
						DATE_FORMAT(" . $sqlname . "dogovor.datum_close, '%Y-%c') = '" . $year . '-' . $mon . "') and
				".$sqlname."credit.identity = '$identity'
			ORDER by ".$sqlname."credit.invoice_date";
		}

		//проходим оплаты
		$da = $db -> getAll($q);
		foreach ($da as $daz) {

			$list[] = array(
				"did"     => $daz['did'],
				"invoice" => $daz['invoice'],
				"date"    => $daz['dfact'],
				"deal"    => $daz['dogovor'],
				"dsumma"  => $daz['dsumma'],
				"dmarga"  => $daz['dmarga'],
				"user"    => $userlist[$daz['iduser']],
				"diduser" => $userlist[$daz['diduser']],
				"aSum"    => $daz['summa'],
				"aMarg"   => $daz['summa'] * $dolya,
				"close"   => $daz['close']
			);

		}

	}

?>
<DIV class="zagolovok"><B>Учтенные сделки/счета сотрудника <?=$User['title']?>:</B></DIV>
<div style="overflow-y: auto; max-height: 450px; height: 450px" class="relativ">

	<?php
	if ($uset[19] != 'on') {
		$sd = get_people($iduser, 'yes');
		if (count($sd) > 0) $u = $sd;
	}
	else $u = array("0" => $iduser);

	$ub = array();

	foreach($u as $k => $v){
		if($v != $key and $userlist[$v] != '') $ub[] = $userlist[$v];
	}

	if(count($u) > 1){

		//print '<div class="pad10"><b>Подчиненные:</b> '.yimplode(", ", $ub).'</div>';

	}
	?>
	<?php
	if($other[2]!='yes'){
	?>
	<table width="99.5%" border="0" align="center" cellpadding="5" cellspacing="0" class="bgwhite fs-10">
		<thead>
		<tr>
			<th class="">Сделка</th>
			<th class="w120">Дата создания</th>
			<th align="right" class="yw100">Сумма</th>
			<th align="right" class="yw100">Маржа</th>
			<th align="" class="yw160">Сотрудник</th>
		</tr>
		</thead>
		<tbody>
		<?php
		for($i = 0; $i < count($list); $i++) {

			if($list[$i]['close'] == 'yes') $icon = '<i class="icon-lock red"></i>';
			else $icon = '<i class="icon-briefcase-1 blue"></i>';
		?>
		<tr>
			<td align="" title="<?=$list[$i]['deal']?>">
				<div class="ellipsis"><a href="javascript:void(0)" onclick="viewDogovor('<?=$list[$i]['did']?>')"><?=$icon?> <?=$list[$i]['deal']?></a>&nbsp;[ <b class="em"><?=$list[$i]['step']?></b> ]</div>
			</td>
			<td align="center"><?=$list[$i]['dcreate']?></td>
			<td align="right"><b><?=num_format($list[$i]['summa'])?></b></td>
			<td align="right"><b><?=num_format($list[$i]['marga'])?></b></td>
			<td align=""><div class="ellipsis"><?=$list[$i]['user']?></div></td>
		</tr>
		<?php
		}
		?>
		</tbody>
	</table>
	<?php } ?>
	<?php
	if($other[2]=='yes'){
	?>
	<table width="99.5%" border="0" align="center" cellpadding="5" cellspacing="0" class="bgwhite fs-10">
		<thead>
		<tr>
			<th width="80">№ счета</th>
			<th width="100">Сумма счета</th>
			<th width="80">Дата оплаты</th>
			<th width="120" align="left">Сотрудник</th>
			<th>Сделка</th>
			<th width="100" align="center">Сумма</th>
			<th width="100" align="center">Маржа</th>
			<th width="120" align="">Куратор сделки</th>
		</tr>
		</thead>
		<tbody>
		<?php
		for($i = 0; $i < count($list); $i++) {

			if($list[$i]['close'] == 'yes') $icon = '<i class="icon-lock red"></i>';
			else $icon = '<i class="icon-briefcase-1 blue"></i>';
			?>
			<tr height="35" class="ha">
				<td align="center"><?=$list[$i]['invoice']?></td>
				<td align="right"><b><?=num_format($list[$i]['aSum'])?></b></td>
				<td align="center"><?=format_date_rus($list[$i]['date'])?></td>
				<td align=""><div class="ellipsis"><?=$list[$i]['user']?></div></td>
				<td align="" title="<?=$list[$i]['deal']?>">
					<div class="ellipsis"><a href="javascript:void(0)" onclick="viewDogovor('<?=$list[$i]['did']?>')"><?=$icon?> <?=$list[$i]['deal']?></a></div>
				</td>
				<td align="right"><b><?=num_format($list[$i]['dsumma'])?></b></td>
				<td align="right"><b><?=num_format($list[$i]['dmarga'])?></b></td>
				<td align=""><div class="ellipsis"><?=$list[$i]['diduser']?></div></td>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>
	<?php } ?>

	<?php
	if(count($u) > 1){

	print '<div class="foot"><b>Подчиненные:</b> '.yimplode(", ", $ub).'</div>';

	}
	?>

</div>
<script>

	$('#dialog').css('width','80%');

</script>
<?php

	exit();
}
if($action == 'loadusers') {

	//загружаем список врачей
	$data = $db->getAll("select * from ".$sqlname."ezarplata ORDER BY name");
	foreach ($data as $row){

		$status = $db->getOne("select tip from ".$sqlname."user WHERE iduser = '".$row['iduser']."'");

		$list[] = array("id" => $row['id'], "name" => $row['name'], "status" => $status, "oklad" => $row['oklad'], "k" => $row['k'], "k1" => $row['k1'], "date" => $row['dateStart']);

	}

	print $result = json_encode_cyr($list);

	exit();

}
if($action == 'loaddata') {

	//Формируем массив плановых показателей и фактических
	$data = $db->getAll("SELECT * FROM ".$sqlname."user WHERE iduser > 0 ".get_people($iduser1)." and acs_plan = 'on' and identity = '$identity' ORDER BY secrty DESC, title");
	foreach ($data as $da) {

		$uset = yexplode(";", $da['acs_import']);
		$sort = '';

		//Расчет плановых показателей для заданного пользователя
		$res = $db->getAll("SELECT SUM(kol_plan) as kol, SUM(marga) as marga FROM " . $sqlname . "plan WHERE mon='" . $mon . "' and year='" . $year . "' and iduser = '" . $da['iduser'] . "' and identity = '$identity'");

		$planTotalOborot = $res[0]['kol'];
		$planTotalMarga  = $res[0]['marga'];

		$user[ $da['iduser'] ] = array("title" => $da['title'], "plan" => $planTotalOborot, "mplan" => $planTotalMarga);

		//выполнение по закрытым сделкам
		if($other[2]!='yes'){

			//учитываем всех подчиненных текущего пользователя
			if ($uset[19] != 'on') {
				$sd = get_people($da['iduser'], 'yes');
				if (count($sd) > 0) $sort = " and " . $sqlname . "dogovor.iduser IN (" . implode(",", $sd) . ")";
			}
			else $sort = " and iduser = '" . $da['iduser'] . "'";

			//Обходим закрытые сделки, чтобы посчитать по ним маржу
			$sort2 = $sort . " and " . $sqlname . "dogovor.close='yes' and DATE_FORMAT(" . $sqlname . "dogovor.datum_close, '%Y-%c') = '" . $year . '-' . $mon . "'";

			$q2 = "
			SELECT
				" . $sqlname . "dogovor.kol_fact as fsumma,
				" . $sqlname . "dogovor.marga as marga,
			FROM " . $sqlname . "dogovor
			WHERE
				" . $sqlname . "dogovor.did > 0
				" . $sort2 . "
				and " . $sqlname . "dogovor.identity = '$identity'
			ORDER BY " . $sqlname . "dogovor.datum
			";

			//перебираем сделки и считаем показатели
			$dazz = $db->getAll($q2);
			foreach ($dazz as $daz) {

				$sumUser[ $da['iduser'] ]['Sum'] += $daz['fsumma'];
				$sumUser[ $da['iduser'] ]['Marg'] += $daz['marga'];

			}

		}

		//выполнение по оплатам
		if($other[2]=='yes'){

			//учитываем всех подчиненных текущего пользователя
			if ($uset[19] != 'on') {
				$sd = get_people($da['iduser'], 'yes');
				if (count($sd) > 0) $sort = " and " . $sqlname . "dogovor.iduser IN (" . implode(",", $sd) . ")";
			}
			else $sort = " and " . $sqlname . "dogovor.iduser = '" . $da['iduser'] . "'";

			//выполнение планов по оплатам
			if($other[18] != 'yes'){
				$q = "
				SELECT
					".$sqlname."credit.did as did,
					".$sqlname."credit.summa_credit as summa,
					".$sqlname."dogovor.kol as dsumma,
					".$sqlname."dogovor.marga as dmarga
				FROM ".$sqlname."credit
				LEFT JOIN ".$sqlname."dogovor ON ".$sqlname."credit.did = ".$sqlname."dogovor.did
				WHERE
					".$sqlname."credit.do = 'on'
					".str_replace("dogovor", "credit", $sort)." and
					DATE_FORMAT(" . $sqlname . "credit.invoice_date, '%Y-%c') = '" . $year . '-' . $mon . "' and
					".$sqlname."credit.did IN (SELECT did FROM ".$sqlname."dogovor WHERE did > 0 ".$sort.") and
					".$sqlname."credit.identity = '$identity'
				ORDER by ".$sqlname."credit.invoice_date";
			}

			//выполнение учет только оплат по закрытым сделкам в указанном периоде
			if($other[18] == 'yes'){
				$q = "
				SELECT
					".$sqlname."credit.did as did,
					".$sqlname."credit.summa_credit as summa,
					".$sqlname."credit.datum_credit as dplan,
					".$sqlname."dogovor.kol as dsumma,
					".$sqlname."dogovor.marga as dmarga
				FROM ".$sqlname."credit
				LEFT JOIN ".$sqlname."dogovor ON ".$sqlname."credit.did = ".$sqlname."dogovor.did
				WHERE
					".$sqlname."credit.do='on'
					".str_replace("dogovor", "credit", $sort)." and
					".$sqlname."credit.did IN (
						SELECT did
						FROM ".$sqlname."dogovor
						WHERE
							did > 0 ".$sort." and
							close = 'yes' and
							DATE_FORMAT(" . $sqlname . "dogovor.datum_close, '%Y-%c') = '" . $year . '-' . $mon . "') and
					".$sqlname."credit.identity = '$identity'
				ORDER by ".$sqlname."credit.invoice_date";
			}

			//проходим оплаты
			$dazz = $db->getAll($q);
			foreach ($dazz as $daz) {

				//оплачено
				$sumUser[ $da['iduser'] ]['Sum'] += $daz['summa'];

				//доля оплаты в сумме сделки
				if($daz['summa'] > 0) $dolya = $daz['summa'] / $daz['dsumma'];
				else $dolya = 0;

				//маржа
				$sumUser[ $da['iduser'] ]['Marg'] += $daz['dmarga'] * $dolya;

			}


		}

	}

	$number = 1;

	$data = $db->getAll("select * from ".$sqlname."ezarplata ORDER BY name");
	foreach ($data as $da){

		$bonus = 0; $k1 = 1; $k2 = 1;

		$oborotDo = $sumUser[ $da['iduser'] ]['Sum'] / $user[$da['iduser']]['plan'] * 100;
		$margaDo  = $sumUser[ $da['iduser'] ]['Marg'] / $user[$da['iduser']]['mplan'] * 100;

		$KPI = json_decode($da['k'], true);

		if($oborotDo <= $KPI[0]['oborotMax'] and $oborotDo >= $KPI[0]['oborotMin']) $k1 = $KPI[0]['oborotK'];
		if($oborotDo <= $KPI[1]['oborotMax'] and $oborotDo >= $KPI[1]['oborotMin']) $k1 = $KPI[1]['oborotK'];
		if($oborotDo <= $KPI[2]['oborotMax'] and $oborotDo >= $KPI[2]['oborotMin']) $k1 = $KPI[2]['oborotK'];
		if($oborotDo >= $KPI[3]['oborotMin']) $k1 = $KPI[3]['oborotK'];

		if($margaDo <= $KPI[0]['margaMax'] and $margaDo >= $KPI[0]['margaMin']) $k2 = $KPI[0]['margaK'];
		if($margaDo <= $KPI[1]['margaMax'] and $margaDo >= $KPI[1]['margaMin']) $k2 = $KPI[1]['margaK'];
		if($margaDo <= $KPI[2]['margaMax'] and $margaDo >= $KPI[2]['margaMin']) $k2 = $KPI[2]['margaK'];
		if($margaDo >= $KPI[3]['margaMin']) $k2 = $KPI[3]['margaK'];

		$bonus = $sumUser[ $da['iduser'] ]['Marg'] * $k1 * $k2 * $da['k1'] / 100;

		$list[] = array(
			"id" => $da['id'],
			"number" => $number,
			"user" => $da['iduser'],
			"name" => $da['name'],
			"oborotPlan" => $user[$da['iduser']]['plan'],
			"oborotFact" => $sumUser[ $da['iduser'] ]['Sum'],
			"oborotDo" => $oborotDo,
			"oborotK" => $k1,
			"margaPlan"  => $user[$da['iduser']]['mplan'],
			"margaFact"  => $sumUser[ $da['iduser'] ]['Marg'],
			"margaDo" => $margaDo,
			"margaK" => $k2,
			"oklad"  => $da['oklad'],
			"bonus"  => $bonus,
			"totall"  => $da['oklad'] + $bonus
		);

		$number++;

	}

	$dat = array("list" => $list);

	//print_r($dat);

	$period = ru_mon($mon)." ".$year;

	include_once $_SERVER["DOCUMENT_ROOT"].$apath.'/opensource/tbs_us/tbs_class.php';
	include_once $_SERVER["DOCUMENT_ROOT"].$apath.'/opensource/tbs_us/plugins/tbs_plugin_opentbs.php';

	if(file_exists($outputFile)) unlink($outputFile);

	$templateFile = 'data/'.$fpath.'reportZarplataTemp.xlsx';
	$outputFile = 'files/'.$fpath.'reportZarplata_'.$mon.'_'.$year.'.xlsx';

	$TBS = new clsTinyButStrong; // new instance of TBS
	$TBS->PlugIn(TBS_INSTALL, OPENTBS_PLUGIN); // load the OpenTBS plugin

	$TBS->SetOption(noerr, true);
	$TBS->LoadTemplate($templateFile, OPENTBS_ALREADY_UTF8);

	$TBS->MergeBlock('list', $list);
	$TBS->Show(OPENTBS_FILE, $outputFile);

	$data = array("list" => $list, "period" => ru_mon($mon).' '.$year, "file" => $outputFile);

	print $result = json_encode_cyr($data);

	exit();
}
if($action == 'loadfiles') {

	$list = array();
	$num = 1;

	if ($handle = opendir('files')) {

		while (false !== ($file = readdir($handle))) {

			//проверяем, что это простой файл, а не папка
			if (is_file('files/'.$fpath.$file)) {

				//парсим файл
				$f = yexplode("_", str_replace(".xlsx","", $file));
				$list[] = array(
					"num" => $num,
					"name" => $f[0],
					"start" => ru_mon($f[1]),
					"end" => $f[2],
					"size" => num_format(round(filesize('files/' . $file) / 1024, 2)),
					"date" => date("d.m.Y H:i",filemtime('files/' . $file)),
					"url" => $file
				);

				$num++;

			}

		}

	}

	print json_encode_cyr($list);

	exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Расчет заработной платы</title>
<link rel="stylesheet" href="../../css/style.crm.new.css">
<link rel="stylesheet" href="../../css/style.card.css">
<link rel="stylesheet" href="../../css/fontello.css">
<link rel="stylesheet" href="./plugins/tablesorter/theme.default.css">

<style>
	<!--
	#telo{
		margin-top: 105px;
	}
	fieldset{
		border:1px solid #ccc;
		background: #FFF;
		padding:15px 10px;
	}
	legend{
		font-size: 1.3rem;
	}

	p, li{
		font-size: 1.1rem;
		line-height: 1.2em;
	}

	table th{
		font-size: 0.9rem;
		background: #E6E9ED;
		border-bottom: 1px dotted #ccc;
	}
	table tbody td{
		border-bottom: 1px dotted #ccc;
	}

	#dtabs ul{
		display: unset;
	}
	.hidden{
		display: none;
	}

	.foot{
		position: fixed;
		text-align: center;
		bottom: 0; left:0;
		box-sizing: border-box;
		border-top:1px solid #ccc;
		background: #FFF;
		padding: 10px;
		margin-top: 10px;
		width: 100vw;
	}

	.period{
		padding: 3px;
		background: #FFF;
		margin-top: -2px;
	}
		.period input{
			width: 80px;
			text-align: center;
			border: 0;
			vertical-align: baseline;
			box-shadow: 0 0 0 !important;
			-moz-box-shadow: 0 0 0 !important;
			-webkit-box-shadow: 0 0 0 !important;
		}

	.infodiv #greenbutton a.button,
	.infodiv a.button,
	.infodiv a.button:link{
		height: 21px;
		font-weight: 700;
		box-shadow: 0 0 0 !important;
			-moz-box-shadow: 0 0 0 !important;
			-webkit-box-shadow: 0 0 0 !important;
	}

	.wrapper,
	.wrapper2{
		position: relative;
		top: 0;
		height: 450px;
		overflow-x: auto;
	}

	.h10{height: 10px}
	-->
</style>

</head>
<body>

<div id="dialog_container" class="dialog_container">
	<div class="dialog-preloader">
		<img src="../../images/rings.svg" border="0" width="128">
	</div>
	<div class="dialog" id="dialog" align="left">
		<div class="close" title="Закрыть или нажмите ^ESC"><i class="icon-cancel"></i></div>
		<div id="resultdiv"></div>
	</div>
</div>

<div class="fixx">
	<DIV id="head">
		<DIV id="ctitle">
			<b>Расчет заработной платы</b>
			<DIV id="close" onClick="window.close();">Закрыть</DIV>
		</DIV>
	</DIV>
	<DIV id="dtabs">
		<UL>
			<LI class="ytab current" id="tb0" data-id="0"><A href="#0">Результат</A></LI>
			<LI class="ytab" id="tb1" data-id="1"><A href="#1">Настройка KPI</A></LI>
			<LI class="ytab" id="tb2" data-id="2" style="float:right"><A href="#2">Файлы</A></LI>
			<LI class="ytab" id="tb3" data-id="3" style="float:right"><A href="#3">Справка</A></LI>
			<LI class="ytab"><A href="javascript:void(0)" onclick="setAccess()">Доступы</A></LI>
		</UL>
	</DIV>
</div>

<DIV class="fixbg"></DIV>

<?php
if (is_writable(__DIR__.'/data')  != true){
	print '
	<div class="warning margbot10">
		<p><b class="red">Внимание! Ошибка</b> - отсутствуют права на запись для папки хранения настроек доступа"<b>data</b>".</p>
	</div>';
}
if (is_writable(__DIR__.'/files') != true){
	print '
	<div class="warning margbot10">
		<p><b class="red">Внимание! Ошибка</b> - отсутствуют права на запись для папки хранения файлов отчета"<b>files</b>".</p>
	</div>';
}
?>

<DIV id="telo">

	<div id="tab-0" class="tabbody">

		<fieldset class="pad10 notoverflow">

				<legend>Результаты за период <b id="mon" class="red">Не выбрано</b></legend>

				<div class="infodiv margbot10">

					<div class="inline period">

						<div class="inline select"><b class="blue">Год:</b>
							<select name="year" id="year">
								<?php
								for($i = date('Y')-2; $i <= date('Y'); $i++){

									if(!$year and $i == date('Y')) $s = 'selected';
									elseif($year and $i == $year) $s = 'selected';
									else $s = '';

									print '<option value="'.$i.'" '.$s.'>'.$i.'&nbsp;&nbsp;</option>';
								}
								?>
							</select>
						</div>
						<div class="inline select margleft10"><b class="blue">Месяц:</b>
							<select name="month" id="month">
								<?php
								for($i = 1; $i <= 12; $i++){

									if(!$mon and $i == date('m')) $s = 'selected';
									elseif($mon and $i == $mon) $s = 'selected';
									else $s = '';

									print '<option value="'.$i.'" '.$s.'>'.ru_mon($i).'</option>';
								}
								?>
							</select>
						</div>

					</div>

					<span id="greenbutton" class="noprint div-center">
						<a href="javascript:void(0)" onclick="loadData()" class="marg0 button">Расчет</a>&nbsp;
					</span>

					<span class="hidden button">
						<a href="files/<?=$fpath?>" id="efile" class="button" title="">Скачать файл Excel</a>
					</span>

				</div>

				<div class="wrapper">

					<table width="100%" border="0" cellspacing="0" cellpadding="4" class="bgwhite tablesorter" id="dataTable" align="center">
					<thead>
					<tr>
						<th width="20" class="{ filter: false }">№</th>
						<th width="">Сотрудник</th>
						<th width="90" class="{sorter: 'digit'}">Оборот.План</th>
						<th width="90" class="{sorter: 'digit'}">Оборот.Факт</th>
						<th width="70">Выполнение</th>
						<th width="50">Коэф.</th>
						<th width="90" class="{sorter: 'digit'}">Маржа.План</th>
						<th width="90" class="{sorter: 'digit'}">Маржа.Факт</th>
						<th width="70">Выполнение</th>
						<th width="50">Коэф.</th>
						<th width="80" class="{sorter: 'digit'}">Оклад</th>
						<th width="80" class="{sorter: 'digit'}">Премия</th>
						<th width="80" class="{sorter: 'digit'}">К выдаче</th>
					</tr>
					</thead>
					<tbody></tbody>
					</table>

				</div>

				<div class="pad10 hidden">
				<span id="greenbutton" class="noprint div-center">
					<a href="javascript:void(0)" onclick="loadData()" class="marg0 button"><i class="icon-plus-circled"></i>&nbsp;&nbsp;Сформировать</a>&nbsp;
				</span>
				</div>

			</fieldset>

	</div>

	<div id="tab-1" class="tabbody hidden table">

		<form action="index.php" method="post" enctype="multipart/form-data" name="form" id="form">
		<input type="hidden" id="action" name="action" value="save">

		<fieldset class="pad10 notoverflow">

			<legend><b>Список сотрудников</b></legend>

			<div class="wrapper2">

			<table width="100%" border="0" cellspacing="0" cellpadding="4" class="bborder bgwhite" id="usersTable" align="center">
			<thead>
			<tr>
				<th width="20" class="{ filter: false }">№</th>
				<th width="">ФИО</th>
				<th width="180">Должность</th>
				<th width="120">Оклад</th>
				<th width="80">Премия</th>
				<th width="100" class="{ sorter: data }">Дата приема</th>
				<th width="150" class="{ sorter: false, filter: false }">Действие</th>
			</tr>
			</thead>
			<tbody></tbody>
			</table>

			</div>

		</fieldset>

		<div class="pad10 margbot10">

			<span id="greenbutton" class="noprint pull-aright">
				<a href="javascript:void(0)" onclick="sync()" class="marg0 button" data-action="edit"><i class="icon-arrows-cw"></i>&nbsp;&nbsp;Синхронизировать</a>&nbsp;
			</span>

		</div>

		</form>

	</div>

	<div id="tab-2" class="tabbody hidden">

		<fieldset class="pad10" style="overflow: auto; height: 450px">

			<legend>Файлы отчетов</legend>

			<div class="infodiv margbot10">

				<table width="100%" border="0" cellspacing="0" cellpadding="4" class="bborder bgwhite" id="filesTable" align="center">
					<thead>
					<tr>
						<th width="20">№</th>
						<th width="">Имя</th>
						<th width="120">Размер</th>
						<th width="120">Дата</th>
						<th width="200">Период</th>
						<th width="180">Действие</th>
					</tr>
					</thead>
					<tbody></tbody>
				</table>

			</div>

		</fieldset>

	</div>

	<div id="tab-3" class="tabbody hidden">

		<fieldset class="pad10" style="overflow: auto; height: 450px">

			<legend>Справка по плагину</legend>

			<div class="infodiv margbot10">

				<pre id="copyright">
##################################################
#                                                #
#   Плагин разработан для SalesMan CRM v.2016    #
#   Разработчик: Владислав Андреев               #
#   Контакты:                                    #
#     - Сайт:  http://isaler.ru                  #
#     - Email: a.vladislav.g@gmail.com           #
#     - Скайп: andreev.v.g                       #
#                                                #
##################################################
				</pre>

				<hr>

				<div class="margbot10">
					<h2>Доступы</h2>

					<p>По умолчанию доступ к приложению имеют ВСЕ руководители и администраторы. Для ограничения доступа конкретным сотрудникам необходимо провести настройки в разделе "Доступы".</p>

					<hr>

					<h2>Настройка Процентов</h2>

					<p>Расчеты поддерживают одновременный учет выполнения планов как по обороту, так и по марже.</p>

					<ul>
						<li>Укажите базовый Процент, выплачиваемый менеджеру от Маржи</li>
						<li>Укажите коэффициенты, в зависимости от процентного выполнения планов</li>
						<li>Если нужно учитывать только маржу, то для Оборота все коэффициенты укажите = 1</li>
					</ul>

					<hr>

					<h2>Логика расчетов</h2>

					<ul>
						<li>Учитываются только сотрудники с отметкой "Имеет план продаж" в личных настройках</li>
						<li>В квоту сотрудника попадают все сделки (или оплаты) подчиненных сотрудников, если не указано "План продаж индивидуальный" в личных настройках</li>
						<?php
						if($other[2]  != 'yes') print $text = '<li>В отчет попадают ВСЕ <b>активные</b> сделки и <b>закрытые</b> сделки, Дата.Закрытия которых совпадают с указанным месяцем</li>';
						if($other[2]  == 'yes' and $other[18] != 'yes') print $text = '<li>Расчеты строятся по <b>оплаченным счетам в периоде</b> в соответствии с настройками системы</li>';
						if($other[2]  == 'yes' and $other[18] == 'yes') print $text = '<li>Расчеты строятся по <b>оплаченным счетам</b> в Сделках, <b>закрытых в отчетном периоде</b> в соответствии с настройками системы</li>';
						?>
						<!--<li><b>По закрытию сделки</b> - учитываются суммы Оборота и Маржи по сделкам, закрытым в указанном периоде</li>
						<li><b>По оплатам</b> - учитываются суммы оплаченных счетов в указанном периоде. При частичной оплате сделки (несколько счетов) маржа расчитывается от суммы сделки пропорционально сумме оплаченного счета</li>
						<li><b>По оплатам в закрытых сделках</b> - учитываются суммы всех оплаченных счетов в сделках, закрытых в указанном периоде</li>-->
					</ul>

					<p>Премия = Маржа * Процент премии * Коэф. по обороту * Коэф. по марже</p>

				</div>

			</div>

		</fieldset>

	</div>

</DIV>

<div class="h10"></div>

<hr>

<div class="h40 gray center-text">Сделано для SalesMan CRM</div>

<script src="js/jquery.min.js"></script>
<script src="js/jquery.metadata.js"></script>
<script src="js/moment.min.js"></script>

<script src="plugins/tablesorter/jquery.tablesorter.js"></script>
<script src="plugins/tablesorter/jquery.tablesorter.widgets.js"></script>
<script src="plugins/tablesorter/widgets/widget-cssStickyHeaders.min.js"></script>
<script>

	$(document).ready(function() {

		var fh  = $(window).height() - 210;
		var fh2 = $(window).height() - 310;

		$('fieldset:not(.notoverflow)').height(fh);
		$('.wrapper').height(fh2);
		$('.wrapper2').height(fh2 + 80);

		$("#dataTable").tablesorter({

			widthFixed : true,
			widgets: [ 'cssStickyHeaders' ],

			widgetOptions: {
				cssStickyHeaders_attachTo      : '.wrapper',
				cssStickyHeaders_addCaption    : true
			}

		});

		$("#usersTable").tablesorter({

			widthFixed : true,
			widgets: [ 'cssStickyHeaders' ],

			widgetOptions: {
				cssStickyHeaders_attachTo      : '.wrapper2',
				cssStickyHeaders_addCaption    : true
			}

		});

		loadUsers();

	});

	$(document).on('click','.ytab',function(){

		var id = $(this).data('id');

		if(id != undefined) {

			$('#dtabs').find('li').not(this).removeClass('current');
			$(this).addClass('current');

			$('#telo').find('div.tabbody').addClass('hidden');
			$('#tab-' + id).removeClass('hidden');

			//if(id == 0 && $('#dataTable tbody').html() == '') loadData();
			if (id == 1) loadUsers();
			if (id == 2) loadFiles();

		}

	});
	$(document).on('click','.yedit',function(){

		var id = $(this).closest('tr').data('id');
		var action = $(this).data('action');

		if(id == 'undefined') id = '0';

		doLoad('index.php?action='+action+'&id=' + id);

	});
	$(document).on('click','.close', function(){
		DClose();
	});
	$(document).on('click','.yfile',function(){

		var file = $(this).closest('tr').data('file');
		var action = $(this).data('action');

		$.get('index.php?action='+action+'&file=' + file, function(data){

			yNotifyMe("CRM. Результат,"+data+",signal.png");
			loadFiles();

		});

	});
	$(document).on('click','.ydeal',function(){

		var user = $(this).closest('tr').data('user');
		var mon  = $('#month').val();
		var year = $('#year').val();

		doLoad('index.php?action=loaddeals&user=' + user +'&year=' + year +'&mon=' + mon);

	});

	function loadData(){

		$('#dataTable tbody').empty().append('<div id="loader"><img src="../../images/loading.gif"></div>');

		var str = '&mon='+$('#month').val()+'&year='+$('#year').val();

		$.get('index.php?action=loaddata', str, function (datas) {

				var table = '';
				var data = datas.list;
				var eperiod = datas.period;
				var efile = datas.file;

				for (var i in data) {

					var number = parseInt(i) + 1;

					table = table +
						'<tr height="40" class="ha hand ydeal" data-user="'+data[i].user+'">' +
						'<td>' + number + '</td>' +
						'<td><b class="blue">' + data[i].name + '</b></td>' +
						'<td align="right">' + number_format(data[i].oborotPlan, 2, ',', ' ') + '</td>' +
						'<td align="right">' + number_format(data[i].oborotFact, 2, ',', ' ') + '</td>' +
						'<td align="right">' + number_format(data[i].oborotDo, 2, ',', ' ') + ' %</td>' +
						'<td align="center">' + data[i].oborotK + '</td>' +
						'<td align="right">' + number_format(data[i].margaPlan, 2, ',', ' ') + '</td>' +
						'<td align="right">' + number_format(data[i].margaFact, 2, ',', ' ') + '</td>' +
						'<td align="right">' + number_format(data[i].margaDo, 2, ',', ' ') + ' %</td>' +
						'<td align="center">' + data[i].margaK + '</td>' +
						'<td align="right">' + number_format(data[i].oklad, 2, ',', ' ') + '</td>' +
						'<td align="right" class="greenbg-sub"><b class="green fs-10">' + number_format(data[i].bonus, 2, ',', ' ') + '</b></td>' +
						'<td align="right" class="bluebg-sub"><b class="blue fs-10">' + number_format(data[i].totall, 2, ',', ' ') + '</b></td>' +
						'</tr>';

				}

				$('#dataTable tbody').empty().html(table);
				$('#mon').empty().html(eperiod);

				$('#efile').attr('href', efile).closest('span').removeClass('hidden');
				//$('#efile').closest('div.infodiv').removeClass('hidden');

				var resort = true;
				$("#dataTable").trigger("update", [resort]);

			}, 'json')
			.complete(function () {

				$('#dataTable').find('td').each(function () {
					var text = $(this).html();
					if (parseFloat(text) == 0) $(this).addClass('gray');
				});

			});

	}
	function loadUsers(){

		$('#usersTable tbody').empty().append('<div id="loader"><img src="../../images/loading.gif"></div>');

		$.get('index.php?action=loadusers', function(data){

			var table = '';

			for(var i in data){

				var s = '';
				var number = parseInt(i) + 1;

				table = table +
					'<tr height="40" class="ha" data-id="' + data[i].id +'">' +
						'<td>' + number +'</td>' +
						'<td><b class="'+s+'">' + data[i].name +'</b></td>' +
						'<td align="">' + data[i].status +'</td>' +
						'<td align="right">' + number_format(data[i].oklad, 2, ',', ' ') +'</td>' +
						'<td align="center">' + number_format(data[i].k1, 2, ',', ' ') +' %</td>' +
						'<td align="center">' + data[i].date +'</td>' +
						'<td align="center"><div style="z-index: 0;">' +
						'<a href="javascript:void(0)" title="Редактировать" class="yedit" data-action="edit">Изменить</a>&nbsp;&nbsp;<span class="gray">' +
						'</div></td>' +
					'</tr>';

			}

			$('#usersTable tbody').empty().html(table);

			var resort = true;
			$("#usersTable").trigger("update", [resort]);

		},'json')
		.complete(function () {

			$('#usersTable').find('td').each(function () {
				var text = $(this).html();
				if (parseFloat(text) == 0) $(this).addClass('gray');
			});

		});
	}
	function loadFiles(){

		$('#filesTable tbody').empty().append('<div id="loader"><img src="../../images/loading.gif"></div>');

		$.get('index.php?action=loadfiles', function(data){

			var table = '';

			for(var i in data){

				table = table + '' +
					'<tr height="40" class="ha" data-file="'+ data[i].url +'">' +
					'<td align="center">' + data[i].num +'</td>' +
					'<td><b>' + data[i].name +'</b></td>' +
					'<td align="center">' + data[i].size +' kb</td>' +
					'<td align="center">' + data[i].date +'</td>' +
					'<td align="center">' + data[i].start + ' ' + data[i].end +'</td>' +
					'<td align="center">' +
					'<a href="files/'+ data[i].url +'" title="Скачать"><i class="icon-download blue"></i>Скачать</a>&nbsp;&nbsp;' +
					'<a href="javascript:void(0)" title="Удалить" class="yfile" data-action="fdelete"><i class="icon-cancel-circled red"></i>Удалить</a>' +
					'</td>' +
					'</tr>';

			}

			$('#filesTable tbody').empty().html(table);

		},'json');
	}
	function setAccess(){
		doLoad('index.php?action=access');
	}
	function sync(){
		$.get('index.php?action=sync', function(data) {

			yNotifyMe("Yoolla. Результат,"+data+",signal.png");
			loadUsers();

		});
	}

	function doLoad(url){
		$('#dialog_container').css('height', $(window).height());
		$('#dialog').css('width','500px').css('height','unset').css('display', 'none');
		$('#dialog_container').css('display', 'block');
		$('.dialog-preloader').center().css('display', 'block');

		$.get(url, function(data){
				$('#resultdiv').empty().html(data);
				$('#dialog').center();
				$("a.button:contains('Отмена')").addClass('bcancel');
				$("a.button:contains('Закрыть')").addClass('bcancel');
			})
			.complete(function() {

				$('#dialog').css('display', 'block');
				$('.dialog-preloader').css('display', 'none');

			});

		$(".popmenu").hide();
		$(".popmenu-top").hide();
		return false;
	}
	function yNotifyMe(data){

		if (("Notification" in window)) {

			var data     = data.split(",");
			var title    = data[0];
			var content  = data[1];
			var img      = data[2];
			var id       = data[3];
			var url      = data[4];

			if (Notification.permission === "granted") {
				var notification = new Notification(title, {
					lang: 'ru-RU',
					body: content,
					icon: 'images/'+img,
					tag: id
				});
			}
			// В противном случае, мы должны спросить у пользователя разрешение
			else if (Notification.permission === 'default') {
				Notification.requestPermission(function (permission) {

					// Не зависимо от ответа, сохраняем его в настройках
					if(!('permission' in Notification)) {
						Notification.permission = permission;
					}
					// Если разрешение получено, то создадим уведомление
					if (permission === "granted") {
						var notification = new Notification(title, {
							lang: 'ru-RU',
							body: content,
							icon: 'images/'+img,
							tag: id
						});
					}
				});
			}

			notification.onshow=function(){
				var wpmupsnd = new Audio("../../images/mp3/bigbox.mp3");
				wpmupsnd.volume = 0.2;
				wpmupsnd.play();
			};
			notification.onclick=function(){

			};

		}
	}

	jQuery.fn.center = function(){
		var w = $(window);

		this.css("position","absolute");
		this.css("top",(w.height()-this.height())/2 + "px");
		this.css("left",(w.width()-this.width())/2+w.scrollLeft() + "px");

		return this;
	}

	function DClose() {
		$('#dialog').css('display', 'none');
		$('#resultdiv').empty();
		$('#dialog_container').css('display', 'none')
		$('.dialog-preloader').css('display', 'none');
		$('#dialog');
		$('#dialog').css('width','500px').css('height','unset').css('position','absolute').css('margin','unset');
	}

	function number_format( number, decimals, dec_point, thousands_sep ) {
		// Format a number with grouped thousands
		//
		// +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
		// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		// +	 bugfix by: Michael White (http://crestidg.com)

		var i, j, kw, kd, km;

		// input sanitation & defaults
		if( isNaN(decimals = Math.abs(decimals)) ){
			decimals = 2;
		}
		if( dec_point == undefined ){
			dec_point = ",";
		}
		if( thousands_sep == undefined ){
			thousands_sep = ".";
		}

		i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

		if( (j = i.length) > 3 ){
			j = j % 3;
		} else{
			j = 0;
		}

		km = (j ? i.substr(0, j) + thousands_sep : "");
		kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
		//kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).slice(2) : "");
		kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");


		return km + kw + kd;
	}

</script>
</body>
</html>