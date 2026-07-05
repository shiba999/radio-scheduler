<?php

// WSL 環境対策
// WSL なら変数を定義 : そうでなければ「空文字」

define("AUDIO_ENV", file_exists("/mnt/wslg/PulseServer") ? "PULSE_SERVER=unix:/mnt/wslg/PulseServer " : "");

// 設定ファイル読み込み関数

function get_settings() {

	$get_file = PROJECT_ROOT . "/json/settings.json";
	$get_json = file_get_contents($get_file);
	$get_object = json_decode($get_json, true);

	return $get_object;

}


// チャンネルIDを受け取りラジオを再生する関数

function radio_play($channel, $volume = false, $length = 0) {

	// 設定読み込み

	$settings_object = get_settings();
	$streamlink = $settings_object["streamlink_path"];
	$streamlink_plugin = $settings_object["sl_plugin_path"];
	$player = $settings_object["player"];
	$player_path = $settings_object["player_path"];
	$amixer_path = $settings_object["amixer_path"];
	$playback_device = $settings_object["playback_device"];
	$playback_options = $settings_object["playback_options"];

	// 監視用ログ

	$log_file = PROJECT_ROOT . "/log/player.log";

	// 1. 再生中だった場合はプレイヤーを停止

	shell_exec("pkill " . $player);

	// 2. streamlink + mpv のコマンドをPHP側で組み立てる

	// AUDIO_ENV は WSL 用の定数 ( WSL以外なら空欄 )
	// --retry-streams 10 --retry-open 10 はストリーム切断時の保険的再接続オプション

	$exec_command = AUDIO_ENV . escapeshellarg($streamlink) . " --plugin-dir " . escapeshellarg($streamlink_plugin) . " --retry-streams 10 --retry-open 10 -p " . escapeshellarg($player_path);

	$args_array = array();

	// 3-1. プレイヤーの音量調整に必要な socat

	array_push($args_array, "--input-ipc-server=" . PROJECT_ROOT . "/socket/player");

	// 3-2. 再生デバイス

	if ( $playback_device != "" ) {
		array_push($args_array, "--audio-device='" . $playback_device . "'");
	}

	// 3-3. $volume に値が送られた場合 (cron) は音量変更
	// それ以外は保存された音量で再生

	if ( $volume !== false ) {
		array_push($args_array, "--volume=" . $volume);
	} else {
		$saved_volume = file_get_contents(PROJECT_ROOT . "/log/volume.log");// 保存中の音量
		if ( ! ctype_digit( (string) $saved_volume ) ) { $saved_volume = 65; }
		array_push($args_array, "--volume=" . $saved_volume);
	}

	// 3-4. 再生オプション

	if ( $playback_options != "" ) {
		array_push($args_array, trim($playback_options));
	}

	// 3-5. 再生する時間が設定されている場合

	if ( (int) $length > 0 ) {
		array_push($args_array, "--length=" . $length);
	}

	// $args_array 結合

	$args_string = implode(" ", $args_array);

	$exec_command .= " --player-args=\"" . $args_string . "\"";

	// 4. ラジオ局URL

	$url = "http://radiko.jp/#!/live/" . $channel;

	$exec_command .= " " . escapeshellarg($url) . " best";

	// 5. ログ出力 & バックグラウンド

	$exec_command .= " > " . escapeshellarg($log_file) . " 2>&1 &";

	// 6. 実行

	//echo $exec_command;

	exec($exec_command);

	$return_array = array();

	echo json_encode($return_array);// その後のプレイヤー再生までの監視は Socket を使用することにした。

}

// 音声ファイルをファイル名を受け取って再生する関数

function audio_play($file, $volume = false, $option = array()) {

	// 設定読み込み

	$settings_object = get_settings();
	$player = $settings_object["player"];
	$player_path = $settings_object["player_path"];
	$amixer_path = $settings_object["amixer_path"];
	$playback_device = $settings_object["playback_device"];
	$playback_options = $settings_object["playback_options"];

	// 監視用ログ

	$log_file = PROJECT_ROOT . "/log/player.log";

	// 1. 再生中だった場合はプレイヤーを停止

	shell_exec("pkill " . $player);

	// 3. ファイル名の抽出

	$file_array = explode("/", $file);
	$file_array_count = count($file_array);
	$file_name = $file_array[ ($file_array_count - 1) ];

	// 最後に & があると即時バックグラウンド実行されるので戻り値は貰えない。
	// 最後の & を取ると戻り値を受け取れるが、再生終了まで処理中になる。

	// 4. 再生処理

	// AUDIO_ENV は WSL 用の定数 ( WSL以外なら空欄 )

	$exec_command = AUDIO_ENV . $player_path;

	// ソケット

	$exec_command .= " --input-ipc-server='" . PROJECT_ROOT . "/socket/player'";

	// 再生デバイス

	if ( $playback_device != "" ) {
		$exec_command .= " --audio-device='" . $playback_device . "'";
	}

	// 音量: $volume に値が送られた場合 (cron) は音量変更
	// それ以外は保存された音量で再生

	if ( $volume !== false ) {
		$exec_command .= " --volume='" . $volume . "'";
	} else {
		$saved_volume = file_get_contents(PROJECT_ROOT . "/log/volume.log");// 保存中の音量
		if ( ! ctype_digit( (string) $saved_volume ) ) { $saved_volume = 65; }
		$exec_command .= " --volume='" . $saved_volume . "'";
	}

	// 再生オプション (設定からのオプション)

	if ( $playback_options != "" ) {
		$exec_command .= " " . trim($playback_options);
	}

	// 再生オプション (引数からのオプション)
	// $option = array()

	// ループ再生
	// --loop-playlist=inf : 無限リピート
	// --loop-playlist=3 : 3回再生して終わり

	if ( array_key_exists("loop", $option) ) {

		// 99 以上指定は無限ループとする

		$loop_value = $option["loop"];
		if ($loop_value > 98) $loop_value = "inf";

		$exec_command .= " --loop-playlist=" . $loop_value;

	}

	// シャッフル再生
	// --shuffle

	if ( array_key_exists("shuffle", $option) ) {
		if ($option["shuffle"] === true) $exec_command .= " --shuffle";
	}

	// ログファイル

	$exec_command .= " --log-file=" . escapeshellarg($log_file) . " " . escapeshellarg($file) . " > /dev/null 2>&1 &";

	echo "\n\n" . $exec_command . "\n\n";

	// 再生実行

	exec($exec_command);

	$status = "exec";

	echo $status;// その後のプレイヤー再生までの監視は Socket を使用することにした。

}

// プレイヤーを停止する関数

function player_kill($echo = true) {

	$settings_object = get_settings();
	$player = $settings_object["player"];

	// 1. プレイヤーを停止

	exec(
		"pkill " . $player . " 2>&1",
		$output,
		$return_var
	);

	echo "exec";// その後のプレイヤー停止までの監視は Socket を使用することにした。

}


?>