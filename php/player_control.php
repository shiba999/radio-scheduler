<?php

// 設定ファイル読み込み関数

function get_settings() {

	$get_file = PROJECT_ROOT . "/json/settings.json";
	$get_json = file_get_contents($get_file);
	$get_object = json_decode($get_json, true);

	return $get_object;

}


// チャンネルIDを受け取り radiko を再生する関数

function radiko_play($channel, $length = 0) {

	// 設定読み込み

	$settings_object = get_settings();
	$streamlink = $settings_object["streamlink_path"];
	$player = $settings_object["player"];
	$player_path = $settings_object["player_path"];

	// 監視用ログ

	$log_file = PROJECT_ROOT . "/log/radiko_play.log";

	// 1. 再生中だった場合はプレイヤーを停止

	shell_exec("pkill " . $player);

	// 2. streamlink + mpv のコマンドをPHP側で組み立てる

	$exec_command = sprintf(
		//"sudo -u www-data %s -p %s",
		"%s -p %s",
		escapeshellarg($streamlink),
		escapeshellarg($player_path)
	);

	// 再生する時間が設定されている場合

	if ( (int) $length > 0 ) {
		$exec_command .= " --player-args=" . escapeshellarg("--length=" . $length);
	}

	// URL + クオリティ

	$url = "http://radiko.jp/#!/live/" . $channel;
	$exec_command .= " " . escapeshellarg($url) . " best";

	// ログ出力 & バックグラウンド

	$exec_command .= " > " . escapeshellarg($log_file) . " 2>&1 &";

	// 実行

	exec($exec_command);

	// ログファイルの確認 (再生成功か失敗かを確認)

	$max_retries = 30;// 最大待機回数
	$retry = 0;
	$log_content = "";

	while ( $retry < $max_retries ) {

		//sleep(1);
		usleep(500000);// 1000000 で 1秒です

		clearstatcache();

		$log_content = file_get_contents($log_file);

		//if ( $log_content !== false && stripos($log_content, "waiting") !== false ) {
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
	$return_array["log"] = $log_content;

	echo json_encode($return_array);

}

// 音声ファイルをファイル名を受け取って再生する関数

function audio_play($file) {

	// 設定読み込み

	$settings_object = get_settings();
	$player = $settings_object["player"];
	$player_path = $settings_object["player_path"];

	// 1. 再生中だった場合はプレイヤーを停止

	shell_exec("pkill " . $player);

	// 監視用ログ

	$log_file = PROJECT_ROOT . "/log/player.log";
	$audio_log_file = PROJECT_ROOT . "/log/audio.log";

	// ファイル名の抽出

	$file_array = explode("/", $file);
	$file_array_count = count($file_array);
	$file_name = $file_array[ ($file_array_count - 1) ];

	// 最後に & があると即時バックグラウンド実行されるので戻り値は貰えない。
	// 最後の & を取ると戻り値を受け取れるが、再生終了まで処理中になる。
	// --no-video は mpv 固有のオプションかもしれないね

	// 2. 再生開始

	exec(
		$player_path . " --no-video --log-file=" . escapeshellarg($log_file)
		. " " . escapeshellarg($file)
		. " > /dev/null 2>&1 & echo \"\$! " . escapeshellarg($file_name) . "\" > "
		. escapeshellarg($audio_log_file)
	);

	// 3. 待機しながら起動確認

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

	// 4. 最大回数まで試しても止まらなければ stopped のまま

	if ( $status !== "playing" ) {
		$status = "error";// プロセスが立ち上がらなかった場合
	}

	echo $status;

}

// プレイヤーを停止する関数

function player_kill($echo = true) {

	$settings_object = get_settings();
	$player = $settings_object["player"];

	// 監視用ログ

	$log_file = PROJECT_ROOT . "/log/radiko_play.log";
	$audio_log_file = PROJECT_ROOT . "/log/audio.log";

	// 1. プレイヤーを停止

	exec(
		"pkill " . $player . " 2>&1",
		$output,
		$return_var
	);

	// 2. プレイヤーが完全に止まるまで短い間隔で確認

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

			file_put_contents($log_file, "");// ログを空にするのは radiko 用
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
	}

}


?>