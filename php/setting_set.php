<?php

// fetch で直接 json ファイルを読み込んでも良いが、PHPを経由して
// header で明示的に読み込む情報の形式を宣言すると確実性が増す

header("Content-Type: application/json; charset=UTF-8");// json を受け取る場合は明示的にJSONと宣言

$setting_file = "../json/settings.json";

// 受信した情報をまとめる

$streamlink_path = $_POST["streamlink_path"];
$player = $_POST["player"];
$player_path = $_POST["player_path"];
$amixer_path = $_POST["amixer_path"];
$reboot_path = $_POST["reboot_path"];
$shutdown_path = $_POST["shutdown_path"];
$openweather_api = $_POST["openweather_api"];
$latitude = $_POST["latitude"];
$longitude = $_POST["longitude"];
$clock_display = $_POST["clock_display"];
$timezone = $_POST["timezone"];

// openweather_api latitude longitude clock_display はおまけ機能なので必須ではない

if ( $streamlink_path && $player && $player_path && $amixer_path && $reboot_path && $shutdown_path ) {

	$setting_object = array(
		"streamlink_path" => $streamlink_path,
		"player" => $player,
		"player_path" => $player_path,
		"amixer_path" => $amixer_path,
		"reboot_path" => $reboot_path,
		"shutdown_path" => $shutdown_path,
		"openweather_api" => $openweather_api,
		"latitude" => $latitude,
		"longitude" => $longitude,
		"clock_display" => $clock_display,
		"timezone" => $timezone
	);

	// 保存処理

	// c+ 読み書きモードで開く
	// b json の場合はバイナリモード推奨

	$fp = @ fopen($setting_file, "c+b");

	if ( ! $fp ) {
		// 存在しない場合やパーミッションが無い場合
		echo 0;
		return;
	}

	// 排他ロック取得

	if ( flock($fp, LOCK_EX) ) {

		ftruncate($fp, 0);// ファイルサイズを 0 に
		rewind($fp);// ファイル先頭に移動
		$result = fwrite($fp, json_encode($setting_object));// json にして保存

		flock($fp, LOCK_UN);// ロック解除

	}

}

echo $result;

?>