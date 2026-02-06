<?php

//$socket_path = "unix:///var/www/html/mpvsocket";
$socket_path = "unix://../socket/player";

$saved_volume = file_get_contents("../log/volume.log");

if ( ! ctype_digit( (string) $val ) ) {
	$saved_volume = 65;
}

$fp = @ stream_socket_client($socket_path, $errno, $errstr, 1); // タイムアウト1秒

// プレイヤーが起動していないと音量が取得できないので
// プレイヤーが動作していない場合は保存された音量を返す

if ($fp) {

	$cmd_array = array(
		"command" => array("get_property", "volume")
	);

	fwrite($fp, json_encode($cmd_array) . "\n");// 音量を取得する命令を送信（最後に必ず \n）

	$response = fgets($fp);// mpv からの返答を 1 行読み込む
	fclose($fp);

	$result = json_decode($response, true);// JSONをパース

	if ( isset($result["data"]) ) {
		//echo "現在の音量: " . $result["data"] . "%";
		echo $result["data"];
	} else {
		//echo "取得失敗: " . ($result["error"] ?? "unknown error");
		//echo "failure";
		echo $saved_volume;
	}

} else {

	//echo "fp: 接続失敗";
	//echo "stopped";
	echo $saved_volume;

}

?>