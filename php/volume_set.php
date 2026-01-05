<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

// 受信した値へ音量を変更
// PCM は端末に接続した USB スピーカーの出力先

/*if ( $_GET["v"] ) {
	//$cmd = "amixer sset 'Master' " . $_GET["v"] . "%";
	$cmd = "amixer sset PCM " . $_GET["v"] . "%";
}*/

if ( $_POST["v"] ) {
	$cmd = "amixer sset PCM " . $_POST["v"] . "%";
}

$output = shell_exec($cmd);

if ( preg_match('/\[(\d+)%\]/', $output, $matches) ) {

	$current_volume = $matches[1];// 取得した音量 (0-100)
	echo $current_volume;

} else {

	echo "Volume acquisition error.";

}

?>