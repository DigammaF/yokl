<?php

	// TODO
	// combat system
	// level system
	// craft system (wood/iron/leather/glass/wax/gold$/silver$/bronze$)
	// chat system
	// global market
	// ascii art
	// css + colors
	// security

	include("item_scripts/item_scripts.php");

	function clamp(float $v, float $low, float $high) {
		return max(min($v, $high), $low);
	}

	function random_float() {
		// returns a random real comprised in [0;1]
		return rand()/getrandmax();
	}

	function redirect(string $page) {
		echo "<script> window.location.replace(\"" . $page . "\"); </script>";
	}

	class Pawn {
		public int $id;
		public string $name;
		public int $place_id;
		public int $level;
		public int $strength;
		public int $speed;
		public int $health;
		public int $maxhealth;
		public int $exp;
		public int $maxexp;
		public int $armor_id;
		public int $weapon_id;
		public int $inventory_id;
		public Item $weapon;
		public Item $armor;

		function __construct(SQLite3 $db, int $id) {
			$record = $db->querySingle("SELECT id,name,place_id,level,strength,speed,health,maxhealth,exp,maxexp,armor_id,weapon_id,inventory_id FROM pawn WHERE id == " . $id, true);
			$this->id = $record["id"];
			$this->name = $record["name"];
			$this->place_id = $record["place_id"];
			$this->level = $record["level"];
			$this->strength = $record["strength"];
			$this->speed = $record["speed"];
			$this->health = $record["health"];
			$this->maxhealth = $record["maxhealth"];
			$this->exp = $record["exp"];
			$this->maxexp = $record["maxexp"];
			$this->armor_id = $record["armor_id"];
			$this->weapon_id = $record["weapon_id"];
			$this->inventory_id = $record["inventory_id"];
			$this->weapon = new Item($db, $record["weapon_id"]);
			$this->armor = new Item($db, $record["armor_id"]);
		}

		function is_dead() {
			return $this->health <= 0;
		}

		function set(SQLite3 $db, string $field, mixed $value) {
			$stmt = $db->prepare("UPDATE pawn SET " . $field . "=:value WHERE id == " . $this->id);
			$stmt->bindValue("value", $value);
			$stmt->execute();
		}
	}

	function escape(string $text, string $a = "'", string $b = "'") {
		return $a . $text . $b;
	}

	function authenticate(SQLite3 $db) {
		$name = $_COOKIE["name"];
		$password_hash = hash("sha256", $_COOKIE["password"]);
		$player_id = $db->querySingle("SELECT id FROM pawn WHERE name == " . escape($name) . " AND password_hash == " . escape($password_hash));
		if (is_bool($player_id) && !$player_id || is_null($player_id)) {
			echo "authentification échouée";
			var_dump($name, $password_hash, $player_id);
			die();
		} else {
			return $player_id;
		}
	}

	class Item {
		public int $id;
		public string|null $type;
		public string $description;
		public int|null $value;
		public string $name;
		public bool $valid;

		function __construct(SQLite3 $db, int $id) {
			$this->id = $id;
			if ($id > 0) {
				$record = $db->querySingle("SELECT id,type,description,value,name FROM item WHERE id == " . $id, true);
				$this->type = $record["type"];
				$this->description = $record["description"];
				$this->value = $record["value"];
				$this->name = $record["name"];
				$this->valid = true;
			} else {
				$this->valid = false;
			}
		}

		function fmt_type() {
			$ITEM_TYPE = [
				"weapon" => "arme",
				"armor" => "armure",
				"usable" => "consommable",
				null => "divers"
			];
			$ITEM_HAS_VALUE = [
				"weapon" => true,
				"armor" => true,
				"usable" => false,
				null => false
			];
			$string = $ITEM_TYPE[$this->type];
			if ($ITEM_HAS_VALUE[$this->type]) {
				$string = $string . escape($this->value, " (+", ")");
			}
			return $string;
		}

		function fmt() {
			$ITEM_TYPE = [
				"weapon" => "arme",
				"armor" => "armure",
				"usable" => "consommable",
				null => "divers"
			];
			$ITEM_HAS_VALUE = [
				"weapon" => true,
				"armor" => true,
				"usable" => false,
				null => false
			];
			$formatted = [
				"header" => $this->name . " : " . $ITEM_TYPE[$this->type] . " ",
				"body" => $this->description
			];
			if ($ITEM_HAS_VALUE[$this->type]) {
				$formatted["header"] = $formatted["header"] . escape($this->value, "(+", ")");
			}
			return $formatted;
		}

		function describe_equipment() {
			if ($this->valid) {
				return $this->name . " (+" . $this->value . ")</br>" . $this->description;
			} else {
				return " x ";
			}
		}

		function echo() {
			if ($this->valid) {
				$fmt = $this->fmt();
				echo "<p>" . $fmt["header"] . "</p>";
				echo escape($fmt["body"], "<p>", "</p>");
			} else {
				echo "<p> x </p>";
			}
		}

		function equipable() {
			return [
				"weapon" => true,
				"armor" => true,
				"usable" => false,
				null => false
			][$this->type];
		}

		function usable() {
			return [
				"weapon" => false,
				"armor" => false,
				"usable" => true,
				null => true
			][$this->type];
		}
	}

	function table_start(array $header) {
		echo "<table>";
		echo_row($header);
	}

	function table_end() {
		echo "</table>";
	}

	function echo_row(array $elements) {
		echo "<tr>";
		foreach ($elements as $element) {
			echo "<th>" . $element . "</th>";
		}
		echo "</tr>";
	}

	function player_has_item(SQLite3 $db, Pawn $player, int $item_id) {
		$item_id = $db->querySingle("SELECT id FROM item WHERE id IN (SELECT item_id FROM inventory WHERE inventory_id == " . $player->inventory_id . ") AND id == " . $item_id);
		return !is_null($item_id);
	}

	function inventory_has_item(SQLite3 $db, int $inventory_id, int $item_id) {
		$item_id = $db->querySingle("SELECT id FROM item WHERE id IN (SELECT item_id FROM inventory WHERE inventory_id == " . $inventory_id . ") AND id == " . $item_id);
		return !is_null($item_id);
	}

	function remove_item(SQLite3 $db, Pawn $player, int $item_id) {
		$db->exec("DELETE FROM inventory WHERE ROWID IN (SELECT ROWID FROM inventory WHERE inventory_id == " . $player->inventory_id . " AND item_id == " . $item_id . " LIMIT 1)");
	}

	function rem_item_inv(SQLite3 $db, int $inventory_id, int $item_id) {
		$db->exec("DELETE FROM inventory WHERE ROWID IN (SELECT ROWID FROM inventory WHERE inventory_id == " . $inventory_id . " AND item_id == " . $item_id . " LIMIT 1)");
	}

	function add_item(SQLite3 $db, Pawn $player, int $item_id) {
		$db->exec("INSERT INTO inventory VALUES " . escape($player->inventory_id . "," . $item_id, "(", ")"));
	}

	function add_item_inv(SQLite3 $db, int $inventory_id, int $item_id) {
		$db->exec("INSERT INTO inventory VALUES " . escape($inventory_id . "," . $item_id, "(", ")"));
	}

	class Fighter {
		public int $id;
		public array $data;
		public bool $changed;
		public string $name;
		public Item $weapon;
		public Item $armor;

		function __construct(SQLite3 $db, int $fighter_id) {
			$this->id = $fighter_id;
			$this->data = $db->querySingle("SELECT id,pawn_id,control,opening,strength,speed,invested_speed,evasion,push,turn,inventory_id,name,weapon_id,armor_id,description,health,level,loot_table_id,exp FROM combat WHERE pawn_id == " . $fighter_id, true);
			$this->changed = false;
			$this->name = $this->data["name"];
			$this->weapon = new Item($db, $this->data["weapon_id"]);
			$this->armor = new Item($db, $this->data["armor_id"]);
		}

		function set(string $field, $value) {
			$this->changed = true;
			$this->data[$field] = $value;
		}

		function get(string $field) {
			return $this->data[$field];
		}

		function commit(SQLite3 $db) {
			if ($this->changed) {
				$this->changed = false;
				$stmt = $db->prepare("UPDATE combat SET id=:id,pawn_id=:pawn_id,control=:control,opening=:opening,strength=:strength,speed=:speed,invested_speed=:invested_speed,evasion=:evasion,push=:push,turn=:turn,inventory_id=:inventory_id,name=:name,description=:description,weapon_id=:weapon_id,armor_id=:armor_id,loot_table_id=:loot_table_id,exp=:exp,health=:health,level=:level WHERE pawn_id == " . $this->id);
				foreach ($this->data as $name => $val) {
					$stmt->bindValue(":".$name, $val);
				}
				$stmt->execute();
			}
		}

		function delete(SQLite3 $db) {
			if ($this->data["control"] == "ai") {
				delete_inventory($db, $this->data["inventory_id"]);
			}
			$db->exec("DELETE FROM combat WHERE pawn_id == " . $this->id);
		}

		function echo($db) {
			table_start(array("", $this->data["name"]));
			echo_row(array("Description", $this->data["description"]));
			echo_row(array("Vie", $this->data["health"]));
			echo_row(array("Force", $this->data["strength"]));
			echo_row(array("Vitesse", $this->data["speed"]));
			echo_row(array("Niveau", $this->data["level"]));
			echo_row(array("Arme", $this->weapon->describe_equipment()));
			echo_row(array("Armure", $this->armor->describe_equipment()));
			if ($this->data["opening"] > 0) {
				echo_row(array("Faille", $this->data["opening"]));
				echo_row(array("Esquive", $this->data["evasion"] . "/" . $this->data["opening"]));
			}
			echo_row(array("Force déployée", $this->data["push"]));
			echo_row(array("Vitesse actuelle", $this->data["invested_speed"]));
			table_end();
		}

		function echo_command() {
			table_start(array("Actions"));
			echo_row(array());
		}

		function is_dead() {
			return $this->data["health"] <= 0;
		}

		function reflect(SQLite3 $db) {
			if ($this->data["pawn_id"] >= 0) {
				$pawn = new Pawn($db, $this->data["pawn_id"]);
				$pawn->set($db, "health", $this->data["health"]);
			}
		}
	}

	function maxhealth_per_strength(int $strength) {
		return $strength * 2;
	}

	function maxexp_per_level(int $level) {
		return $level * 20;
	}

	function ai_exp_earned(int $level) {
		return $level;
	}

	function get_new_combat_id(SQLite3 $db) {
		return $db->querySingle("SELECT MAX(id) FROM combat") + 1;
	}

	function create_fighter(SQLite3 $db, int $pawn_id, int $combat_id) {
		$pawn = new Pawn($db, $pawn_id);
		$id = $combat_id;
		$control = "human";
		$opening = 0;
		$strength = $pawn->strength;
		$speed = $pawn->speed;
		$invested_speed = 0;
		$evasion = 0;
		$push = 0;
		$turn = 0;
		$inventory_id = $pawn->inventory_id;
		$name = $pawn->name;
		$description = "";
		$weapon_id = $pawn->weapon_id;
		$armor_id = $pawn->armor_id;
		$loot_table_id = -1;
		$exp = maxexp_per_level($pawn->level);
		$health = $pawn->health;
		$level = $pawn->level;
		$db->exec("INSERT INTO combat VALUES (" . join(",", array($id, $pawn_id, escape($control), $opening, $strength, $speed, $invested_speed, $evasion, $push, $turn, $inventory_id, escape($name), escape($description), $weapon_id, $armor_id, $loot_table_id, $exp, $health, $level)) . ")");
		return $id;
	}

	function instantiate_ennemy(SQLite3 $db, int $ennemy_id, int $level, int $combat_id) {
		$ennemy = $db->querySingle("SELECT bstrength,bspeed,description,loot_table_id,bexp,inv_table_id,strength_factor,speed_factor,name FROM ennemy WHERE id == " . $ennemy_id, true);
		$id = $combat_id;
		$control = "ai";
		$opening = 0;
		$strength = $ennemy["bstrength"] + $level * $ennemy["strength_factor"];
		$speed = $ennemy["bspeed"] + $level * $ennemy["speed_factor"];
		$invested_speed = 0;
		$evasion = 0;
		$push = 0;
		$turn = 0;
		// create inventory from loot table and pick better weapon and armor
		$inventory_id = new_inventory($db);
		$best_weapon = new Item($db, 0);
		$best_armor = new Item($db, 0);
		foreach (run_loot_table($db, $ennemy["inv_table_id"]) as $item_id) {
			add_item_inv($db, $inventory_id, $item_id);
			$item = new Item($db, $item_id);
			if ($item->type == "armor") {
				if (!$best_armor->valid || $item->value > $best_armor->value) {
					$best_armor = $item;
				}
			}
			if ($item->type == "weapon") {
				if (!$best_weapon->valid || $item->value > $best_weapon->value) {
					$best_weapon = $item;
				}
			}
		}
		// --------------------------------------------------------------------
		$name = $ennemy["name"];
		$description = $ennemy["description"];
		$loot_table_id = $ennemy["loot_table_id"];
		$exp = ai_exp_earned($level);
		$health = maxhealth_per_strength($strength);
		$args = array(
			$id,
			-1,
			escape($control),
			$opening,
			$strength,
			$speed,
			$invested_speed,
			$evasion,
			$push,
			$turn,
			$inventory_id,
			escape($name),
			escape($description),
			$best_weapon->id,
			$best_armor->id,
			$loot_table_id,
			$exp,
			$health,
			$level
		);
		$db->exec("INSERT INTO combat VALUES (" . join(",", $args) . ")");
		return $id;
	}

	function copy_inventory(SQLite3 $db, int $id) {
		$instance_id = new_inventory($db);
		$content = $db->query("SELECT item_id FROM inventory WHERE inventory_id == " . $id);
		while ($item_id = $content->fetchArray()) {
			$db->exec("INSERT INTO inventory VALUES (" . $instance_id . "," . $item_id . ")");
		}
		return $instance_id;
	}

	function delete_inventory(SQLite3 $db, int $id) {
		$db->exec("DELETE FROM inventory WHERE inventory_id == " . $id);
	}

	function combat_speed_up(Fighter $fighter, int $amount) {
		echo "<p>" . $fighter->name . " tente de prendre de la vitesse (+" . $amount . ")</p>";
		if ($fighter->data["speed"] >= $amount && $amount <= 2 && $amount > 0) {
			$fighter->set("speed", $fighter->data["speed"] - $amount);
			$fighter->set("invested_speed", $fighter->data["invested_speed"] + $amount);
			echo "<p>" . $fighter->name . " prends de la vitesse (+" . $amount . ")</p>";
		} else {
			echo "<p>" . $fighter->name . " ne peut pas accélerer autant</p>";
		}
	}

	function combat_discover(Fighter $atk, Fighter $def) {
		echo "<p>" . $atk->name . " tourne autour de " . $def->name . "</p>";
		if ($atk->data["invested_speed"] > $def->data["invested_speed"]) {
			$delta = $atk->data["invested_speed"] - $def->data["invested_speed"];
			$atk->set("invested_speed", 0);
			$def->set("opening", $delta);
			$def->set("evasion", 0);
			$atk->set("push", 0);
			echo "<p>" . $atk->name . " découvre une opportunité d'attaque sur " . $def->name . "</p>";
		} else {
			echo "<p>" . $atk->name . " n'est pas assez rapide par rapport à " . $def->name . "</p>";
		}
	}

	function combat_push(Fighter $atk, Fighter $def, int $amount) {
		echo "<p>" . $atk->name . " construit son attaque contre " . $def->name . " (+" . $amount . ")</p>";
		if ($atk->data["strength"] > $amount && $amount == 1) {
			if ($def->data["opening"] > 0) {
				$atk->set("strength", $atk->data["strength"] - $amount);
				$atk->set("push", min($def->data["opening"], $atk->data["push"] + $amount));
			} else {
				echo "<p>" . $def->name . " ne laisse aucune opportunité d'attaque pour l'instant</p>";
			}
		} else {
			echo "<p>" . $atk->name . " n'a pas assez de force pour cela</p>";
		}
	}

	function combat_evade(Fighter $fighter, int $amount) {
		echo "<p>" . $fighter->name . " amorce une esquive (+" . $amount . ")</p>";
		if ($fighter->data["speed"] > $amount && $amount <= 2 && $amount > 0) {
			$fighter->set("evasion", min($fighter->data["opening"], $fighter->data["evasion"] + $amount));
		} else {
			echo "<p>" . $fighter->name . " n'est pas assez rapide pour cela</p>";
		}
	}

	function combat_attack(Fighter $atk, Fighter $def) {
		echo "<p>" . $atk->name . " lance une attaque contre " . $def->name . "</p>";
		if ($def->data["opening"] > 0) {
			if ($atk->data["push"] > $def->data["evasion"]) {
				$weapon_damage = 0;
				$armor_defense = 0;
				if ($atk->weapon->valid) {
					$weapon_damage = $atk->weapon->value;
				}
				if ($def->armor->valid) {
					$armor_defense = $def->armor->value;
				}
				$damage = max(0, $atk->data["push"] - $def->data["evasion"] + $weapon_damage - $armor_defense);
				echo "<p>" . $atk->name . " inflige " . $damage . " points de dégâts à " . $def->name . "</p>";
				$def->set("health", $def->data["health"] - $damage);
				$def->set("opening", 0);
				$def->set("evasion", 0);
				$atk->set("push", 0);
			} else {
				echo "<p>" . $def->name . " évite l'attaque</p>";
			}
		} else {
			echo "<p>" . $def->name . " ne laisse aucune opportunité d'attaque pour l'instant</p>";
		}
	}

	function combat_use_item(SQlite3 $db, Fighter $fighter, int $item_id, int $interaction_id) {
		// check if using is legal (player possesses the item and the item is usable)
		$item = new Item($db, $item_id);

		if (!$item->valid || !$item->usable() || !inventory_has_item($db, $fighter->data["inventory_id"], $item_id)) {
			redirect("place.php");
			die();
		}

		// check that the interaction is bound with the item
		$id = $db->querySingle("SELECT item_interaction_id FROM item_interaction_bind WHERE item_interaction_id == " . $_GET["interaction_id"] . " AND item_id == " . $item->id);
		$combat_available = $db->querySingle("SELECT combat FROM item_interaction WHERE id == " . $id);

		if (is_null($id) || !$combat_available) {
			redirect("place.php");
			die();
		}

		$verb = $db->querySingle("SELECT verb FROM item_interaction WHERE id == " . $id);
		echo "<p>" . $fighter->name . " " . $verb . " " . $item->name . "</p>";
		run_combat_script($id, new CombatItemScriptContext($db, $fighter, $item));
	}

	function combat_flee(SQLite3 $db, Fighter $fighter) {

	}

	function param_link(string $page, array $values, string $text) {
		$params = array();
		foreach ($values as $key => $value) {
			array_push($params, $key . "=" . $value);
		}
		return "<a href='" . $page . "?" . join("&", $params) . "'>" . $text . "</a>";
	}

	// mode = show|owner|pickupable
	function echo_inventory(SQLite3 $db, int $inventory_id, string $mode = "show", bool $combat = false) {
		$inventory = $db->query("SELECT item_id FROM inventory WHERE inventory_id == " . $inventory_id);
		table_start(array("Objet", "Type", "Description", "Action"));
		while ($item = $inventory->fetchArray()) {
			$item_id = $item["item_id"];
			if ($item_id > 0) {
				$item = new Item($db, $item_id);
				$row = array($item->name, $item->fmt_type(), $item->description, "");
				if ($mode == "owner") {
					if ($item->equipable()) {
						$row[3] = $row[3] . " " . param_link("equip.php", array("item_id" => $item_id), "equipper");
					}
					if ($item->usable()) {
						$use_binds = $db->query("SELECT item_interaction_id,item_id FROM item_interaction_bind WHERE item_id == " . $item->id);
						while ($bind = $use_binds->fetchArray()) {
							$use_type = $db->querySingle("SELECT id,name,combat FROM item_interaction WHERE id == " . $bind["item_interaction_id"], true);
							if (!$combat) {
								$row[3] = $row[3] . " " . param_link(
									"use.php",
									array("item_id" => $item->id, "use_id" => $use_type["id"]),
									$use_type["name"]
								);
							} else if ($combat && $use_type["combat"]) {
								$row[3] = $row[3] . " " . param_link(
									"combat.php",
									array("mode" => "move", "type" => "use_item", "item_id" => $item->id, "interaction_id" => $use_type["id"]),
									$use_type["name"]
								);
							}
						}
					}
					if (!$combat) {
						$row[3] = $row[3] . " " . param_link("drop.php", array("item_id" => $item_id), "lâcher");
					}	
				}
				if ($mode == "pickupable") {
					$row[3] = $row[3] . " " . param_link("pickup.php", array("item_id" => $item_id), "ramasser");
				}
				echo_row($row);
			}
		}
		table_end();
	}

	class Combat {
		public Fighter $a;
		public Fighter $b;
		public int $id;
		public bool $valid;
		public string $cancel;

		function __construct(SQLite3 $db, int $pawn_id) {
			$this->id = $db->querySingle("SELECT id FROM combat WHERE pawn_id == " . $pawn_id);
			if (is_null($this->id)) {
				$this->valid = false;
			} else {
				$this->valid = true;
				$querry = $db->query("SELECT pawn_id FROM combat WHERE id == " . $this->id);
				$fighters = array();
				while ($fighter = $querry->fetchArray()) {
					array_push($fighters, $fighter["pawn_id"]);
				}
				function fighters_cmp($a, $b) {
					return $a->id - $b->id;
				}
				usort($fighters, "fighters_cmp");
				$this->a = new Fighter($db, $fighters[0]);
				$this->b = new Fighter($db, $fighters[1]);
			}
		}

		function cancelled() {
			return !is_null($this->cancel);
		}

		function pick_first_turn() {
			if ($this->a->data["speed"] == $this->b->data["speed"]) {
				if (random_float() > 0.5) {
					$this->a->set("turn", true);
					$this->b->set("turn", false);
				} else {
					$this->a->set("turn", false);
					$this->b->set("turn", true);
				}
			} else if ($this->a->data["speed"] > $this->b->data["speed"]) {
				$this->a->set("turn", true);
				$this->b->set("turn", false);
			} else {
				$this->a->set("turn", false);
				$this->b->set("turn", true);
			}
		}

		function commit(SQLite3 $db) {
			$this->a->commit($db);
			$this->b->commit($db);
		}

		function delete(SQLite3 $db) {
			$this->a->delete($db);
			$this->b->delete($db);
		}

		function get_fighter(int $pawn_id) {
			if ($this->a->data["pawn_id"] == $pawn_id) {
				return $this->a;
			}
			if ($this->b->data["pawn_id"] == $pawn_id) {
				return $this->b;
			}
		}

		function get_other_fighter(int $pawn_id) {
			if ($this->a->data["pawn_id"] == $pawn_id) {
				return $this->b;
			}
			if ($this->b->data["pawn_id"] == $pawn_id) {
				return $this->a;
			}
		}

		function winner() {
			if ($this->a->is_dead()) {
				return $this->b;
			} else if ($this->b->is_dead()) {
				return $this->a;
			} else {
				return null;
			}
		}

		function loser() {
			if ($this->a->is_dead()) {
				return $this->a;
			} else if ($this->b->is_dead()) {
				return $this->b;
			} else {
				return null;
			}
		}

		function end(SQLite3 $db) {
			$winner = $this->winner();
			if (!is_null($winner)) {
				$this->award_victory($db, $winner->id);
			}
			$this->a->reflect($db);
			$this->b->reflect($db);
			$this->a->delete($db);
			$this->b->delete($db);
		}

		function award_victory(SQLite3 $db, int $winner_id) {
			// get other's loot table and roll it to award winner
			$loser = $this->get_other_fighter($winner_id);
			$winner = $this->get_fighter($winner_id);
			if ($loser->data["loot_table_id"] >= 0) {
				$loot = run_loot_table($db, $loser->data["loot_table_id"]);
				foreach ($loot as $item_id) {
					$item = new Item($db, $item_id);
					echo "<p>" . $loser->name . " laisse tomber :";
					$item->echo();
					echo "</p>";
					add_item_inv($db, $winner->data["inventory_id"], $item->id);
				}
			}
		}
	}

	function is_fighting(SQLite3 $db, int $pawn_id) {
		$ans = $db->querySingle("SELECT id FROM combat WHERE pawn_id == " . $pawn_id);
		return !is_null($ans);
	}

	function run_loot_table(SQLite3 $db, int $loot_table_id) {
		$loot_table = $db->query("SELECT probability,item_id FROM loot_table WHERE id == " . $loot_table_id);
		if ($loot_table->numColumns() == 0) { return array(); }
		// the above line explicitly describes what the function does if the table doesn't exist
		// but it isn't required
		$items = array();

		while ($loot = $loot_table->fetchArray()) {
			if ($loot["probability"] > random_float()) {
				array_push($items, $loot["item_id"]);
			}
		}

		return $items;
	}

	function new_inventory(SQLite3 $db) {
		$inv_id = $db->querySingle("SELECT MAX(inventory_id) FROM inventory") + 1;
		$db->exec("INSERT INTO inventory VALUES(" . $inv_id . ",0)");
		return $inv_id;
	}

	function check_encounter(SQLite3 $db, int $place_id, bool $pacification) {
		// compute pacification factor
		$pacification_factor = 1; // 0->low encounter probability, 1->full encounter probability
		if ($pacification) {
			$last_pacification_timestamp = $db->querySingle("SELECT pacification_timestamp FROM place WHERE id == " . $place_id);
			$time_since_last_pacification = time() - $last_pacification_timestamp;
			$pacification_factor = clamp($time_since_last_pacification/86400, 0, 1);
		}
		// test against place encounter probability
		$encounter_probability = $db->querySingle("SELECT encounter_probability FROM place WHERE id == " . $place_id);
		return $encounter_probability * $pacification_factor > random_float();
	}

	function roll_encounter(SQLite3 $db, int $encounter_table_id) {
		$psum = 0;
		$roll = random_float();
		$encounter_table = $db->query("SELECT probability,ennemy_id,level FROM encounter_table WHERE id == " . $encounter_table_id);
		while ($encounter = $encounter_table->fetchArray()) {
			$psum += $encounter["probability"];
			if ($psum > $roll) {
				return $encounter;
			}
		}
		return null;
	}

?>