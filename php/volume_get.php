<?php

$settings_json = file_get_contents("../json/settings.json");
$settings_object = json_decode($settings_json, true);
$amixer_path = $settings_object["amixer_path"];

// PCM は端末に接続した USB スピーカーの出力先

//$output = shell_exec("amixer -c 0 get PCM 2>&1");
$output = shell_exec( $amixer_path . " -c 0 get PCM 2>&1" );
//$user = shell_exec("whoami");

if ( preg_match('/\[(\d+)%\]/', $output, $matches) ) {

	$current_volume = $matches[1];// 取得した音量 (0-100)
	echo $current_volume;

} else {

	echo "error";

}

?>