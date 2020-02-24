<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*      SalesMan Project        */
/*        www.isaler.ru         */
/*          ver. 2019.2         */
/* ============================ */

error_reporting(E_ERROR);

$rootpath = realpath(__DIR__.'/../../../');

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";

$action = $_REQUEST['action'];

$setFile = "../data/settings.json";

$settings = json_decode(file_get_contents($setFile), true);

if ($action == "settings.do") {

	$params = [
		//этап, на который надо перевести сделку
		"step"      => $_REQUEST['step'],
		//отправлять welcome по смс
		"sendsms"   => ($_REQUEST['sendsms'] == 'true') ? true : false,
		//текст смс
		"text"      => "Добро пожаловать на конференцию AsterConf. Voxlink",
		//артикулы участия онлайн
		"online"    => $_REQUEST['online'],
		//типы сделок
		"tips"      => $_REQUEST['tips'],
		//направление
		"direction" => $_REQUEST['direction'],
		//расч.счет для налички
		"rs"        => $_REQUEST['rs'],
		//шаблон документа
		"template"  => $_REQUEST['template'],
		//тема сообщения для отправки билета
		"theme"     => $_REQUEST['theme'],
		//шаблон сообщения для отправки билета
		"ticket"    => $_REQUEST['ticket'],
		//сотрудник, от имени которого отправим почту
		"iduser"    => $_REQUEST['iduser'],
	];

	if (is_writable("../data/")) {

		file_put_contents($setFile, json_encode_cyr($params));
		print "Сохранено";

	}
	else {

		print "Не могу сохранить";

	}

	exit();

}

if ($action == "pay.do") {

	require_once $rootpath."/inc/class/Deal.php";
	require_once $rootpath."/inc/class/Invoice.php";

	$crid   = $_REQUEST['crid'];
	$params = $_REQUEST;

	$params['newstep'] = $settings['step'];

	$invoice = new \Salesman\Invoice();
	$result  = $invoice -> doit($crid, $params);

	$mes = ($result['error']['text'] != '') ? $result['error']['text'] : $result['text'];

	print json_encode_cyr([
		"result" => $result['text'],
		"error"  => $result['error']['text']
	]);

	exit();

}

if ($action == "settings") {

	//загружаем все возможные цепочки и конвертируем в JSON
	$mFunnel = json_encode_cyr(getMultiStepList());

	require_once $rootpath."/inc/class/Elements.php";
	$element = new \Salesman\Elements();

	//print_r($settings);

	if (empty($settings))
		//первоначальные настройки
		$settings = [
			//этап, на который надо перевести сделку
			"step"      => getStep("90"),
			//отправлять welcome по смс
			"sendsms"   => true,
			//текст смс
			"text"      => "Добро пожаловать на конференцию AsterConf. Voxlink",
			//артикулы участия онлайн
			"online"    => 7,
			//типы сделок
			"tips"      => 8,
			//направление
			"direction" => 3
		];

	?>
	<DIV class="zagolovok"><B>Настройка</B></DIV>
	<form action="php/settings.php" method="post" enctype="multipart/form-data" name="sForm" id="sForm">
		<input type="hidden" id="action" name="action" value="settings.do">

		<div id="flyitbox"></div>

		<div id="formtabs" style="overflow-y: auto; overflow-x: hidden; max-height: 80vh" class="p5">

			<div class="divider mt10 mb10">Типы сделок</div>

			<div class="flex-container box--child pl10 pr10">

				<div class="flex-string wp100">

					<?php
					print $element -> DealTypeSelect("tips", [
						"noempty"  => true,
						"multiple" => false,
						"class"    => "wp100 multiselects",
						"sel"      => $settings['tips']
					]);
					?>

				</div>

			</div>

			<div class="divider mt10 mb10">Выбор конференции</div>

			<div class="flex-container box--child pl10 pr10">

				<div class="flex-string wp100">

					<?php
					print $element -> DirectionSelect("direction", [
						"noempty"  => true,
						"multiple" => false,
						"class"    => "wp100",
						"sel"      => $settings['direction']
					]);
					?>

				</div>

			</div>

			<div class="divider mt10 mb10">Онлайн-участие</div>

			<div class="flex-container box--child pl10 pr10">

				<div class="flex-string wp100">

					<?php
					print $element -> DealTypeSelect("online", [
						"noempty"  => true,
						"multiple" => false,
						"class"    => "wp100",
						"sel"      => $settings['online']
					]);
					?>

				</div>

			</div>

			<div class="divider mt10 mb10">Этап сделки</div>

			<div class="pl10 pr10 box--child">

				<div class="flex-container mt10 box--child">

					<div class="flex-string wp100">

						<?php
						print $element -> StepSelectFromFunnel("step", [
							"tip"       => $settings['tips'],
							"direction" => $settings['direction'],
							"noempty"   => true,
							"multiple"  => false,
							"class"     => "wp100",
							"sel"       => $settings['step']
						]);
						?>

						<div class="infodiv p5 mb10">Этап, на который будет переведена сделка после регистрации участика</div>

					</div>

				</div>

			</div>

			<div class="divider mt10 mb10">Наличная оплата</div>

			<div class="pl10 pr10 box--child">

				<div class="flex-container mt10 box--child">

					<div class="flex-string wp100">

						<?php
						print $element -> rsSelect("rs", [
							"noempty"  => true,
							"multiple" => false,
							"class"    => "wp100",
							"sel"      => $settings['rs']
						]);
						?>

						<div class="infodiv p5 mb10">На какой счет вносить оплату для платежей на конференции</div>

					</div>

				</div>

			</div>

			<div class="divider mt10 mb10">Отправка приветственного SMS</div>

			<?php
			/**
			 * Проверим активность плагина
			 */
			$smsfile = $rootpath.'/plugins/smsSender/data/'.$fpath.'settings.json';
			if(file_exists($smsfile)) {

				$smssender = json_decode(file_get_contents($smsfile), true);
				$smsrez = '<b class="green">Настроена отправка через шлюз "'.$smssender['type'].'"</b>';
				$smsclass = 'success';
				$smscolor = 'green';

			}
			else{

				$smsrez = '<b class="red">Шлюз не настроен. СМС не будут доставлены получателям</b>';
				$smsclass = 'warning';
				$smscolor = 'red';

			}
			?>

			<div class="pl10 pr10 box--child">

				<div class="flex-container mt10 box--child">

					<div class="flex-string mt10 mb20 wp100">

						<div class="<?=$smsclass?> m0"><?=$smsrez?></div>

					</div>

					<div class="flex-string mt10 mb20 wp100">

						<div class="pl10">

							<label for="sendsms" class="switch">
								<input type="checkbox" name="sendsms" id="sendsms" value="true" <?= ($settings['sendsms'] == true ? "checked" : "") ?>>
								<span class="slider empty"></span>
							</label>
							<label for="sendsms" class="inline">&nbsp;Отправлять&nbsp;<i class="icon-info-circled blue"></i></label>

						</div>

					</div>
					<div class="flex-string wp100">

						<textarea id="text" name="text" class="wp100" rows="2"><?= $settings['text'] ?></textarea>

					</div>

					<div class="infodiv p5 mb10 wp100">Отправка СМС осуществляется через плагин <b>smsSender</b></div>

				</div>

			</div>

			<div class="divider mt10 mb10">Webhook</div>

			<div class="pl10 pr10 box--child">

				<div class="flex-container mt10 box--child">

					<div id="webhook" class="flex-string mt10 mb20 wp100 fs-11"></div>
					<div class="infodiv p5 mb10 wp100">Webhook срабатывает на изменение этапа сделки >= 80%. При этом генерируется Билет для каждого контакта и отправляется по email</div>

				</div>

			</div>

			<div class="divider mt10 mb10">Шаблон для генерации Билета</div>

			<div class="pl10 pr10 box--child">

				<div class="flex-container mt10 box--child">

					<div class="flex-string mt10 mb20 wp100">

						<select name="template" id="template" class="wp100">
							<option value="">--выбор--</option>
							<?php
							$resultt = $db -> getAll("SELECT * FROM ".$sqlname."contract_type WHERE type NOT IN ('invoice','get_akt','get_aktper') AND identity = '$identity' ORDER by title");
							foreach ($resultt as $data) {

								print '<optiongroup>'.$data['title'].'</optiongroup>';

								$res = $db -> getAll("SELECT * FROM ".$sqlname."contract_temp WHERE typeid = '$data[id]' AND identity = '$identity' ORDER BY title");
								foreach ($res as $da) {
									print '<option value="'.$da['id'].'" '.($settings['template'] == $da['id'] ? "selected" : "").'>'.$da['title'].'</option>';
								}

							}
							?>
						</select>

					</div>

				</div>

			</div>

			<div class="divider mt10 mb10">Email-сообщение с билетом</div>

			<div class="pl10 pr10 box--child">

				<div class="flex-container mt10 box--child">

					<div class="flex-string mt10 mb20 wp100">

						<?php
						print $element -> UsersSelect("iduser", [
							"noempty"  => true,
							"multiple" => false,
							"class"    => "wp100",
							"sel"      => $settings['iduser']
						]);
						?>
						<div class="fs-09 gray">От кого будет отправлено сообщение</div>

					</div>
					<div class="flex-string wp100">

						<input id="theme" name="theme" class="wp100" value="<?= $settings['theme'] ?>">
						<div class="fs-09 gray">Тема сообщения</div>

					</div>
					<div class="flex-string wp100">

						<textarea id="ticket" name="ticket" class="wp100" rows="5"><?= $settings['ticket'] ?></textarea>
						<div class="fs-09 gray">Текст сообщения</div>

					</div>

				</div>

			</div>

			<div class="space-50"></div>

		</div>

		<hr>

		<div class="text-right button--pane">

			<A href="javascript:void(0)" onClick="save()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onClick="new aDClose()" class="button">Отмена</A>

		</div>

	</form>

	<script>

		var mFunnel = $.parseJSON('<?=$mFunnel?>');
		var $etips = $('#tips');
		var currentStep = <?=$settings['step']?>;

		$(document).ready(function () {

			$('#ticket').autoHeight(200);
			$('#dialog').css('width', '700px').center();

			$(".connected-list").css('height', "120px");
			$(".multiselect").multiselect({sortable: true, searchable: true});

			checkWebhook();

			//изменение списка этапов
			if (Object.keys(mFunnel).length > 0) {

				$etips.bind('change', function () {

					let tip = $('option:selected', this).val();
					let direction = $('#direction option:selected').val();

					if (parseInt(direction) > 0) {

						let steps = mFunnel[direction][tip]['nsteps'];
						let def = mFunnel[direction][tip]['default'];
						let str = '';
						let $s;

						if(currentStep > 0)
							def = currentStep;

						for (let i in steps) {

							$s = (steps[i].id === def) ? "selected" : "";

							str += '<option value="' + steps[i].id + '" ' + $s + '>' + steps[i].name + '% - ' + steps[i].content + '</option>';

						}

						$('#step').html(str);

					}

				});

				$('#direction').bind('change', function () {

					$etips.trigger('change');

				});

				//$etips.trigger('change');

			}

		});

		function save() {

			let str = $('#sForm').serialize();

			$('#dialog_container').css('display', 'none');

			$.post("php/settings.php", str, function (data) {

				yNotifyMe("CRM. Результат," + data + ",signal.png");
				new aDClose();

				aconf.clearInput();
				aconf.getCounts();

			});

		}

	</script>
	<?php

	exit();

}

if ($action == "pay") {

	$pid  = $_REQUEST['pid'];
	$clid = $_REQUEST['clid'];
	$did  = $_REQUEST['did'];

	$credit         = $db -> getRow("SELECT * FROM ".$sqlname."credit WHERE did = '$did' and identity = '$identity'");
	$credit['mcid'] = $db -> getOne("SELECT mcid FROM ".$sqlname."dogovor WHERE did = '$did' and identity = '$identity'");

	$datum_credit = current_datum();
	$isper        = (isServices($credit['did'])) ? 'yes' : '';

	?>
	<div class="zagolovok">Отметка о поступлении платежа</div>
	<form method="post" action="php/settings.php" enctype="multipart/form-data" name="sForm" id="sForm">
		<input name="action" id="action" type="hidden" value="pay.do">
		<input name="crid" id="crid" type="hidden" value="<?= $credit['crid'] ?>">
		<input name="did" id="did" type="hidden" value="<?= $credit['did'] ?>">
		<input name="pid" id="pid" type="hidden" value="<?= $pid ?>">

		<div id="formtabs" class="box--child wp100" style="max-height: 70vh; overflow-y:auto !important; overflow-x:hidden">

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Дата:</div>
				<div class="flex-string wp80 pl10 norelativ">
					<input name="datum_credit" type="text" id="datum_credit" class="w160 inputdate" readonly value="<?= current_datum() ?>">
				</div>

			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Номер счета:</div>
				<div class="flex-string wp80 pl10 norelativ">
					<input type="text" name="invoice" class="required w160" id="invoice" value="<?= $credit['invoice'] ?>">
				</div>

			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Расч.счет:</div>
				<div class="flex-string wp80 pl10 norelativ">
					<select name="rs" id="rs" class="required wp95">
						<option value="">--выбор--</option>
						<?php
						$result = $db -> query("SELECT * FROM ".$sqlname."mycomps WHERE id = '".$credit['mcid']."' and identity = '$identity' ORDER BY name_shot");
						while ($data = $db -> fetch($result)) {
							?>
							<optgroup label="<?= $data['name_shot'] ?>">
								<?php
								$res = $db -> query("SELECT * FROM ".$sqlname."mycomps_recv WHERE cid = '".$data['id']."' and identity = '$identity' ORDER BY title");
								while ($da = $db -> fetch($res)) {

									$s = ($da['id'] == $settings['rs']) ? 'selected' : '';

									print '<option value="'.$da['id'].'" '.$s.'>'.$da['title'].'</option>';

								}
								?>
							</optgroup>
						<?php } ?>
					</select>
				</div>

			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Дата платежки:</div>
				<div class="flex-string wp80 pl10 norelativ">
					<input type="date" name="date_do" class="required inputdate w160" id="date_do" value="<?= current_datum() ?>">
				</div>

			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Сумма оплаты:</div>
				<div class="flex-string wp80 pl10 norelativ">
					<input type="text" name="summa" class="required w160" id="summa" value="<?= num_format($credit['summa_credit']) ?>">
				</div>

			</div>

			<div class="space-10"></div>

		</div>

		<div class="attention text-center mt10 p10 Bold">Посетитель <b class="blue"><?=current_person($pid)?></b> будет зарегистрирован</div>

		<div class="infodiv wp100 div-center mt5 p10 fs-12 Bold">

			Сумма по счету:&nbsp;<span class="red"><?= num_format($credit['summa_credit']) ?> <?= $valuta ?></span>
			<input type="hidden" name="summa_credit" id="summa_credit" value="<?= $credit['summa_credit'] ?>">

		</div>

		<hr>

		<div class="button--pane pull-aright">

			<a href="javascript:void(0)" onclick="save()" class="button">Сохранить</a>&nbsp;
			<a href="javascript:void(0)" onClick="new aDClose()" class="button">Отмена</a>

		</div>

	</form>

	<script>

		$(document).ready(function () {

			if (!isMobile) {

				$('.inputdate').each(function () {

					$(this).datepicker({
						dateFormat: 'yy-mm-dd',
						numberOfMonths: 2,
						firstDay: 1,
						dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
						monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
						changeMonth: true,
						changeYear: true,
						yearRange: '1940:2030',
						minDate: new Date(1940, 1 - 1, 1),
						showButtonPanel: true,
						currentText: 'Сегодня',
						closeText: 'Готово'
					});

				});

				$('#dialog').css('width', '600px').center();

				$('#ui-datepicker-div').hide();

			}
			else {

				$('#formtabs').find('.space-10').remove();

				let vh = $(window).height() - $('.button--pane').outerHeight() - $('.infodiv').outerHeight() - $('.attention').outerHeight() - $('.zagolovok').outerHeight() - 50;

				$('#dialog').css({"width":'calc(100vw - 15px)',"height":'calc(100vh - 15px)'}).center();
				$('#formtabs').css({"max-height":vh + 'px'});

			}

		});

		function save() {

			let str = $('#sForm').serialize();

			$('#dialog_container').css('display', 'none');

			$.post("php/settings.php", str, function (data) {

				yNotifyMe("CRM. Результат," + data.result + ",signal.png");
				new aDClose();

				if (data.error !== '') {

					aconf.register('<?=$pid?>', '<?=$clid?>', '<?=$did?>');

					$('#ui-datepicker-div').remove();

				}

			},'json')
				.complete(function(){

					aconf.getCounts();
					aconf.clearInput();

				});

		}

	</script>
	<?php

}

if ($action == 'check.webhook') {

	$hook = array();

	$url  = $scheme.$_SERVER["HTTP_HOST"]."/plugins/Conference/php/webhook";
	$url2 = "{HOME}/plugins/Conference/php/webhook";

	$hook['deal.step'] = ($db -> getOne("SELECT COUNT(*) FROM ".$sqlname."webhook WHERE event = 'deal.change.step' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event") > 0) ? '<span class="green ok Bold" data-event="deal.change.step">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'deal.change.step\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	print 'Подключение к событию "<b>deal.change.step</b>" - '.$hook['deal.step'].'</li>';

	exit();

}