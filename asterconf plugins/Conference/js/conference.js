/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*      SalesMan Project        */
/*        www.isaler.ru         */
/*          ver. 2019.2         */
/* ============================ */

/**
 * Скрипт добавляет доп.пункт в меню для Экспорта списков конференции с фильтрами
 */

$(document).ready(function() {

	let $menu = '' +
		'<li class="hidden-iphone" data-type="deal">' +
		'  <a href="javascript:void(0)" class="navlink" onclick="asterExport();">' +
		'    <span><i class="icon-asterisk"></i></span>' +
		'    <span class="">Выгрузки для Asterconf</span>' +
		'  </a>' +
		'</li>';

	$('ul#menudeals').append($menu);

});

function asterExport(){

	doLoad("/plugins/Conference/php/export.php?action=export");

}