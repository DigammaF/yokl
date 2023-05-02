<html>
<head>
	<title>PHP</title>
	<meta charset="UTF-8">
</head>
<body>

	<?php

		include("core.php");

		if ($_POST["strength"] + $_POST["speed"] > 10) {
			echo "Trop de force et de vitesse !";
			die();
		}

		$db = new SQLite3("all.db");
		setcookie("name", $_POST["name"]);
		setcookie("password", $_POST["password"]);
		$inv_id = new_inventory($db);
		$player_id = $db->querySingle("SELECT MAX(id) FROM pawn") + 1;
		$params = array(
			$player_id,
			escape($_POST["name"]), // name
			1, // place id
			1, // level
			$_POST["strength"], // strength
			$_POST["speed"], // speed
			maxhealth_per_strength($_POST["strength"]), // health
			maxhealth_per_strength($_POST["strength"]), // maxhealth
			0, // exp
			maxexp_per_level(1), // maxexp
			0, // armor_id
			0, // weapon_id
			$inv_id, // inventory_id
			escape(hash("sha256", $_POST["password"])), // password_hash
			0,
		);
		$param_str = join(",", $params);
		$db->exec("INSERT INTO pawn VALUES (" . $param_str . ")");
		$db->close();
		redirect("place.php");

	?>

</body>
</html>