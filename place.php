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

		// print current place

		$description = $db->querySingle("SELECT description FROM place WHERE id == " . $player->place_id);
		echo $description;

		// print search button
		echo "</br><a href='search.php'>Chercher aux alentours</a>";

		// print self

		table_start(array("", $player->name));
		echo_row(array("Vie", $player->health . "/" . $player->maxhealth));
		echo_row(array("Force", $player->strength));
		echo_row(array("Vitesse", $player->speed));
		echo_row(array("Niveau", $player->level));
		echo_row(array("Exp", $player->exp . "/" . $player->maxexp));
		table_end();

			// print equiped
			table_start(array("Equipement", "", "Action"));
			if ($player->weapon->valid) {
				echo_row(array(
					"Arme",
					$player->weapon->describe_equipment(),
					param_link("unequip.php", array("item_id" => $player->weapon_id), "Déséquipper")
				));
			}
			if ($player->armor->valid) {
				echo_row(array(
					"Armure",
					$player->armor->describe_equipment(),
					param_link("unequip.php", array("item_id" => $player->armor_id), "Déséquipper")
				));
			}
			table_end();

			// print inventory
			echo_inventory($db, $player->inventory_id, "owner");

		// print available directions
		$outgoing_links = $db->query("SELECT source_id,destination_id,direction FROM link WHERE source_id == " . $player->place_id);
		table_start(array("Chemin vers ...", "Direction", "Action"));
		while ($link = $outgoing_links->fetchArray()) {
			$name = $db->querySingle("SELECT name FROM place WHERE id == " . $link["destination_id"]);
			echo_row(array($name, $link["direction"], param_link("travel.php", array("destination_id" => $link["destination_id"]), "voyager")));
		}
		table_end();

		// print what's on the ground
		$ground_inventory_id = $db->querySingle("SELECT ground_inventory_id FROM place WHERE id == " . $player->place_id);
		echo "</br>Ce qu'il y a sur le sol ici :";
		echo_inventory($db, $ground_inventory_id, "pickupable");

		$db->close();
	?>

</body>
</html>