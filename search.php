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

		if (is_fighting($db, $player->id)) {
			redirect("combat.php");
			die();
		}

		$place_loot_table_id = $db->querySingle("SELECT loot_table_id FROM place WHERE id == " . $player->place_id);

		$found = run_loot_table($db, $place_loot_table_id);

		foreach ($found as $item_id) {
			$item = new Item($db, $item_id);
			echo "<p>Vous avez trouvé :";
			$item->echo();
			echo "</p>";
			add_item($db, $player, $item->id);
		}

		if (count($found) == 0) {
			echo "<p>Vous n'avez rien trouvé</p>";
		}

		if (check_encounter($db, $player->place_id,  false)) {
			$encounter_table_id = $db->querySingle("SELECT ennemy_table_id FROM place WHERE id == " . $player->place_id);
			$encounter = roll_encounter($db, $encounter_table_id);
			if (!is_null($encounter)) {
				$name = $db->querySingle("SELECT name FROM ennemy WHERE id == " . $encounter["ennemy_id"]);
				echo "<p>" . $name . " vous attaque !</p>";
				$combat_id = get_new_combat_id($db);
				create_combat($db, $combat_id);
				create_fighter($db, $player->id, $combat_id);
				instantiate_ennemy($db, $encounter["ennemy_id"], $encounter["level"], $combat_id);
				$combat = new Combat($db, $player->id);
				$combat->pick_first_turn();
				$combat->commit($db);
			}
		}

		echo "</br><a href='place.php'>Ok</a>";

		$db->close();
	?>

</body>
</html>