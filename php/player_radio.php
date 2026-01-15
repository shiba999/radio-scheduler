<?php

	require_once dirname(__FILE__) . "/__definition__.php";

	header("Content-Type: application/json; charset=UTF-8");

	include_once PROJECT_ROOT . "/php/player_control.php";

	$channel = $_POST["channel"];
	$result = radio_play($channel);

	echo $result;

?>