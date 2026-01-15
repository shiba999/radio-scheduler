<?php

	require_once dirname(__FILE__) . "/__definition__.php";

	include_once PROJECT_ROOT . "/php/player_control.php";

	$file = PROJECT_ROOT . "/upload/" . $_POST["file"];

	$result = audio_play($file);

	echo $result;

?>