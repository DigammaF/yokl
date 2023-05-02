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

		// check travel is legal (making sure there is a link)
		$link = $db->querySingle("SELECT source_id,destination_id FROM link WHERE source_id == " . $player->place_id . " AND destination_id == " . $_GET["destination_id"], true);

		if (count($link) == 0 || is_fighting($db, $player->id)) {
			redirect("place.php");
			die();
		}

		// travel
		$player->set($db, "place_id", $link["destination_id"]);

		if (check_encounter($db, $link["destination_id"],  true)) {
			$encounter_table_id = $db->querySingle("SELECT ennemy_table_id FROM place WHERE id == " . $link["destination_id"]);
			$encounter = roll_encounter($db, $encounter_table_id);
			if (!is_null($encounter)) {
				$combat_id = get_new_combat_id($db);
				create_fighter($db, $player->id, $combat_id);
				instantiate_ennemy($db, $encounter["ennemy_id"], $encounter["level"], $combat_id);
				$combat = new Combat($db, $player->id);
				$combat->pick_first_turn();
				$combat->commit($db);
			}
		}

		// redirect
		redirect("place.php");

		$db->close();
	?>

</body>
</html>