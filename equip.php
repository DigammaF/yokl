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

		// check if equip is legal (player has item in inventory and is not already equiped and item is equipable)
		if (!player_has_item($db, $player, $_GET["item_id"])) {
			abort();
		}

		$item = new Item($db, $_GET["item_id"]);

		if (!$item->valid || !$item->equipable()) {
			abort();
		}

		// check player isn't already equiped and equip
		switch ($item->type) {
			case "weapon":
				if ($player->weapon_id > 0) {
					abort();
				} else {
					$db->exec("UPDATE pawn SET weapon_id = " . $item->id . " WHERE id == " . $player->id);
					remove_item($db, $player, $item->id);
				}
				break;
			case "armor":
				if ($player->armor_id > 0) {
					abort();
				} else {
					$db->exec("UPDATE pawn SET armor_id = " . $item->id . " WHERE id == " . $player->id);
					remove_item($db, $player, $item->id);
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