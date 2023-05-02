<html>
<head>
	<title>PHP</title>
	<meta charset="UTF-8">
</head>
<body>

	<?php

		include("core.php");

		$db = new SQLite3("all.db");
		$player = new Pawn($db, authenticate($db));

		function abort() {
			redirect("place.php");
			die();
		}

		// check if unequip is legal (player is equiped with the item)
		$item = new Item($db, $_GET["item_id"]);

		if (!$item->valid) {
			abort();
		}

		// end checking and unequip
		switch ($item->type) {
			case "weapon":
				if ($player->weapon_id != $item->id) {
					abort();
				} else {
					$db->exec("UPDATE pawn SET weapon_id = 0 WHERE id == " . $player->id);
					add_item($db, $player, $item->id);
				}
				break;
			case "armor":
				if ($player->armor_id != $item->id) {
					abort();
				} else {
					$db->exec("UPDATE pawn SET armor_id = 0 WHERE id == " . $player->id);
					add_item($db, $player, $item->id);
				}
				break;
			default:
				abort();
		}

		// redirect
		redirect("place.php");

		$db->close();
	?>

</body>
</html>