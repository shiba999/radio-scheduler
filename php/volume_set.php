<?php

//$socket_path = "unix:///var/www/html/mpvsocket";
$socket_path = "unix://../socket/player";

$saved_volume = (int) $_POST["v"];
file_put_contents("../log/volume.log", $saved_volume);

$fp = @ stream_socket_client($socket_path, $errno, $errstr, 1); // タイムアウト1秒

if ( $fp && $saved_volume ) {

	$cmd_array = array(
		"command" => array(
			"set_property",
			"volume",
			$saved_volume
		)
	);

	//fwrite($fp, "{\"command\": [\"set_property\", \"volume\", " . $vol . "]}\n");
	fwrite($fp, json_encode($cmd_array) . "\n");// 音量を取得する命令を送信（最後に必ず \n）

	$response = fgets($fp);// mpv からの返答を 1 行読み込む

	fclose($fp);

	// {"request_id":0,"error":"success"} こんな形式で帰ってくる

	//echo $response;
	$response_array = json_decode($response, true);

	echo $saved_volume;

} else {

	//echo "fp: 接続失敗";
	//echo "stopped";
	echo $saved_volume;

}

?>