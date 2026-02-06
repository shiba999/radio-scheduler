<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");// json を受け取る場合は明示的にJSONと宣言

$settings_json = file_get_contents("../json/settings.json");
$settings_object = json_decode($settings_json, true);
$amixer_path = $settings_object["amixer_path"];

//echo "<p>" . $amixer_path . "</p>";

function get_playback_controls($type = null) {

	global $amixer_path;

	//$all = get_raw_controls();

	$output = shell_exec($amixer_path . " -c 0 contents 2>&1");
	preg_match_all("/name='([^']+)'/", $output, $matches);
	$all = $matches[1];

	$volume_controls = array();
	$switch_controls = array();
        
	foreach ($all as $full) {

		if ( strpos( $full, "Playback Volume") !== false ) {
			$short = str_replace(" Playback Volume", "", $full);
			$volume_controls[$short] = $full;
		} else if ( strpos($full, "Playback Switch") !== false ) {
			$short = str_replace(" Playback Switch", "", $full);
			$switch_controls[$short] = $full;
		}

	}

	if ($type == "volume") return array_keys($volume_controls);
	if ($type == "switch") return array_keys($switch_controls);

	return array_keys($volume_controls);// デフォルトVolume系

}

/*function set_volume($vol) {
	foreach (get_playback_controls('volume') as $ctrl) {
		exec( $amixer_path . " -c 0 sset '{$ctrl}' {$vol}%" );
	}
	// SwitchもON
	foreach (get_playback_controls('switch') as $ctrl) {
		exec( $amixer_path . " -c 0 sset '{$ctrl}' on" );
	}
}*/

function get_volume($ctrl) {

	global $amixer_path;

	$output = exec( $amixer_path . " -c 0 get " . $ctrl . " 2>&1" );

	$result = array();

	$result["output"] = $ctrl;

	if ( preg_match_all('/\[(\d+)%\]/', $output, $matches) ) {
		$result["volume"] = $matches[1][0];
	}

	if ( preg_match_all('/\[(on|off)\]/i', $output, $matches) ) {
		$result["mute"] = $matches[1][0];
	}

	if ( preg_match_all('/\[([-\d.]+dB)\]/', $output, $matches) ) {
		$result["db"] = $matches[1][0];
	}

	return $result;

}

// 取得した volume , switch デバイス名からミキサー用の配列を形成

$mixer = array();

$volumes = get_playback_controls("volume");

foreach ( $volumes as $device ) {

	$values = get_volume($device);

	if ( isset($values["volume"]) || isset($values["mute"]) ) {
		unset($values["output"]);
		$mixer[$device] = $values;
	}

}

//echo "<h2>switch</h2>";

$switch = get_playback_controls("switch");

foreach ( $switch as $device ) {

	$values = get_volume($device);

	if ( isset($values["volume"]) || isset($values["mute"]) ) {
		unset($values["output"]);
		$mixer[$device] = $values;
	}

}

//echo "<pre>";
//echo print_r($mixer, true);
//echo "</pre>";

echo json_encode($mixer);

?>