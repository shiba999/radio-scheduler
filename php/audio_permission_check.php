<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");

require_once dirname(__FILE__) . "/__definition__.php";

$audio_file = PROJECT_ROOT . "/audio/hello_world.mp3";
$settings_log = PROJECT_ROOT . "/log/settings.log";

// 実行前にログを空にする

file_put_contents($settings_log, "");

// WSL専用: 有効な WSLg ソケットを探す関数

/*function get_wsl_pulse_server() {

	$default_path = "/mnt/wslg/PulseServer";

	// もしファイルが存在し、書き込み（通信）可能であればそのパスを返す

	if ( file_exists($default_path) ) {
		return "unix:" . $default_path;
	}

	return null;

}

$pulse_path = get_wsl_pulse_server();

$audio_env = "";

if ( $pulse_path ) {
	$audio_env = "PULSE_SERVER=" . $pulse_path . " ";
}*/

$settings_json = file_get_contents(PROJECT_ROOT . "/json/settings.json");
$settings_object = json_decode($settings_json, true);

$streamlink = $settings_object["streamlink_path"];
$player = $settings_object["player"];
$player_path = $settings_object["player_path"];
$playback_device = $settings_object["playback_device"];
$playback_options = $settings_object["playback_options"];

// 実行前にプレイヤーを停止

shell_exec("pkill " . $player);

// コマンド実行

// AUDIO_ENV は WSL 用の定数 ( WSL以外なら空欄 )

$exec_command = AUDIO_ENV . $player_path;// AUDIO_ENV は WSL 用の定数 ( WSL以外なら空欄 )
$exec_command .= " --input-ipc-server='" . PROJECT_ROOT . "/socket/player'";// ソケット

// 再生デバイス

if ( $playback_device != "" ) {
	$exec_command .= " --audio-device='" . $playback_device . "'";
}

// 音量

$saved_volume = file_get_contents(PROJECT_ROOT . "/log/volume.log");// 保存中の音量
if ( ! ctype_digit( (string) $saved_volume ) ) { $saved_volume = 65; }
//array_push($args_array, "--volume=" . $saved_volume);
$exec_command .= " --volume='" . $saved_volume . "'";

// 再生オプション

if ( $playback_options != "" ) {
	$exec_command .= " " . trim($playback_options);
}

$exec_command .= " --log-file=" . escapeshellarg($settings_log) . " " . escapeshellarg($audio_file) . " > /dev/null 2>&1 &";

//$exec_command = AUDIO_ENV . $player_path . " --no-video --log-file=" . escapeshellarg($settings_log) . " " . escapeshellarg($audio_file) . " > /dev/null 2>&1 &";

//echo $exec_command . "\n\n";

exec($exec_command);

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

// ログファイルの確認 (再生成功か失敗かを確認)

$max_retries = 15;// 最大待機回数
$retry = 0;
$log_content = "";

$retry_log = array(
	"success" => false,
	"reason" => "",
	"retry" => 0
);

while ( $retry < $max_retries ) {

	//sleep(1);
	usleep(300000);// 1000000 で 1秒です

	clearstatcache();

	// ログ読込

	$log_content = file_get_contents($settings_log);

	//echo "\n----------------------" . $log_content . "\n----------------------\n\n";

	// ログ解析

	$log_lower = strtolower($log_content);// 全て小文字に変換して比較

	if ($log_content !== false) {

		// 失敗かを調査

		foreach ( $fail_patterns as $pattern ) {

			if ( strpos($log_lower, $pattern) !== false ) {

				//return ["success" => false, "reason" => $pattern, "log" => $log_content];

				$retry_log["success"] = false;
				$retry_log["reason"] = $pattern;
				$retry_log["retry"] = $retry;

				echo json_encode($retry_log);

				return;

			}

		}

		// 成功かを調査

		foreach ( $success_patterns as $pattern ) {

			if ( strpos($log_lower, $pattern) !== false ) {

				//return ["success" => true, "reason" => $pattern, "log" => $log_content];

				$retry_log["success"] = true;
				$retry_log["reason"] = $pattern;
				$retry_log["retry"] = $retry;

				echo json_encode($retry_log);

				return;

			}

		}

	}

	$retry++;

}

// それ以外の場合 (不明)

$retry_log["success"] = false;
$retry_log["reason"] = "unknown";
$retry_log["retry"] = $retry;

echo json_encode($retry_log);

?>