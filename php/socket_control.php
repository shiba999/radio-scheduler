<?php

require_once dirname(__FILE__) . "/__definition__.php";

//$socket_path = "unix:///var/www/html/mpvsocket";
$socket_path = "unix://" . PROJECT_ROOT . "/socket/player";

//$saved_volume = file_get_contents(PROJECT_ROOT . "/log/volume.log");

/*if ( ! ctype_digit( (string) $saved_volume ) ) {
	$saved_volume = 65;
}*/

// socket ファイルの読み込み (タイムアウト1秒)

$fp = @ stream_socket_client($socket_path, $errno, $errstr, 1);

// プレイヤーが起動していないと通信ができない

if ($fp) {

	// 音量変更

/*	$cmd_array = array(
		"command" => array(
			"set_property",
			"volume",
			$saved_volume
		)
	);*/

	// 音量取得
	// {"data":65.000000,"request_id":0,"error":"success"}

/*	$cmd_array = array(
		"command" => array("get_property", "volume")
	);*/

	// 一時停止状態確認
	// {"data":false,"request_id":0,"error":"success"}

/*	$cmd_array = array(
		"command" => array("get_property", "pause")
	);*/

	// 現在位置（秒）
	// {"data":4.330666,"request_id":0,"error":"success"}

/*	$cmd_array = array(
		"command" => array("get_property", "time-pos")
	);*/

	// 総再生時間（秒）
	// {"data":35.030204,"request_id":0,"error":"success"}

/*	$cmd_array = array(
		"command" => array("get_property", "duration")
	);*/

	// 残り時間（秒）
	// {"data":31.409871,"request_id":0,"error":"success"}

/*	$cmd_array = array(
		"command" => array("get_property", "time-remaining")
	);*/

	// 任意位置にシーク（例: 27 秒へ）
	// {"request_id":0,"error":"success"}

/*	$cmd_array = array(
		"command" => array("set_property", "time-pos", 27.0)
	);*/

	// 再生中ファイルのパス: path (/audio/aaa.mp3 など)
	// {"data":"/var/www/html/upload/awakening.mp3","request_id":66,"error":"success"}
	// ファイル名のみ: filename (aaa.mp3)
	// {"data":"awakening.mp3","request_id":66,"error":"success"}
	// 拡張子なし:filename/no-ext (aaa)
	// {"data":"awakening","request_id":66,"error":"success"}

	$cmd_array = array(
		"command" => array("get_property", "path"),
		"request_id" => 66
	);

	fwrite($fp, json_encode($cmd_array) . "\n");// 音量を取得する命令を送信（最後に必ず \n）

	$response = fgets($fp);// mpv からの返答を 1 行読み込む

	fclose($fp);// socket 通信終了

	echo $response;

/*	$result = json_decode($response, true);// JSONをパース

	if ( isset($result["data"]) ) {
		//echo "現在の音量: " . $result["data"] . "%";
		echo $result["data"];
	} else {
		//echo "取得失敗: " . ($result["error"] ?? "unknown error");
		//echo "failure";
		echo $saved_volume;
	}*/

} else {

	echo '{"request_id":0,"error":"not_working"}';

}

?>