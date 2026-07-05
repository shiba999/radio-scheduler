<?php

	require_once dirname(__FILE__) . "/__definition__.php";

	include_once PROJECT_ROOT . "/php/player_control.php";

	//$file = PROJECT_ROOT . "/upload/" . $_POST["file"];

	$option = array(
		"loop" => 1,
		"shuffle" => true
	);

	$audio_file = PROJECT_ROOT . "/upload/awakening.mp3";
	//$audio_file = PROJECT_ROOT . "/mp3";

	echo "\n\n" . $audio_file;

/*	if ( array_key_exists("loop", $option) ) {
		echo "\n\nキーは存在します\n\n";
	} else {
		echo "\n\nキーは存在しません\n\n";
	}*/

	$result = audio_play($audio_file, 55, $option);

	echo $result;

?>