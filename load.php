<html>
<head>
	<title>PHP</title>
	<meta charset="UTF-8">
</head>
<body>

	<?php

		include("core.php");

		setcookie("name", $_POST["name"]);
		setcookie("password", $_POST["password"]);

		redirect("place.php");

	?>

</body>
</html>