$(document).ready(function(){ //Для исполнения из <head></head>
	$('.nav a[href^="#"]').click(function() { //По клику на все a href с классом nav, начинающиеся с #
		offset = $('.nav').innerHeight() + 10; //высота нашего меню
		var target = $(this).attr('href'); //Получаем id блока, к которому скроллим
		$('html, body').animate({
			scrollTop: $(target).offset().top - offset //Скроллим к началу блока - высота меню
		}, 500); //За какое время
		$('.nav a[href^="#"]').removeClass('active'); //Удаляем у всех ссылок меню класс active
		$(this).addClass('active'); //Добавляем класс active к нажатой ссылке
		return false;
	});

});

