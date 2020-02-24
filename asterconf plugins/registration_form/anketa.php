<?php
if ($_REQUEST['clid'] == '' || $_REQUEST['did'] == '' ) {
	print "Вы перешли по некорректной ссылке! <br> Свяжитесь с нами!";
	exit();	
} 

?>



<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Document</title>
	
	<script src="https://code.jquery.com/jquery-3.4.1.js"></script>
	<script type="text/javascript">
$(document).ready(function(){

    var i =  1;

    $('#addPers').click(function() {
        $('<div class="cloned-pers"><H3>Данные участника:</H3><p><input name="name_'+i+'" type="text" value="" placeholder="Имя участника" required></p><p><input name="mail_'+i+'" type="email" value="" placeholder="Email участника" required></p><p><input name="phone_'+i+'" type="tel" value="" placeholder="Тел. участника" required></p><div><p><H3>Участие: </H3><br><input type="radio" name="participation_'+i+'" value="dall" checked> Оба дня<input type="radio" name="participation_'+i+'" value="d1"> День 1<input type="radio" name="participation_'+i+'" value="d2"> День 2	<input type="radio" name="participation_'+i+'" value="online"> Онлайн</p><p><H3>Бизнес-ланч: </H3><br><input type="radio" name="lunch_'+i+'" value="d1"> День 1<input type="radio" name="lunch_'+i+'" value="d2"> День 2<input type="radio" name="lunch_'+i+'" value="dall"> Оба дня<input type="radio" name="lunch_'+i+'" value="no" checked> Нет</p><p><H3>Банкет на второй день: </H3><br><input type="radio" name="banket_'+i+'" value="yes"> Да<input type="radio" name="banket_'+i+'" value="no" checked> Нет	</p></div><div><label><input type="checkbox" name="yhotel_'+i+'" value="yes" onchange="showRoom(this);"/> Заказать гостиницу</label><div style="display:none;"><p>Въезд: <input name="arrival_'+i+'" type="date" min="2019-11-01"> - Выезд: <input name="departure_'+i+'" type="date" min="2019-11-01"></p><p><input type="radio" name="room_'+i+'" value="single"> Одноместный<input type="radio" name="room_'+i+'" value="double" checked> Двухместный</p></div><br>----------------------------------------------------<br></div>').fadeIn(300).appendTo('#alldata');
        i++;
        if (i>1){
        	$('#removePers').show();
        }
    });

    $('#removePers').click(function() {
    if(i > 1) {
        $('.cloned-pers:last').remove();
        i--;
        if (i == 1) {
   		 	$('#removePers').hide();
    	}
    } 
    });


    // here's our click function for when the forms submitted

   /* $('.submit').click(function(){

	    var answers = [];
	    $.each($('.field'), function() {
	        answers.push($(this).val());
	    });

	    if(answers.length == 0) {
	        answers = "none";
	    }   

	    alert(answers);

	    return false;

    });*/


});



		/* Скрытие / открытие полей заказа номера */
		function showRoom(num) {
			var divperson = $(num).parent().parent();
			
			if ($(num).is(':checked')) {	
				$(divperson).find("div").show();
			} else {
				$(divperson).find("div").hide();
			}
		}
	</script>
</head>
<body>
	

	<H1>Анкета клиента</H1>
	<form action="formhandler.php" method="post">
		<div id="alldata" name="alldata">
			<?php
				//echo '<pre>'.print_r($_REQUEST,true).'</pre>';
				if ($_REQUEST['recv']['castType'] == 'client') {
					echo '
						<div>
						<H3>Проверьте ревизиты Вашей компании:</H3>
						
							<p><input name="castUrName" type="text" value="'.$_REQUEST['recv']['castUrName'].'" placeholder="Название компании" required> </p>
							<p><input name="castInn" type="text" value="'.$_REQUEST['recv']['castInn'].'" placeholder="ИНН компании" required> </p>
							<p><input name="castKpp" type="text" value="'.$_REQUEST['recv']['castKpp'].'" placeholder="КПП компании" required> </p>
							<p><input name="castBank" type="text" value="'.$_REQUEST['recv']['castBank'].'" placeholder="Название Банка" required> </p>
							<p><input name="castBankBik" type="text" value="'.$_REQUEST['recv']['castBankBik'].'" placeholder="БИК Банка" required> </p>
							<p><input name="castBankRs" type="text" value="'.$_REQUEST['recv']['castBankRs'].'" placeholder="Расч/счет" required> </p>
							<p><input name="castBankKs" type="text" value="'.$_REQUEST['recv']['castBankKs'].'" placeholder="Кор/счет" required> </p>
							<p><input name="castDirName" type="text" value="'.$_REQUEST['recv']['castDirName'].'" placeholder="ФИО руководителя" required> </p>
							<p><input name="castDirStatus" type="text" value="'.$_REQUEST['recv']['castDirStatus'].'" placeholder="Должность руководителя" required> </p>
							<p><input name="castUrAddr" type="text" value="'.$_REQUEST['recv']['castUrAddr'].'" placeholder="Юридический адрес" required> </p>
						
						</div>
					';
				} else { //Если клиент - физик, отобразим способы оплаты
					echo '
						<div>
						<H3>Выберите способ оплаты:</H3>
						
						<select name = "payment">
							<option value = "sberbank" selected>Карта Сбербанк</option>
							<option value = "tinkoff">Тинькофф</option>
							<option value = "otkritie">Открытие</option> 
							<option value = "roketbank">Рокетбанк</option>
							<option value = "yandex">Яндекс-деньги</option> 
							<option value = "webmoney">WebMoney</option>
						</select>
						
						</div>
					';
				}
				echo '<input name="clid" type="hidden" value="'.$_REQUEST['clid'].'">
				<input name="did" type="hidden" value="'.$_REQUEST['did'].'">
				
			<div>
				<H3>Данные участника:</H3>
				<p><input name="name_0" type="text" value="'.$_REQUEST['pname'].'" placeholder="Имя участника" required></p>
				<!--<p><input name="surname" type="text" placeholder="Фамилия участника" required></p>-->
				<p><input name="mail_0" type="email" value="'.$_REQUEST['pmail'].'" placeholder="Email участника" required></p>
				<p><input name="phone_0" type="tel" value="'.$_REQUEST['ptel'].'" placeholder="Тел. участника" required></p>';
				//unset($_REQUEST);
			?>	
				<div name="reservation">
					<p><H3>Участие: </H3><br>
					<input type="radio" name="participation_0" value="dall" checked> Оба дня
					<input type="radio" name="participation_0" value="d1"> День 1
				   	<input type="radio" name="participation_0" value="d2"> День 2
					<input type="radio" name="participation_0" value="online"> Онлайн
				   	</p>
					<p><H3>Бизнес-ланч: </H3><br>
					<input type="radio" name="lunch_0" value="d1"> День 1
				   	<input type="radio" name="lunch_0" value="d2"> День 2
				   	<input type="radio" name="lunch_0" value="dall"> Оба дня
				   	<input type="radio" name="lunch_0" value="no" checked> Нет
				   	</p>
				   	<p><H3>Банкет на второй день: </H3><br>
					<input type="radio" name="banket_0" value="yes"> Да
				   	<input type="radio" name="banket_0" value="no" checked> Нет
				   	</p>
				</div>
				<div>
				<label><input type="checkbox" name="yhotel_0" value="yes" onchange="showRoom(this);"/> Заказать гостиницу</label>
				
				<div class="is-hotel" style="display:none;">
					<!--<p>Количество ночей: <input name="nights_0" type="number" value="1" min="1" max="99"></p> -->
					<p>Въезд: <input name="arrival_0" type="date" min="2019-11-01"> - Выезд: <input name="departure_0" type="date" min="2019-11-01"></p>
					<p><input type="radio" name="room_0" value="single"> Одноместный
				   	<input type="radio" name="room_0" value="double" checked> Двухместный</p>
				</div>
				<br>----------------------------------------------------<br>
				</div>
			</div>
			
		</div>
		
		<input type="submit" value="Отправить">
	</form>
	<button id="addPers">Добавить участника</button>
	<button id="removePers" style="display:none;">Удалить участника</button>
</body>
</html>