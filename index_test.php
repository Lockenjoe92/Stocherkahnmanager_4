<html>
	<head>
		<title>Testseite</title>
	</head>
	<body>
		<?php
		if($_GET['action']=="hallo")
			echo "Ebenfalls Hallo! :)";
		else
			echo "Langweilige Testseite ohne Inhalte..."
		?>
	</body>
</html>