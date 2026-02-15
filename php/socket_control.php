<?php

require_once dirname(__FILE__) . "/__definition__.php";

header("Content-Type: application/json; charset=UTF-8");

//$socket_path = "unix:///var/www/html/mpvsocket";
$socket_path = "unix://" . PROJECT_ROOT . "/socket/player";

// 受信情報の整理

/*$_POST["type"] = "set";
$_POST["property"] = "test";
$_POST["value"] = "abc";
$_POST["id"] = 123;*/

$type = ( $_POST["type"] == "set" ) ? "set" : "get";
$property = $_POST["property"] ?? false;
$value = $_POST["value"] ?? false;
$id = $_POST["id"] ?? 0;

/*echo "type: " . $type . "<br />";
echo "property: " . $property . "<br />";
echo "value: " . $value . "<br />";
echo "id: " . $id . "<br />";*/

$return_json = '{"request_id":0,"data":"","error":"not_working"}';

/*
$cmd_array = array(
	"command" => array("get_property", "duration"),
	"request_id" => 100
);
*/

// property が存在しない場合はそこで終了

if ( ! $property ) {
	echo $return_json;
	return;
}

/*
//echo "<pre>";
//echo print_r($cmd_array, true);
//echo "</pre>";

//$saved_volume = file_get_contents(PROJECT_ROOT . "/log/volume.log");

if ( ! ctype_digit( (string) $saved_volume ) ) {
	$saved_volume = 65;
}*/

// socket ファイルの読み込み (タイムアウト*秒)

$fp = @ stream_socket_client($socket_path, $errno, $errstr, 0.3);

// プレイヤーが起動していないと通信ができない

if ($fp) {

/*
	// 音量変更

	$cmd_array = array(
		"command" => array(
			"set_property",
			"volume",
			$saved_volume
		)
	);

	// 音量取得
	// {"data":65.000000,"request_id":0,"error":"success"}

	$cmd_array = array(
		"command" => array("get_property", "volume")
	);

	// 一時停止状態確認
	// {"data":false,"request_id":0,"error":"success"}

	$cmd_array = array(
		"command" => array("get_property", "pause")
	);

	// 現在位置（秒）
	// {"data":4.330666,"request_id":0,"error":"success"}

	$cmd_array = array(
		"command" => array("get_property", "time-pos")
	);

	// 総再生時間（秒）
	// {"data":35.030204,"request_id":0,"error":"success"}

	$cmd_array = array(
		"command" => array("get_property", "duration")
	);

	// 残り時間（秒）
	// {"data":31.409871,"request_id":0,"error":"success"}

	$cmd_array = array(
		"command" => array("get_property", "time-remaining")
	);

	// 任意位置にシーク（例: 27 秒へ）
	// {"request_id":0,"error":"success"}

	$cmd_array = array(
		"command" => array("set_property", "time-pos", 27.0)
	);

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

	// protocol プロパティ
	// {"data":"awakening","request_id":66,"error":"success"}

	$cmd_array = array(
		"command" => array("get_property", "duration"),
		"request_id" => 100
	);*/

 	// fgets で mpv の応答を永遠に待ち続けてハングを防止する
    stream_set_timeout($fp, 0, 300000);// 0秒 + 300,000マイクロ秒

	// 送られてきた値を判別して変換
	// 文字列で送られてきた "true" や "false" は
	// Boolean の true や false に変換する

	if ($value === "true") {
		$value = true;
	} elseif ($value === "false") {
		$value = false;
	}

	$cmd_array = array(
		"command" => array(),
		"request_id" => $id
	);

	if ( $type === "get" ) {

		$cmd_array["command"] = array(
			"get_property",
			$property
		);

	} else {

		$cmd_array["command"] = array(
			"set_property",
			$property,
			$value
		);

	}

	fwrite($fp, json_encode($cmd_array) . "\n");// 音量を取得する命令を送信（最後に必ず \n）

	$response = fgets($fp);// mpv からの返答を 1 行読み込む

	$info = stream_get_meta_data($fp);
	if ( $info["timed_out"] ) {
		// タイムアウトした際のレスポンスを生成
		echo '{"request_id":0,"data":"","error":"timeout"}';
		return;
	}

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

	echo $return_json;

}

?>