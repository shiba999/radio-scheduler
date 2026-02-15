<?php

// WSL 環境対策
// WSL なら変数を定義 : そうでなければ「空文字」

//define("AUDIO_ENV", file_exists("/mnt/wslg/PulseServer") ? "PULSE_SERVER=unix:/mnt/wslg/PulseServer " : "");

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
	$player = $settings_object["player"];
	$player_path = $settings_object["player_path"];
	$amixer_path = $settings_object["amixer_path"];
	$playback_device = $settings_object["playback_device"];
	$playback_options = $settings_object["playback_options"];

	// 監視用ログ

	$log_file = PROJECT_ROOT . "/log/radio.log";

	// 1. 再生中だった場合はプレイヤーを停止

	shell_exec("pkill " . $player);

	// 2. streamlink + mpv のコマンドをPHP側で組み立てる

/*	$exec_command = sprintf(
		//"sudo -u www-data %s -p %s",
		"%s -p %s",
		escapeshellarg($streamlink),
		escapeshellarg($player_path)
	);*/

	// AUDIO_ENV は WSL 用の定数 ( WSL以外なら空欄 )
	// --retry-streams 10 --retry-open 10 はストリーム切断時の保険的再接続オプション

	$exec_command = AUDIO_ENV . escapeshellarg($streamlink) . " --retry-streams 10 --retry-open 10 -p " . escapeshellarg($player_path);

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

	// プレイヤーの起動確認は Socket を使用することにしてみた。

/*	// 7. ログファイルの確認 (再生成功か失敗かを確認)

	$max_retries = 30;// 最大待機回数
	$retry = 0;
	$log_content = "";

	while ( $retry < $max_retries ) {

		//sleep(1);
		usleep(500000);// 1000000 で 1秒です

		clearstatcache();

		$log_content = file_get_contents($log_file);

		if ($log_content !== false) {

			// Waiting以外のログ確認

			if ( stripos($log_content, "Available streams:") !== false ) {
				$return_array["result"] = "success";
				break;// 判定可能ログが来たので抜ける
			} else if ( stripos($log_content, "error:" ) !== false ) {
				$return_array["result"] = "failure";
				break;// 判定可能ログが来たので抜ける
			}

		}

		$retry++;

	}

	$return_array["retry"] = $retry;
	$return_array["log"] = $log_content;*/

	echo json_encode($return_array);

}

// 音声ファイルをファイル名を受け取って再生する関数

function audio_play($file, $volume = false) {

	// 設定読み込み

	$settings_object = get_settings();
	$player = $settings_object["player"];
	$player_path = $settings_object["player_path"];
	$amixer_path = $settings_object["amixer_path"];
	$playback_device = $settings_object["playback_device"];
	$playback_options = $settings_object["playback_options"];

	// 監視用ログ

	$log_file = PROJECT_ROOT . "/log/player.log";
	//$audio_log_file = PROJECT_ROOT . "/log/audio.log";

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
		//shell_exec($amixer_path . " -c 0 -M sset PCM " . $volume . "%");
		$exec_command .= " --volume='" . $volume . "'";
	} else {
		$saved_volume = file_get_contents(PROJECT_ROOT . "/log/volume.log");// 保存中の音量
		if ( ! ctype_digit( (string) $saved_volume ) ) { $saved_volume = 65; }
		//array_push($args_array, "--volume=" . $saved_volume);
		$exec_command .= " --volume='" . $saved_volume . "'";
	}

	// 再生オプション

	if ( $playback_options != "" ) {
		$exec_command .= " " . trim($playback_options);
	}

	// ログファイル

	$exec_command .= " --log-file=" . escapeshellarg($log_file) . " " . escapeshellarg($file) . " > /dev/null 2>&1 &";

/*	$exec_command .= " --log-file=" . escapeshellarg($log_file) . " " . escapeshellarg($file)
		. " > /dev/null 2>&1 & echo \"\$! " . escapeshellarg($file_name) . "\" > "
		. escapeshellarg($audio_log_file)
	;*/

	//echo $exec_command;

	// 再生実行

	exec($exec_command);

	$status = "exec";

	// プレイヤーの起動確認は Socket を使用することにしてみた。

/*	// 5. 待機しながら起動確認

	$situation = "stopped";
	$max_attempts = 25;// 最大試行回数
	$attempt = 0;
	$interval = 300000;// 1回あたり 0.3秒

	while ( $attempt < $max_attempts ) {

		$pgrep_output = [];
		$pgrep_return = 0;

		exec(
			"pgrep " . $player,
			$pgrep_output,
			$pgrep_return
		);

		// プレイヤーのプロセスが存在する → 再生開始と判断

		if ( ! empty($pgrep_output) ) {
			$status = "playing";
			break;
		}

		usleep($interval);

		$attempt++;

	}

	// 6. 最大回数まで試しても止まらなければ stopped のまま

	if ( $status !== "playing" ) {
		$status = "error";// プロセスが立ち上がらなかった場合
	}*/

	echo $status;

}

// プレイヤーを停止する関数

function player_kill($echo = true) {

	$settings_object = get_settings();
	$player = $settings_object["player"];

	// 監視用ログ

	//$log_file = PROJECT_ROOT . "/log/radio.log";
	//$audio_log_file = PROJECT_ROOT . "/log/audio.log";

	// 1. プレイヤーを停止

	exec(
		"pkill " . $player . " 2>&1",
		$output,
		$return_var
	);

	echo "exec";

	// プレイヤーの停止判断は Socket を使用することにしてみた。

/*	// 2. プレイヤーが完全に止まるまで短い間隔で確認

	$situation = "running";
	$max_attempts = 25;// 最大試行回数
	$attempt = 0;
	$interval = 300000;// 1回あたり 0.3秒

	while ( $attempt < $max_attempts ) {

		// pgrep でプレイヤーのプロセス確認

		$pgrep_output = [];
		$pgrep_return = 0;

		exec(
			"pgrep " . $player,
			$pgrep_output,
			$pgrep_return
		);

		// プロセスが存在しない → 停止完了

		if ( empty($pgrep_output) ) {

			file_put_contents($log_file, "");// ログを空にするのはラジオ用
			file_put_contents($audio_log_file, "");// 音声ファイル用のログも空にする
			$situation = "stopped";

			break;

		}

		// まだ動いているので少し待機して再チェック

		usleep($interval);

		$attempt++;

	}

	// 3. 最大回数まで試しても止まらなければ running のまま

	if ( $status !== "stopped" ) {
		$status = "error";// プロセスが止まらなかった場合
	}

	if ( $echo === true ) {
		echo $situation;
	}*/

}


?>