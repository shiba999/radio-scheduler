<?php

$settings_json = file_get_contents("../json/settings.json");
$settings_object = json_decode($settings_json, true);
$amixer_path = $settings_object["amixer_path"];

if ( $_POST["v"] ) {
	//$cmd = "amixer sset PCM " . $_POST["v"] . "%";
	$cmd = $amixer_path . " sset PCM " . $_POST["v"] . "%";
}

$output = shell_exec($cmd);

if ( preg_match('/\[(\d+)%\]/', $output, $matches) ) {

	$current_volume = $matches[1];// 取得した音量 (0-100)
	echo $current_volume;

} else {

	echo "Volume acquisition error.";

}

?>