
<?php

	$SCRIPTS = array();
	$COMBAT_SCRIPTS = array();

	class ItemScriptContext {
		public SQLite3 $db;
		public Pawn $user;
		public Item $item;

		function __construct(SQLite3 $db, Pawn $user, Item $item) {
			$this->db = $db;
			$this->user = $user;
			$this->item = $item;
		}
	}

	class CombatItemScriptContext {
		public SQLite3 $db;
		public Fighter $user;
		public Item $item;

		function __construct(SQLite3 $db, Fighter $user, Item $item) {
			$this->db = $db;
			$this->user = $user;
			$this->item = $item;
		}
	}

	function register_script(int $interaction_id, callable $script) {
		global $SCRIPTS;
		$SCRIPTS += [$interaction_id => $script];
	}

	function register_combat_script(int $interaction_id, callable $script) {
		global $COMBAT_SCRIPTS;
		$COMBAT_SCRIPTS += [$interaction_id => $script];
	}

	function run_script(int $interaction_id, ItemScriptContext $context) {
		global $SCRIPTS;
		$SCRIPTS[$interaction_id]($context);
	}

	function run_combat_script(int $interaction_id, CombatItemScriptContext $context) {
		global $COMBAT_SCRIPTS;
		$COMBAT_SCRIPTS[$interaction_id]($context);
	}

	include("eat.php");

?>
