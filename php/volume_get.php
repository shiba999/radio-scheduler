<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

// PCM は端末に接続した USB スピーカーの出力先

//$output = shell_exec("amixer get Master");
$output = shell_exec("amixer -c 0 get PCM 2>&1");

$user = shell_exec("whoami");

//echo $output . " (" . $user . ")";
//echo $output;
//echo nl2br($output);

if ( preg_match('/\[(\d+)%\]/', $output, $matches) ) {

	$current_volume = $matches[1];// 取得した音量 (0-100)
	echo $current_volume;

} else {

	echo "Volume acquisition error.";

}

?>