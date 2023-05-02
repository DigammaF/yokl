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

		$item = new Item($db, $_GET["item_id"]);

		if (!$item->valid) {
			abort();
		}

		// check if player has the item
		if (!player_has_item($db, $player, $item->id)) {
			abort();
		}

		$ground_inventory_id = $db->querySingle("SELECT ground_inventory_id FROM place WHERE id == " . $player->place_id);
		rem_item_inv($db, $player->inventory_id, $item->id);
		add_item_inv($db, $ground_inventory_id, $item->id);

		redirect("place.php");

		$db->close();
	?>

</body>
</html>