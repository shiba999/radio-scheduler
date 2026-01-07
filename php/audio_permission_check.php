<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");

$settings_json = file_get_contents("../json/settings.json");
$settings_object = json_decode($settings_json, true);

$streamlink = $settings_object["streamlink_path"];
$player = $settings_object["player"];
$player_path = $settings_object["player_path"];

$audio_file = "../upload/awakening.mp3";
$settings_log = "../log/settings.log";

/*exec(
	$player . " --no-video --log-file=" . escapeshellarg($audio_file)
	. " " . escapeshellarg($file)
	. " > /dev/null 2>&1 & echo \"\$! " . escapeshellarg($audio_file) . "\" > "
	. escapeshellarg($audio_log_file)
);*/

// 実行前にログを空にする

file_put_contents($settings_log, "");

// 実行前にプレイヤーを停止

shell_exec("pkill " . $player);

// コマンド実行

$exec_command = $player . " --no-video --log-file=" . escapeshellarg($settings_log) . " " . escapeshellarg($audio_file) . " > /dev/null 2>&1 &";

exec($exec_command);

// ログファイルの確認 (再生成功か失敗かを確認)

$max_retries = 10;// 最大待機回数
$retry = 0;
$log_content = "";

// 失敗判定文字列群

$fail_patterns = [
	"audio output initialization failed",
	"failed to create audio output",
	"cannot open audio device",
	"audio device not found"
];

// 成功判定文字列群

$success_patterns = [
	"starting audio playback",
	"audio ready",
	"starting ao"
];

while ( $retry < $max_retries ) {

	//sleep(1);
	usleep(1000000);// 1000000 で 1秒です

	clearstatcache();

	// ログ読込

	$log_content = file_get_contents($settings_log);

	// ログ解析

	$log_lower = strtolower($log_content);

	if ($log_content !== false) {

		// 失敗かを調査

		foreach ( $fail_patterns as $pattern ) {
			if ( strpos($log_lower, $pattern) !== false ) {
				//return ["success" => false, "reason" => $pattern, "log" => $log_content];
				echo json_encode(["success" => false, "reason" => $pattern]);
				return;
			}
		}

		// 成功かを調査

		foreach ( $success_patterns as $pattern ) {
			if ( strpos($log_lower, $pattern) !== false ) {
				//return ["success" => true, "reason" => $pattern, "log" => $log_content];
				echo json_encode(["success" => true, "reason" => $pattern]);
				return;
			}
		}

		// それ以外の場合 (不明)

		echo json_encode(["success" => false, "reason" => "unknown"]);

		return;

	}

	$retry++;

}

?>