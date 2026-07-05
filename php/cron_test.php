<?php

// cron から実行されるファイル (1分に1回実行)
// スケジュール情報を読み込み、現在の時間と一致したスケジュールを実行する。
// cron 経由でラジオチャンネルを再生し失敗を前提に日経第一が再生可能かをテストする

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

$socket_path = "unix://" . PROJECT_ROOT . "/socket/player";

// チャンネルが再生されたかを Socket で確認できるまで待機する関数
// @param string $socket_path ソケットファイルのパス
// @param int $max_wait_seconds 最大待ち時間（秒）
// @return bool 再生が確認できたらtrue、タイムアウト等で確認できなければfalse

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

$error_array = array();
$error_log = "";

// ラジオ再生
// php /var/www/html/radio-scheduler/php/cron_test.php

include_once PROJECT_ROOT . "/php/player_control.php";

// 希望したチャンネルを再生 ( 例: STV )

radio_play("STV", 45);

sleep(3);// 最初の起動待ちとして少し待機

$is_playing = wait_playback($socket_path, 24);

$final_result = "success";

if ( $is_playing ) {

	echo "再生に成功しました。\n";// 再生が確認できた後の処理
	$pre_playback = true;

} else {

	echo "再生に失敗しました。予備チャンネルの再生を試みます。\n";

	array_push($error_array, "error 001: 再生に失敗しました。予備チャンネルの再生を試みます。");
	$error_log = "[CRON ERROR] error 001";

	// 利用中のプロバイダが希望チャンネルエリアから外れた可能性があるため
	// 全国で視聴可能な日経第一を再生する

	//radio_play("RN1", 45);
	radio_play("STV", 45);

	sleep(3);// 最初の起動待ちとして少し待機

	$is_pre_playing = wait_playback($socket_path, 24);

	if ( $is_pre_playing ) {

		echo "予備チャンネルの再生に成功しました。\n";// 再生が確認できた後の処理
		$pre_playback = true;
		$final_result = "alternative";

	} else {

		echo "予備チャンネルの再生に失敗しました。オフライン状態、またはストリーミングサービスに障害がある可能性があります。\n";
		array_push($error_array, "error 002: 予備チャンネルの再生に失敗しました。オフライン状態、またはストリーミングサービスに障害がある可能性があります。");
		$error_log .= " error 002";

		$final_result = "audio";

	}

}

// 代替チャンネルも再生できなかった場合はネットワークエラーの可能性
// またはサービス先のメンテナンスやダウンの可能性があるため
// mp3 再生 → 再生確認後に、異常事態があった旨をメール送信・ログ保存を行う。

if ( $final_result === "audio" ) {

	// ラジオ不通のため、ローカル音声ファイルの再生を試みる。

	$audio_file = PROJECT_ROOT . "/upload/awakening.mp3";

	$option = array(
		"loop" => 99,
		"shuffle" => false
	);

	audio_play($audio_file, 45, $option);

	sleep(3);// 最初の起動待ちとして少し待機

	$is_audio_playing = wait_playback($socket_path, 15, $option);

	//echo $is_audio_playing;

	if ( $is_audio_playing ) {

		echo "音声ファイルを再生しました。\n";// 再生が確認できた後の処理

	} else {

		echo "音声ファイルの再生に失敗しました。ご利用のシステムに障害がある可能性があります。\n";
		array_push($error_array, "error 003: 音声ファイルの再生に失敗しました。ご利用のシステムに障害がある可能性があります。");
		$error_log .= " error 003";

		$final_result = "failure";

	}

}

// $final_result の状態に応じて障害の通知を行う
// メール送信とログの保存

if ( count($error_array) > 0 ) {

	// メール送信

	require_once "/var/www/html/send_mail/send_mail.php";// 自分が構築したメール送信システムを指定

	$this_time = date("Y-m-d H:i:s", time());

	$mailto = "groove.groove.999@gmail.com";

	$subject = "らぢ助 CRON 実行エラー";

	$body = "らぢ助 CRON 実行時にエラーが発生しました。\n以下のメッセージを参照し、不具合を確認してください。\n\n";
	$body .= $this_time;
	$body .= "\n\n";
	$body .= implode("\n", $error_array);
	$body .= "\n\n";
	$body .= "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -\nらぢ助 (Radio Scheduler)";

	$admin_name = "らぢ助 (Radio Scheduler)";

	//$result = mb_send_mail($mailto, $subject, $body);
	$result = gmail_api_mail( $mailto, $subject, $body, $admin_name );

	if ( $result ) {
		echo "メールを送信しました。";
		$error_log .= " > Send email";
	} else {
		echo "メールを送信できませんでした。";
		$error_log .= " > Email sending failed.";
	}

	$error_log .= " > Email sending failed. " . $this_time . "\n";

	// ログの保存

	$log_file = PROJECT_ROOT . "/log/cron.log";

	// ↓これは cron.php 内部の流れで保存させた方が良いと思う。
	// ついでに行数制限で追記できるようにした方が良い。

	//file_put_contents($log_file, $error_log, FILE_APPEND);

}

?>