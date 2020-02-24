<!doctype html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<title>Розыгрыш призов</title>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
	
	<style>
        #winner{
		    position:absolute;
		}
    </style>
	

</head>
<body>
	
	<form action="" method="post">
		<p><select name="day">
			<option disabled selected>Выберите день</option>
			<option value="day1">Курс молодого бойца</option>
			<option value="day2">Продвинутый курс</option>
		</select></p>
		<p><input type="submit" value="Определить победителя"></p>
	</form>

	
<?php
	require_once "loto.php";	
?>

<center><img src="../img/299.gif" id="loader"></center>
<script>

	function fillDiv(div, proportional) {

	  var div=$('#winner')
	  var currentHeight = div.outerHeight();
	  var currentWidth = div.outerWidth()+3;
	  var availableHeight = window.innerHeight;
	  var availableWidth = window.innerWidth;

	  var scale = availableWidth / currentWidth;
	  setTimeout(function(){
	  	$('#loader').hide();
	  	div.css({
		    "left": "0px",
		    "top": "200px",
		    "transform": "scale3d("  + scale + ", " + scale + ", 1)",
		    "transform-origin": "0 0",
		    "display": "block"
		  });
	  },1000);
	  
	}

	fillDiv();
	$( window ).resize(fillDiv)
</script>	
</body>
</html>