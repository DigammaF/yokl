<html>
<head>
	<title>PHP</title>
	<meta charset="UTF-8">
</head>
<body>

	<?php

		include("core.php");
		include("item_scripts/item_scripts.php");

		$db = new SQLite3("all.db");
		$player = new Pawn($db, authenticate($db));

		function abort() {
			redirect("place.php");
			die();
		}

		// check if using is legal (player possesses the item and the item is usable)
		$item = new Item($db, $_GET["item_id"]);

		if (!$item->valid || !$item->usable() || !player_has_item($db, $player, $item->id) || is_fighting($db, $player->id)) {
			abort();
		}

		// check that the interaction is bound with the item
		$id = $db->querySingle("SELECT item_interaction_id FROM item_interaction_bind WHERE item_interaction_id == " . $_GET["use_id"] . " AND item_id == " . $item->id);

		if (is_null($id)) {
			abort();
		}

		run_script($id, new ItemScriptContext($db, $player, $item));
		
		// redirect
		redirect("place.php");

		$db->close();
	?>

</body>
</html>