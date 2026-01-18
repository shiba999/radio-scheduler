<?php

// fetch で直接 json ファイルを読み込んでも良いが、PHPを経由して
// header で明示的に読み込む情報の形式を宣言すると確実性が増す

header("Content-Type: application/json; charset=UTF-8");// json を受け取る場合は明示的にJSONと宣言

//require_once dirname(__FILE__) . "/__definition__.php";

// プレイヤー情報取得

$setting_json = file_get_contents("../json/settings.json");
$setting_object = json_decode($setting_json, true);
$player = $setting_object["player"];

// プロセス確認

$pgrep_output = [];// プロセスIDが入って来る
$pgrep_return = 0;

exec(
	"pgrep " . $player,
	$pgrep_output,
	$pgrep_return
);

// $pgrep_output の中にプロセスIDが含まれているか確認
// 存在する場合は音声ファイルは再生中
// 存在しなければ音声ファイルは停止中

$return_array = array(
	"state" => false,
	//"file" => $file_name
	"file" => ""
);

// ログファイル確認

$log_file = "../log/audio.log";
$audio_log = file_get_contents($log_file);

if ( $audio_log != "" ) {

	$audio_log_explode = explode( " ",  trim($audio_log) );// ID 'filename.mp3'
	$this_pid = trim($audio_log_explode[0]);
	$file_name = trim($audio_log_explode[1], "'");

	$return_array["file"] = rawurlencode($file_name);

	if ( in_array($this_pid, $pgrep_output) ) {
		$return_array["state"] = true;
	}

}

echo json_encode($return_array);

?>