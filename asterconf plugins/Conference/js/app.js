/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*      SalesMan Project        */
/*        www.isaler.ru         */
/*          ver. 2019.2     	*/
/*			Larshin Oleg		*/
/*    							*/
/* ============================ */

let isMobilee = {
	Android: function () {
		return navigator.userAgent.match(/Android/i);
	},
	BlackBerry: function () {
		return navigator.userAgent.match(/BlackBerry/i);
	},
	iOS: function () {
		return navigator.userAgent.match(/iPhone|iPad|iPod/i);
	},
	Opera: function () {
		return navigator.userAgent.match(/Opera Mini/i);
	},
	Windows: function () {
		return navigator.userAgent.match(/IEMobile/i);
	},
	any: function () {
		return (isMobilee.Android() || isMobilee.BlackBerry() || isMobilee.iOS() || isMobilee.Opera() || isMobilee.Windows());
	}
};
let isMace = {

	iOS: function () {
		return navigator.userAgent.match(/Macintosh/i);
	}

};
let isChrome = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
let isSafari = /Safari/.test(navigator.userAgent) && /Apple Computer/.test(navigator.vendor);

let isMobile = false;
let isPad = false;
let isMac = false;

let isCtrl = false;

//console.log(navigator);

if (isMobilee.any() || $(window).width() < 767) {
	isMobile = true;
	isPad = false;
}
if ($(window).width() > 767) {
	isMobile = false;
	isPad = true;
}
if ($(window).width() > 1024) isPad = false;

if (isMace.iOS()) isMac = true;

$(document).ready(function () {

	$.Mustache.load('tpl/template.mustache');

	aconf.getCounts();
	setInterval(aconf.getCounts, 20 * 1000);

	$("#dialog").draggable({handle: ".zagolovok", cursor: "move", opacity: "0.85", containment: "document"});

	$(document).keydown(function (e) {

		let keycode;

		if (e == null) { // ie
			keycode = e.keyCode;
		} else { // mozilla
			keycode = e.which;
		}

		// escape, close box, esc
		if (keycode === 27)
			new aDClose();

		if (keycode === 17)
			isCtrl = true;

	});

	$('#Form').ajaxForm({

		dataType: 'json',
		beforeSubmit: function () {

			let em = 0;

			if ($('.button').hasClass('disabled')) {

				Swal.fire({
					title: "Ошибка",
					text: "Не выбран посетитель",
					type: "error"
				});
				return false;

			}

			$('.information').empty().addClass('hidden');

			$(".required").removeClass("empty").css({"color": "inherit", "background": "#FFF"});
			$(".required").each(function () {

				if ($(this).val() === '') {
					$(this).addClass("empty").css({"color": "#FFF", "background": "#FF8080"});
					em = em + 1;
				}

			});

			if (em > 0) {

				Swal.fire({
					title: "Ошибка",
					text: "Не заполнено " + em + " обязательных полей\n\rОни выделены цветом",
					type: "error"
				});
				return false;

			}
			if (em === 0) {

				return true;

			}

		},
		success: function (data) {

			aconf.clearInput();
			aconf.getCounts();
			aconf.clearInput();

			$('.information').fadeTo(10, 1).html('<div class="div-center fs-12 green Bold">' + data.result + '</div>').removeClass('hidden');

			setTimeout(function () {
				$('.information').addClass('hidden');
			}, 20000);

		}
	});

});

$(window).bind('resizeEnd', function () {

	if (isMobilee.any() || $(window).width() < 767) {
		isMobile = true;
		isPad = false;
	}
	if ($(window).width() > 767) {
		isMobile = false;
		isPad = true;
	}
	if ($(window).width() > 1024) isPad = false;

	$('#dialog').center();

});
$(window).resize(function () {

	if (this.resizeTO) clearTimeout(this.resizeTO);
	this.resizeTO = setTimeout(function () {
		$(this).trigger('resizeEnd');
	}, 500);

	if ($('#dialog').is(':visible')) {

		$('#dialog').center();
		$('.dialog-preloader').center();
		$('#dialog_container').css('height', $(window).height());

	}

});

$(document).on('click', '.dialog--close', function () {

	new aDClose();

});

/*закрытие окна информации*/
$(document).on('click', '.aconf--close', function () {

	$('.information').empty().addClass('hidden');
	$('body').css({"overflow-y": "auto"});

});

/*кнопка запуска поиска вручную*/
$(document).on('click', '.aconf--search', function () {

	aconf.search();

});

/*очистка поисковой строки*/
$(document).on('click', '.aconf--clean', function () {

	aconf.clearInput();
	$('.aconf--close').trigger('click');

});

$(document).on('click', '.aconf--listall', function () {

	let $el = $('#modal');
	let $tip = $(this).data('tip');

	$el.css('left', '0vw').find('.content').empty().html('<div id="loader" style="width: 100%; margin-top: 100px; text-align: center;"><img src="./img/Services.svg" width="50px"></div>');
	$('body').css({"overflow": "hidden"});

	$.getJSON('php/app.php?action=get.list&tip=' + $tip, function (viewData) {

		let ind = viewData.list.length;

		let datalist = {
			list: viewData.list,
			index: function () {

				return viewData.tip === 'registered' ? ind-- : ++window['INDEX'];

			},
			iperson: function () {

				if (this.add === 0) return '<b class="green">' + this.person + '</b>';
				else return '<b class="blue">' + this.person + '</b>';

			},
			resetIndex: function () {

				window['INDEX'] = null;
				//return true;

			}
		};

		$el.find('.content').empty().mustache('totalTpl', datalist).animate({scrollTop: 0}, 200);
		$el.find('.head').html(viewData.head);

	});

});

$(document).on('click', '.close', function () {

	$('#modal').css('left', '100vw')
		.find('.head').empty()
		.find('.content').empty();

	$('body').css({"overflow-y": "auto"});

});

$(document).on('click', '.astring', function () {

	let clid = $(this).data('clid');
	let pid = $(this).data('pid');
	let did = $(this).data('did');
	let person = $(this).find('.person').html();

	aconf.getDeal(pid, clid, did, 'yes');

	$('#code').val(person.replace(/(<([^>]+)>)/ig, ""));

	$('.close').trigger('click');

	//aconf.search();

});

$(document).on('click', '.astringview', function () {

	let clid = $(this).data('clid');
	let pid = $(this).data('pid');
	let did = $(this).data('did');
	let person = $(this).find('.person').html();

	aconf.getDeal(pid, clid, did, 'yes');

	$('#code').val(person.replace(/(<([^>]+)>)/ig, ""));

	$('.close').trigger('click');
	$('.button').addClass('disabled');

	//aconf.search();

});

$(document).on('click', '.aconf--regit', function () {

	let $elm = $(this).closest('.aconf--item');
	let clid = $elm.data('clid');
	let pid = $elm.data('pid');
	let did = $elm.data('did');

	if ($(this).hasClass('aconf--noregit')) {

		adoLoad("php/settings.php?action=pay&did=" + did + '&pid=' + pid + '&clid=' + clid);

	} else if ($(this).hasClass('aconf--regited')) {

		Swal.fire({
			title: "Уже зарегистрирован",
			text: "Посетитель уже зарегистрирован",
			type: "info"
		});

	} else
		aconf.register(pid, clid, did);

});

/*
$(document).on('click', '.aconf--noregit', function () {

	let $elm = $(this).closest('.aconf--item');
	let clid = $elm.data('clid');
	let pid = $elm.data('pid');
	let did = $elm.data('did');

	adoLoad("php/settings.php?action=pay&did=" + did + '&pid=' + pid + '&clid=' + clid);

});
*/

$(document).on('click', '.aconf--info', function () {

	let $elm = $(this).closest('.aconf--item');
	let clid = $elm.data('clid');
	let pid = $elm.data('pid');

	aconf.getDeal(pid, clid);

});

$(document).on('click', '.settings', function () {

	adoLoad("php/settings.php?action=settings");

});

$('input#code').focus(function () {

	$("html, body").animate({scrollTop: $(document).height() * 0.9}, "slow");

	return false;

});

let aconf = {
	//Загрузка счетчиков
	getCounts: function () {

		$.getJSON('php/app.php?action=get.count', function (data) {

			$('.countAll').html(data.counts);
			$('.countReg').html(data.count);

		});

	},

	//Загрузка информации о сделке
	getDeal: function (pid, clid, did, search) {

		$.getJSON('php/app.php?action=get.deal&pid=' + pid + '&clid=' + clid + '&did=' + did, function (data) {

			$('.information').empty().mustache('clientTpl', data).removeClass('hidden');

			if (data.did > 0) {

				$('#did').val(data.did);
				$('#clid').val(clid);
				$('#pid').val(pid);

			}

			if (data.approve === 1) $('.button').removeClass('disabled');

			aconf.getCounts();

		})
			.complete(function () {

				if (search === 'yes')
					aconf.search();

				$('body').animate({
					scrollTop: (300)
				}, 500);

			});

	},

	//очистка инпута
	clearInput: function () {

		$('#code').val('');
		$('#pid').val('0');
		$('#clid').val('0');
		$('#did').val('0');
		//$('.button').addClass('disabled');
		$('.information').empty().addClass('hidden');
		$('.aconf--result').empty();

	},

	//поиск контактов
	search: function () {

		let word = $("#code").val();
		let url = 'php/app.php?action=get.client&word=' + word;

		if (word.length > 1) {

			$.get(url, function (data) {

				if (parseInt(data.count) > 0) {

					$('.aconf--result').empty().mustache('personsTpl', data).removeClass('hidden');
					$('.aconf--close').trigger('click');

				} else {

					$('.aconf--result').html('<div class="attention">Ничего не найдено. Вероятно Постетитель уже зарегистрирован или не планирует посещать конференцию</div>');

				}

			}, "json");

		} else
			Swal.fire({
				title: "Упс, проблема!",
				text: "Укажите больше 1 буквы",
				type: "error"
			});

	},

	//регистрация пользователя
	register: function (pid, clid, did) {

		let url = 'php/app.php?action=register&pid=' + pid + '&clid=' + clid + '&did=' + did;
		let person;

		$.get(url, function (data) {

			//todo: обработчик регистрации

			if (data.error !== 0) {

				Swal.fire({
					title: "Упс, проблема!",
					text: data.result,
					type: "error"
				});

			}
			else {

				// присваиваем переменной массив с ответом
				person = data;

				$('.aconf--result').empty();

				Swal.fire({
					title: "Успешно!",
					text: data.result,
					type: "success"
				});

			}


		}, "json")
			.complete(function () {

				aconf.getCounts();

				/**
				 * Добавляем печать для принтера Dymo
				 */
				try {

					//console.log("Создаем XML");
					// open label
					let labelXml = '<?xml version="1.0" encoding="utf-8"?>\
		                <DieCutLabel Version="8.0" Units="twips">\
		                    <PaperOrientation>Landscape</PaperOrientation>\
		                    <Id>Address</Id>\
		                    <PaperName>11352 Return Address Int</PaperName>\
		                    <DrawCommands/>\
		                    <ObjectInfo>\
		                        <TextObject>\
		                            <Name>Name</Name>\
		                            <ForeColor Alpha="255" Red="0" Green="0" Blue="0" />\
		                            <BackColor Alpha="0" Red="255" Green="255" Blue="255" />\
		                            <LinkedObjectName></LinkedObjectName>\
		                            <Rotation>Rotation0</Rotation>\
		                            <IsMirrored>False</IsMirrored>\
		                            <IsVariable>True</IsVariable>\
		                            <HorizontalAlignment>Center</HorizontalAlignment>\
		                            <VerticalAlignment>Top</VerticalAlignment>\
		                            <TextFitMode>AlwaysFit</TextFitMode>\
		                            <UseFullFontHeight>True</UseFullFontHeight>\
		                            <Verticalized>False</Verticalized>\
		                            <StyledText>\
		                                <Element>\
		                                <Attributes>\
		                                    <Font Family="Arial" Size="20" Bold="True" Italic="False" Underline="False" Strikeout="False" />\
		                                </Attributes>\
		                                </Element>\
		                            </StyledText>\
		                        </TextObject>\
		                        <Bounds X="0" Y="95" Width="3073" Height="616" />\
		                    </ObjectInfo>\
		                    <ObjectInfo>\
		                        <TextObject>\
		                            <Name>Surname</Name>\
		                            <ForeColor Alpha="255" Red="0" Green="0" Blue="0" />\
		                            <BackColor Alpha="0" Red="255" Green="255" Blue="255" />\
		                            <LinkedObjectName></LinkedObjectName>\
		                            <Rotation>Rotation0</Rotation>\
		                            <IsMirrored>False</IsMirrored>\
		                            <IsVariable>True</IsVariable>\
		                            <HorizontalAlignment>Center</HorizontalAlignment>\
		                            <VerticalAlignment>Middle</VerticalAlignment>\
		                            <TextFitMode>AlwaysFit</TextFitMode>\
		                            <UseFullFontHeight>True</UseFullFontHeight>\
		                            <Verticalized>False</Verticalized>\
		                            <StyledText>\
		                                <Element>\
		                                <Attributes>\
		                                    <Font Family="Arial" Size="8" Bold="False" Italic="False" Underline="False" Strikeout="False" />\
		                                </Attributes>\
		                                </Element>\
		                            </StyledText>\
		                        </TextObject>\
		                        <Bounds X="0" Y="800" Width="3073" Height="300" />\
		                    </ObjectInfo>\
		                    <ObjectInfo>\
		                        <TextObject>\
		                            <Name>Company</Name>\
		                            <ForeColor Alpha="255" Red="0" Green="0" Blue="0" />\
		                            <BackColor Alpha="0" Red="255" Green="255" Blue="255" />\
		                            <LinkedObjectName></LinkedObjectName>\
		                            <Rotation>Rotation0</Rotation>\
		                            <IsMirrored>False</IsMirrored>\
		                            <IsVariable>True</IsVariable>\
		                            <HorizontalAlignment>Center</HorizontalAlignment>\
		                            <VerticalAlignment>Middle</VerticalAlignment>\
		                            <TextFitMode>AlwaysFit</TextFitMode>\
		                            <UseFullFontHeight>True</UseFullFontHeight>\
		                            <Verticalized>False</Verticalized>\
		                            <StyledText>\
		                                <Element>\
		                                <Attributes>\
		                                    <Font Family="Arial" Size="8" Bold="False" Italic="False" Underline="False" Strikeout="False" />\
		                                </Attributes>\
		                                </Element>\
		                            </StyledText>\
		                        </TextObject>\
		                        <Bounds X="0" Y="1122" Width="3073" Height="200" />\
		                    </ObjectInfo>\
		                </DieCutLabel>';

					let label = dymo.label.framework.openLabelXml(labelXml);

					// set label text
					/*
					console.log(data.pers_name);
					console.log(data.pers_surname);
					console.log(data.pers_company);
					*/

					let name = person.pers_name;
					let surname = person.pers_surname;
					let company = person.pers_company;

					label.setObjectText("Name", name);
					label.setObjectText("Surname", surname);
					label.setObjectText("Company", company);

					// select printer to print on
					// for simplicity sake just use the first LabelWriter printer
					let printers = dymo.label.framework.getPrinters();

					if (printers.length === 0) {

						throw "No DYMO printers are installed. Install DYMO printers.";
						//console.log("No DYMO printers are installed. Install DYMO printers.")

					}

					let printerName = "";

					for (let i = 0; i < printers.length; ++i) {

						let printer = printers[i];

						if (printer.printerType === "LabelWriterPrinter") {

							printerName = printer.name;
							break;

						}

					}

					if (printerName === "") {

						throw "No LabelWriter printers found. Install LabelWriter printer";
						console.log("No LabelWriter printers found. Install LabelWriter printer");

					}

					// finally print the label
					label.print(printerName);
					label.print(printerName);

				}
				catch (e) {

					//alert(e.message || e);

					Swal.fire({
						title: "Ошибка!",
						text: e.message || e,
						type: "error"
					});

					// console.log("error!!!!!!!!");

				}
				/**
				 * Конец скрипта печати
				 */

			});

	}

};

function checkWebhook() {

	$('#webhook').load('php/settings.php?action=check.webhook');

}

function editWebhook(event, url) {

	$.get('/admin/webhook.php?action=edit_do&title=Conference&event=' + event + '&url=' + url, function (data) {

		yNotifyMe("CRM. Результат," + data.result + ",signal.png");
		checkWebhook();

	}, 'json');

}

/**
 * Центрирование элементов в окне
 * @returns {jQuery}
 */
$.fn.center = function () {

	let w = $(window);

	this.css("position", "absolute");

	if (!isMobile || $(window).width() > 500) {

		this.css("top", (w.height() - this.height()) / 2 + "px");
		this.css("left", (w.width() - this.width()) / 2 + w.scrollLeft() + "px");

	} else {

		this.css("top", "0px");
		this.css("left", "0px");

	}

	return this;

};

/**
 * Автоматическое увеичение размера текстового поля
 * @param maxHeight - максимальая высота поля
 * @param rows - количество строк при инициализации
 * @returns {$}
 */
$.fn.autoHeight = function (maxHeight, rows) {

	if (rows === 'undefined') rows = 1;

	this.trigger('input');

	this.each(function () {

		$(this).attr('rows', rows);
		resize($(this));

		$('#dialog').center();

	});

	this.off('input');
	this.on('input', function () {

		resize($(this));

		$('#dialog').center();

	});

	function resize($text) {

		$text.css({'min-height': '100px', 'height': $text[0].scrollHeight + 'px', 'overflow-y': 'hidden'});
		//if($text[0].scrollHeight > maxHeight) $text.css({'height': (maxHeight) + 'px', 'overflow-y':'auto'});

		$('#dialog').center();

	}

	return this;

};

/**
 * Проверка обязательных полей перед отправкой
 * включая чекбосы и радио-кнопки
 * .required - input, select, textarea
 * .multireq - блок, который оборачивает multiselect
 * .req      - блок, который оборачивает группу radio, checkbox
 * эти блоки будут подсвечиваться как обязательные + в них будут искаться элементы
 * которые должны быть заполнены
 * РАБОТАЕТ
 */
function checkRequired(forma) {

	var $req1, $req2, $req3;
	var $block = $('#dialog');

	//если диалоговое окно открыто
	//то ищем id формы, т.к. полюбому мы проверяем заполненные поля в ней
	if ($block.is(':visible'))
		forma = $block.find('form').attr('id');

	if (forma && forma !== 'undefined') {

		var $form = $('#' + forma);

		$req1 = $form.find(".required");
		$req2 = $form.find(".req").not('.ydropDown.like-input');//.not('.like-input');
		$req3 = $form.find(".multireq");

	} else {

		$req1 = $(".required");
		$req2 = $(".req").not('.ydropDown.like-input');//.not('.like-input');
		$req3 = $(".multireq");

	}


	var em = 0;

	/*
	Проходим обычные поля: input, select, textarea
	*/
	$req1.removeClass("empty").css({"color": "inherit", "background": "#FFF"});
	$req1.each(function () {

		var $val = $(this).val();

		if ($val === '') {

			$(this).addClass("empty").css({"color": "#222", "background": "#FFE3D7"});
			em++;

		}

	});

	/*
	Проходим поля выбора: radio, checkbox
	*/
	$req2.removeClass("warning");
	$req2.each(function () {

		var value = $(this).find('input:checked').val();

		//кол-во выбранных элементов
		//var countSel = $('#' + $id + ':checked').length;

		if (value === 'undefined' || value === undefined) {

			$(this).addClass('warning');
			em++;

		}

	});

	/*
	Проходим все поля с опцией multiselect
	*/
	$req3.removeClass("warning");
	$req3.each(function () {

		var $select = $(this).find('select');

		//кол-во выбранных элементов
		var countSel = $select.val().length;

		if (countSel === 0) {

			$(this).addClass('warning');
			em++;

		} else $(this).removeClass('warning');

	});

	if (em > 0) {

		Swal.fire({
			title: "Ошибка",
			text: "Не заполнено " + em + " обязательных полей\n\rОни выделены цветом",
			type: "error"
		});

		$('#message').fadeTo(1, 0).css('display', 'none');

		return false;

	} else return true;

}

/**
 * Открытие url в модальном окне. Используется для вызова форм
 * @param url
 * @returns {boolean}
 */
function adoLoad(url) {

	let $dialog = $('#dialog');
	let $resultdiv = $('#resultdiv');
	let $container = $('#dialog_container');
	let $preloader = $('.dialog-preloader');

	$container.css('height', $(window).height());
	$dialog.css('width', '500px').css('height', 'unset').css('display', 'none');
	$container.css('display', 'block');
	$preloader.center().css('display', 'block');

	$.ajax({
		type: "GET",
		url: url,
		success: function (data) {

			$resultdiv.empty().html(data);

			$dialog.find("a.button:contains('Отмена')").addClass('bcancel').prepend('<i class="icon-cancel-circled"></i>');
			$dialog.find("a.button:contains('Закрыть')").addClass('bcancel').prepend('<i class="icon-cancel-circled"></i>');
			$dialog.find("a.button:contains('Сохранить')").prepend('<i class="icon-ok"></i>');
			$dialog.find("a.button:contains('Добавить')").prepend('<i class="icon-plus-circled-1"></i>');

			$preloader.css('display', 'none');

			$resultdiv.find('select').not('.multiselect').each(function () {
				$(this).wrap("<span class='select'></span>");
			});

			//console.log(isMobile);

			if (isMobile || $(window).width() < 500) {

				//$dialog.find('form').find('#formtabs').append('<div style="height: 200px" class="block wp100">&nbsp;</div>');

				$dialog.css({
					'position': 'unset',
					'margin': '0 auto',
					'margin-bottom': '50px',
					'width': '100vw',
					'height': '100vh'
				});
				$container.css('overflow-y', 'auto');

				$dialog.on('focus', 'input', function () {

					$('#formtabs').scrollTo($(this), 500);

				});
				$dialog.on('focus', 'textarea', function () {

					$('#formtabs').scrollTo($(this), 500);

				});

				$('input.datum').each(function () {
					this.setAttribute('type', 'date');
				});
				$('input.inputdate').each(function () {
					this.setAttribute('type', 'date');
				});
				$('input.inputdatetime').each(function () {
					this.setAttribute('type', 'datetime-local');
				});


			}
			if (!isMobile) {

				$('input[type="date"]').each(function () {
					this.setAttribute('type', 'text');
				});
				$('input[type="time"]').each(function () {
					this.setAttribute('type', 'text');
				});
				$('input[type="datetime"]').each(function () {
					this.setAttribute('type', 'text');
				});

			}

			if (typeof doLoadCallback === 'function') doLoadCallback();

			$dialog.css('display', 'block').center();

		},
		statusCode: {
			404: function () {
				new DClose();
				Swal.fire({
					title: "Ошибка 404: Страница не найдена!",
					type: "warning"
				});
			},
			500: function () {
				new DClose();
				Swal.fire({
					title: "Ошибка 500: Ошибка сервера!",
					type: "error"
				});
			}
		}
	});

	$('body').css({"overflow": "hidden"});

	return false;

}

/**
 * Закрытие модального окна
 * @constructor
 */
function aDClose() {

	$('#resultdiv').empty();
	$('#dialog_container').css('display', 'none');
	$('.dialog-preloader').css('display', 'none');
	$('#dialog').css({
		'display': 'none',
		'width': '500px',
		'height': 'unset',
		'position': 'absolute',
		'margin': 'unset'
	}).center();

	$('body').css({"overflow-y": "auto"});
	$('#ui-datepicker-div').remove();

}

/**
 * Подключение js-файла
 * @param path
 * @returns {boolean}
 */
function includeJS(path) {

	for (let i = 0; i < javascripts.length; i++) {
		if (path === javascripts[i]) {
			return false;
		}
	}

	javascripts.push(path);
	$.ajax({
		url: path,
		dataType: "script",// при типе script, JS сам инклюдится и воспроизводится
		async: false
	});

}

function yNotifyMe(data) {

	if (("Notification" in window)) {

		data = data.split(",");

		let title = data[0];
		let content = data[1];
		let img = data[2];
		let id = data[3];
		let url = data[4];

		if (Notification.permission === "granted") {
			var notification = new Notification(title, {
				lang: 'ru-RU',
				body: content,
				icon: '../../../images/' + img,
				tag: id
			});
		}
		// В противном случае, мы должны спросить у пользователя разрешение
		else if (Notification.permission === 'default') {
			Notification.requestPermission(function (permission) {

				// Не зависимо от ответа, сохраняем его в настройках
				if (!('permission' in Notification)) {
					Notification.permission = permission;
				}
				// Если разрешение получено, то создадим уведомление
				if (permission === "granted") {
					let notification = new Notification(title, {
						lang: 'ru-RU',
						body: content,
						icon: '../../../images/' + img,
						tag: id
					});
				}
			});
		}

		notification.onshow = function () {
			let wpmupsnd = new Audio("../../../images/mp3/bigbox.mp3");
			wpmupsnd.volume = 0.2;
			wpmupsnd.play();
		};
		notification.onclick = function () {

		};

	}
}

function openDogovor(id, hash) {

	let str = '';

	if (!hash)
		str = '';

	else if (hash !== "undefined") str = '#' + hash;

	window.open('/card.deal.php?did=' + id + str);


	return false;

}