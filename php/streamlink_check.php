<?php

	require_once dirname(__FILE__) . "/__definition__.php";

	function get_settings() {

		$get_file = PROJECT_ROOT . "/json/settings.json";
		$get_json = file_get_contents($get_file);
		$get_object = json_decode($get_json, true);

		return $get_object;

	}

	$settings_object = get_settings();
	$streamlink = $settings_object["streamlink_path"];

	echo exec( $streamlink . " --version 2>&1" );

?>