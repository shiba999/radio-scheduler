<?php

	require_once dirname(__FILE__) . "/__definition__.php";

	include_once PROJECT_ROOT . "/php/player_control.php";

	$result = player_kill();

	echo $result;

?>