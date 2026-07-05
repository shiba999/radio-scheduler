<?php

// cron から実行されるファイル (1分に1回実行)
// スケジュール情報を読み込み、現在の時間と一致したスケジュールを実行する。

require_once dirname(__FILE__) . "/__definition__.php";

// タイムゾーン取得

$schedule_file = PROJECT_ROOT . "/json/settings.json";
$schedule_json = file_get_contents($schedule_file);
$schedule_object = json_decode($schedule_json, true);

// タイムゾーンは設定されていなくても "Asia/Tokyo" となるようにしている
// "Asia/Tokyo" 以外に設定されていれば、対応したタイムゾーンになる
// このソフトを日本以外で使う人は居ないとは思うけど

$timezone_text = "UTC";

if ( isset($schedule_object["timezone"]) ) {
	$timezone_text = $schedule_object["timezone"];
} else {
	$timezone_text = "Asia/Tokyo";
}

date_default_timezone_set($timezone_text);

// 保存されたスケジュールを取得

$schedule_file = PROJECT_ROOT . "/json/schedule.json";
$schedule_json = file_get_contents($schedule_file);

$schedule_object = json_decode($schedule_json, true);

// チャンネルが再生されたかを Socket で確認できるまで待機する関数
// @param string $socket_path ソケットファイルのパス
// @param int $max_wait_seconds 最大待ち時間（秒）
// @return bool 再生が確認できたらtrue、タイムアウト等で確認できなければfalse

$socket_path = "unix://" . PROJECT_ROOT . "/socket/player";// Socket パス

function wait_playback( string $socket_path, int $max_wait_seconds = 30 ): bool {

	$retry_interval = 3;// チェック間隔（3秒）
	$elapsed_time = 0;

	echo "Checking playback to begin...\n";

	while ($elapsed_time < $max_wait_seconds) {

		// 1. ソケット接続を試みる

		$fp = @ stream_socket_client($socket_path, $errno, $errstr, 0.3);

		if ( $fp ) {

			// 2. タイムアウト設定

			stream_set_timeout($fp, 0, 300000);

			// 3. filename取得コマンド送信

			$cmd_array = [
				"command" => ["get_property", "filename"],
				"request_id" => 1
			];

			fwrite($fp, json_encode($cmd_array) . "\n");

			// 4. 返答の読み込みとクローズ

			$response = fgets($fp);
			$info = stream_get_meta_data($fp);
			fclose($fp);

			// ソケット通信がタイムアウトした場合はスキップして次へ

			if ( $info["timed_out"] ) {
				goto wait_and_continue;
			}

			// 5. レスポンスの解析

			$res_data = json_decode($response, true);
			$current_file = $res_data['data'] ?? '';

			// ファイル名が取得できたら「再生開始」とみなして true を返す

			if ( ! empty($current_file) ) {
				echo "[Playback!] " . $current_file . "\n";
				return true;
			}

		}

		wait_and_continue:

		// 再生が確認できない場合は3秒待機

		sleep($retry_interval);

		$elapsed_time += $retry_interval;

		echo "Waiting... " . $elapsed_time . "/" . $max_wait_seconds . "sec\n";

	}

	echo "Error: Playback could not be confirmed within the specified time.\n";

	return false;

}

// 現在時間
// https://blog.codecamp.jp/php-datetime

$exec_time = date("Y/m/d (D) G:i:s");
$current_date = date("D-G-i");
$current_date_array = explode("-", $current_date);

// 曜日 Mon > mon 小文字に変換しておく

$current_week = strtolower($current_date_array[0]);

// 時と分 文字列から数値に変換しておく

$current_hour = (int) $current_date_array[1];
$current_minute = (int) $current_date_array[2];

// 現在の日時情報とスケジュール情報の比較
// 自由にスケジュールを設定できるため、同じ時間に複数のスケジュールが設定されている可能性もあり得る。その場合の振る舞い方を考慮する必要がある。

// 1. 同時刻に複数のスケジュール合が見つかった場合は、一番最後のスケジュールを優先させる。
// 2. 一番最初に見つかった時間に実行して、次の処理に移る。

// [仮採用] 1 を採用する。同時に複数の "リピートさせないスケジュール" を検出した場合、全ての enabled を false に変更させる必要があるから。
// そのため、ループ処理途中に repeat > false の要素があった場合は enabled > false へ変更させなければならない。
// 上記の様なスケジュールへ変更があった場合は、変数を用意して true ならスケジュール json を更新させる必要がある。

$update = false;// スケジュール情報を更新する必要がある場合は true にする
$save_log = false;// スケジュール実行が有ったら true にしてログ保存
$roop = -1;// ログのスケジュール番号用
$roop_log = "[" . $exec_time . "]";// ログを蓄積
$error_array = array();// エラー内容収納 (メール用)
$restart_later = false;// true ならログ書き込み後に再起動を実行

// 保存されているスケジュールを１件ずつ確認

foreach ( $schedule_object as $schedule ) {

	// ループ処理のカウント
	// 途中で continue が入るため冒頭で +1 させている
	// なので初期値は -1

	$roop++;

	// スケジュールの諸情報を取り出す

	$this_enabled = $schedule["enabled"];// true: 有効 , false: 無効
	$this_repeat = $schedule["repeat"];// true: 有効 , false: 無効
	$this_week_array = $schedule["week"];// 実行曜日 (配列)
	$this_time_array = explode(":", $schedule["time"]);// 実行時間: "12:05" (文字列) を配列に分割
	$this_hour = (int) $this_time_array[0];// 実行時間 (時) 数値に変換
	$this_minute = (int) $this_time_array[1];// 実行時間 (時) 数値に変換
	$this_action = $schedule["action"];
	$this_channel = $schedule["channel"];
	$this_volume = $schedule["volume"];

	// 有効なら次へ 無効ならストップ

/*	$roop_log .= $roop . ". [" . $current_date . "]"
		. " ... Current time: " . $current_hour . ":" . $current_minute 
		. " | This schedule time: " . $this_hour . ":" . $this_minute
		. " | Action: " . $this_action . " , channel: " . $this_channel
	;*/

	$roop_log .=
		" {" . $roop . "} | This Schedule: " . $this_hour . ":" . $this_minute
		. " | Action: " . $this_action . " , channel: " . $this_channel
	;

	if ( $this_enabled === false ) {
		$roop_log .= " ... # Not executed (enabled: false)";
		continue;// false (無効) の場合は何もしない
	}

	// 曜日チェック > 設定されていない場合は次へ
	// 設定されている場合 > チェックして該当する曜日でない場合はストップ

	if ( count($this_week_array) > 0 && in_array($current_week, $this_week_array) === false ) {
		$roop_log .= " ... # Not executed (week: false)";
		continue;// 曜日を設定しており、かつ該当の曜日でない場合は何もしない
	}

	// 時間のチェック
	// 時間のチェック方法はどうしよう？
	// 現在時間と設定時間との比較
	// 1. 各時間の 時 と 分 の値を比較して同じだった場合に実行する。この場合何らかの要因で cron がピンポイントで遅れてしまった場合に実行されない場合がある。
	// 2. 設定時間をタイムスタンプに変換して比較する。この場合、過去に実行したスケジュールも対象に含まれてしまう場合がある。
	// [仮採用] 1 の方法を採用してみる。cron が確実に実行されることを祈りましょう。

	if ( $current_hour !== $this_hour || $current_minute !== $this_minute ) {
		$roop_log .= " ... # Not executed (time: false)";
		continue;// 時 と 分 両方一致しない場合は何もしない
	}

	// repeat が false となっている場合は、一回だけの実行なので
	// enabled を false へ変更させる必要がある

	if ( $this_repeat === false ) {
		$schedule_object[$roop]["enabled"] = false;
		$update = true;
	}

	// アクションを実行

	if ( $this_action === "play" ) {

		$save_log = true;
		$error_log = "";

		include_once PROJECT_ROOT . "/php/player_control.php";

		// *** 再生 1 *** ラジオ 1 (スケジュール登録された任意のチャンネル)

		radio_play($this_channel, $this_volume);
		//radio_play("AAA", $this_volume);

		sleep(3);// 最初の起動待ちとして少し待機

		$is_playing = wait_playback($socket_path, 30);

		$final_result = "success";

		if ( $is_playing ) {

			//echo "再生に成功しました。\n";// 再生が確認できた後の処理
			//$pre_playback = true;

		} else {

			//echo "再生に失敗しました。予備チャンネルの再生を試みます。\n";

			array_push($error_array, "error 001: 再生に失敗しました。予備チャンネルの再生を試みました。");
			$error_log .= "[CRON ERROR] error 001";

			// *** 再生 2 *** ラジオ 2 (固定の予備チャンネル)

			// 利用中のプロバイダが希望チャンネルエリアから外れた可能性があるため
			// 全国で視聴可能な日経第一を再生する

			radio_play("RN1", $this_volume);
			//radio_play("AAA", $this_volume);


			sleep(3);// 最初の起動待ちとして少し待機

			$is_pre_playing = wait_playback($socket_path, 30);

			if ( $is_pre_playing ) {

				//echo "予備チャンネルの再生に成功しました。\n";// 再生が確認できた後の処理
				//$pre_playback = true;
				$final_result = "alternative";

			} else {

				//echo "予備チャンネルの再生に失敗しました。オフライン状態、またはストリーミングサービスに障害がある可能性があります。\n";
				array_push($error_array, "error 002: 予備チャンネルの再生に失敗しました。オフライン状態、またはストリーミングサービスに障害がある可能性があります。ローカル音声ファイルの再生を試みました。");
				$error_log .= " error 002";
				$final_result = "audio";

			}

		}

		// *** 再生 3 *** 音声ファイル

		// 代替チャンネルも再生できなかった場合はネットワークエラーの可能性
		// またはサービス先のメンテナンスやダウンの可能性があるため
		// mp3 再生 → 再生確認後に、異常事態があった旨をメール送信・ログ保存を行う。

		if ( $final_result === "audio" ) {

			// ラジオ不通のため、ローカル音声ファイルの再生を試みる。

			$audio_file = PROJECT_ROOT . "/audio/i_have_to_report_it.mp3";
			//$audio_file = PROJECT_ROOT . "/upload/space_cobra.mp3";

			$option = array(
				"loop" => 99,// 無限ループ
				"shuffle" => false
			);

			audio_play($audio_file, $this_volume, $option);

			sleep(3);// 最初の起動待ちとして少し待機

			$is_audio_playing = wait_playback($socket_path, 15, $option);

			//echo $is_audio_playing;

			if ( ! $is_audio_playing ) {

				//echo "音声ファイルの再生に失敗しました。ご利用のシステムに障害がある可能性があります。\n";
				array_push($error_array, "error 003: 音声ファイルの再生に失敗しました。ご利用のシステムに障害がある可能性があります。");
				$error_log .= " error 003";
				$final_result = "failure";

			}

		}

		$roop_log .= " " . $error_log;

	} else if ( $this_action === "audio" ) {

		$save_log = true;
		$error_log = "";

		// 音声ファイル再生

		include_once PROJECT_ROOT . "/php/player_control.php";

		$file = PROJECT_ROOT . "/upload/" . $this_channel;

		audio_play($file, $this_volume);

		sleep(3);// 最初の起動待ちとして少し待機

		$is_audio_playing = wait_playback($socket_path, 15, $option);

		//echo $is_audio_playing;

		if ( ! $is_audio_playing ) {

			//echo "音声ファイルの再生に失敗しました。ご利用のシステムに障害がある可能性があります。\n";
			array_push($error_array, "error 003: 音声ファイルの再生に失敗しました。ご利用のシステムに障害がある可能性があります。");
			$error_log .= " error 003";
			$final_result = "failure";

		}

		$roop_log .= " " . $error_log;

	} else if ( $this_action === "stop" ) {

		$save_log = true;
		$roop_log .= " player stop";

		// プレイヤーを停止

		include_once PROJECT_ROOT . "/php/player_control.php";

		player_kill();

	} else if ( $this_action === "reboot" ) {

		$save_log = true;

		// 再起動を予約 > ログ保存後に再起動

		$restart_later = true;

	}

}

// 必要に応じてスケジュール情報を更新

// 1. リピート設定していないスケジュールは削除か無効化させる
// 2. 他に何かあれば変更

// オブジェクトをjsonで保存 (一応ファイルロック)

if ( $update === true ) {
	$result = file_put_contents($schedule_file, json_encode($schedule_object), LOCK_EX);
}

// メール送信 - - - - - - - - - - - - - - -

if ( count($error_array) > 0 ) {

	// メール送信

	require_once PROJECT_ROOT . "/php/send_mail.php";// 自分が構築したメール送信システムを指定

	$this_time = date("Y-m-d H:i:s", time());

	$mailto = "groove.groove.999@gmail.com";// これも管理用メールを設定ページから追加できるようにすること

	$subject = "らぢ助 CRON 実行エラー";// メールアドレスと同様変更可能に

	$body = "らぢ助 CRON 実行時にエラーが発生しました。\n以下のメッセージを参照し、不具合を確認してください。\n\n";
	$body .= $this_time;
	$body .= "\n\n";
	$body .= implode("\n", $error_array);
	$body .= "\n\n";
	$body .= "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -\nらぢ助 (Radio Scheduler)";

	//$admin_name = "らぢ助 (Radio Scheduler)";

	//$result = mb_send_mail($mailto, $subject, $body);
	//$result = gmail_api_mail( $mailto, $subject, $body, $admin_name );
    //$result = smtp_mail( $mailto, $subject, $body, $admin_name );

	$result = system_mail($subject, $body);

	if ( $result ) {
		//echo "メールを送信しました。";
		$roop_log .= " > Send email";
	} else {
		//echo "メールを送信できませんでした。";
		$roop_log .= " > Email sending failed.";
	}

}

// ログを保存 - - - - - - - - - - - - - - -
// アクションが実行された場合 ($save_log === true) ログを保存
// 再生エラーなどがあればアクション実行済みなのでメールも送られている

if ($save_log) {

	// 再起動が予約されているのであれば、ここで再起動を実行する。

	if ( $restart_later ) {

		include_once PROJECT_ROOT . "/php/power_control.php";

		// 再起動を実行 > 10秒後に実行
		// 待機している間に > 再起動受理確認 > ログ保存

		$reboot_result = system_reboot(10);

		if ($reboot_result) {
			$roop_log .= " reboot accepted.";
		} else {
			$roop_log .= " reboot failed...";
		}

	}

	$roop_log .= "\n";

    $log_file = PROJECT_ROOT . "/log/cron.log";
    $max_lines = 50;

    // 既存ログ取得

    $lines = [];

    if ( file_exists($log_file) ) {
        $lines = file($log_file, FILE_IGNORE_NEW_LINES);
    }

    // 新規ログ追加

    $lines[] = trim($roop_log);

    // 行数制限

    if ( count($lines) > $max_lines ) {
        $lines = array_slice($lines, -$max_lines);
    }

    // ログの保存

    $result = file_put_contents(
        $log_file,
        implode(PHP_EOL, $lines) . PHP_EOL,
        LOCK_EX
    );

}

?>