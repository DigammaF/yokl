
<?php

	$eat = function (ItemScriptContext $context) {
		remove_item($context->db, $context->user, $context->item->id);
	};

	register_script(1, $eat);

?>
