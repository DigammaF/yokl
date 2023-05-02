<html>
<head>
	<title>PHP</title>
	<meta charset="UTF-8">
</head>
<body>

	<?php

		include("core.php");

		// control: player|ai

		$db = new SQLite3("all.db");
		$player = new Pawn($db, authenticate($db));

		function abort() {
			redirect("place.php");
			die();
		}

		function get(string $key) {
			if (array_key_exists($key, $_GET)) {
				return $_GET[$key];
			} else if ($key == "mode") {
				return "show";
			} else {
				abort();
			}
		}

		$combat = new Combat($db, $player->id);

		if (!$combat->valid) {
			abort();
		}

		if ($combat->cancelled()) {
			echo "<p>" . $combat->cancel . "</p>";
			param_link("place.php", array(), "Ok");
			$combat->end($db);
			die();
		}

		$player_fighter = $combat->get_fighter($player->id);
		$other_fighter = $combat->get_other_fighter($player->id);

		// mode=show
		// mode=move
			// type
				// speed_up (amount)
				// discover
				// push (amount)
				// evade (amount)
				// attack
				// use_item (item_id, interaction_id)

			// give turn to other player (or have ai play)
		
		if ($player_fighter->data["turn"] && get("mode") == "move") {
			switch (get("type")) {
				case "speed_up":
					combat_speed_up($player_fighter, get("amount"));
					break;
				case "discover":
					combat_discover($player_fighter, $other_fighter);
					break;
				case "push":
					combat_push($player_fighter, $other_fighter,  get("amount"));
					break;
				case "evade":
					combat_evade($player_fighter, get("amount"));
					break;
				case "attack":
					combat_attack($player_fighter, $other_fighter);
					break;
				case "use_item":
					combat_use_item($db, $player_fighter, get("item_id"), get("interaction_id"));
					break;
				default:
					abort();
					break;
			}
			$player_fighter->set("turn", false);
			$other_fighter->set("turn", true);
			if (!is_null($combat->winner())) {
				echo $combat->loser()->name + " est vaincu !";
				$combat->end($db);
				echo "<a href='place.php'>Ok</a>";
				die();
			}
		}
		if ($other_fighter->data["turn"] && $other_fighter->data["control"] == "ai") {
			// ai plays --------------------------------
			$actions = array(
				[
					"probability" => 0.2,
					"script" => function (Fighter $player_fighter, Fighter $other_fighter) {
						combat_speed_up($other_fighter, rand(1, 2));
					}
				],
				[
					"probability" => 0.2,
					"script" => function (Fighter $player_fighter, Fighter $other_fighter) {
						combat_discover($other_fighter, $player_fighter);
					}
				],
				[
					"probability" => 0.2,
					"script" => function (Fighter $player_fighter, Fighter $other_fighter) {
						combat_push($other_fighter, $player_fighter, 1);
					}
				],
				[
					"probability" => 0.2,
					"script" => function (Fighter $player_fighter, Fighter $other_fighter) {
						combat_evade($other_fighter, rand(1, 2));
					}
				],
				[
					"probability" => 0.2,
					"script" => function (Fighter $player_fighter, Fighter $other_fighter) {
						combat_attack($other_fighter, $player_fighter);
					}
				]
			);
			$psum = 0;
			$roll = random_float();
			foreach ($actions as $action) {
				$psum += $action["probability"];
				if ($psum > $roll) {
					$action["script"]($player_fighter, $other_fighter);
					break;
				}
			}
			// -----------------------------------------
			$player_fighter->set("turn", true);
			$other_fighter->set("turn", false);
			if (!is_null($combat->winner())) {
				echo $combat->loser()->name + " est vaincu !";
				$combat->end($db);
				echo "<a href='place.php'>Ok</a>";
				die();
			}
		}

		// show combat interface
		$combat->a->echo($db);
		$combat->b->echo($db);

		// if it's player's turn, add action interface
		if ($player_fighter->data["turn"]) {
			table_start(array("Action", "Amplitude", ""));
			echo_row(array(
				"Prendre de la vitesse",
				param_link("combat.php", array("mode" => "move", "type" => "speed_up", "amount" => 1), "un peu"),
				param_link("combat.php", array("mode" => "move", "type" => "speed_up", "amount" => 2), "beaucoup")
			));
			echo_row(array(
				param_link("combat.php", array("mode" => "move", "type" => "discover"), "Créer une opportunité"),
				"",
				""
			));
			echo_row(array(
				param_link("combat.php", array("mode" => "move", "type" => "push", "amount" => 1), "Construire l'attaque"),
				"",
				""
			));
			echo_row(array(
				"Esquiver",
				param_link("combat.php", array("mode" => "move", "type" => "evade", "amount" => 1), "lentement"),
				param_link("combat.php", array("mode" => "move", "type" => "evade", "amount" => 2), "rapidement")
			));
			echo_row(array(
				param_link("combat.php", array("mode" => "move", "type" => "attack"), "Attaquer"),
				"",
				""
			));
			table_end();
			echo_inventory($db, $player_fighter->data["inventory_id"], "owner", true);
		}

		$combat->commit($db);
		$db->close();
	?>

</body>
</html>