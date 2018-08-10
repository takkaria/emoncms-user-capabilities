<?php

// Make sure we have our capability check function
require_once "Modules/user_capabilities/user_capabilities.php";

if (user_has_capability('capabilities_edit')) {
	$menu_dropdown_config[] = [
		'name' => "Capabilities",
		'icon' => 'icon-lock',
		'path' => "user_capabilities",
		'order' => 50
	];
}

