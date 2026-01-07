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

// 現在時間
// https://blog.codecamp.jp/php-datetime

//$this_date = date("Y/m/d (D) G:i:s");
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

$update = false;
$roop = -1;
$roop_log = "";

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

	// 有効なら次へ 無効ならストップ

	$roop_log .= $roop . ". [" . $current_date . "]"
		. " ... Current time: " . $current_hour . ":" . $current_minute 
		. " | This schedule time: " . $this_hour . ":" . $this_minute
		. " | Action: " . $this_action . " , channel: " . $this_channel
	;

	if ( $this_enabled === false ) {
		$roop_log .= " ... # 未実行 (enabled: false)";
		continue;// false (無効) の場合は何もしない
	}

	// 曜日チェック > 設定されていない場合は次へ
	// 設定されている場合 > チェックして該当する曜日でない場合はストップ

	if ( count($this_week_array) > 0 && in_array($current_week, $this_week_array) === false ) {
		$roop_log .= " ... # 未実行 (week: false)";
		continue;// 曜日を設定しており、かつ該当の曜日でない場合は何もしない
	}

	// 時間のチェック
	// 時間のチェック方法はどうしよう？
	// 現在時間と設定時間との比較
	// 1. 各時間の 時 と 分 の値を比較して同じだった場合に実行する。この場合何らかの要因で cron がピンポイントで遅れてしまった場合に実行されない場合がある。
	// 2. 設定時間をタイムスタンプに変換して比較する。この場合、過去に実行したスケジュールも対象に含まれてしまう場合がある。
	// [仮採用] 1 の方法を採用してみる。cron が確実に実行されることを祈りましょう。

	if ( $current_hour !== $this_hour || $current_minute !== $this_minute ) {
		$roop_log .= " ... # 未実行 (time: false)";
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

		// radiko 再生

		include_once PROJECT_ROOT . "/php/player_control.php";

		radiko_play($this_channel);

	} else if ( $this_action === "audio" ) {

		// 音声ファイル再生

		include_once PROJECT_ROOT . "/php/player_control.php";

		$file = PROJECT_ROOT . "/upload/" . $this_channel;

		audio_play($file);

	} else if ( $this_action === "stop" ) {

		// プレイヤーを停止

		include_once PROJECT_ROOT . "/php/player_control.php";

		player_kill();

	} else if ( $this_action === "reboot" ) {

		// 再起動を実行

		include_once PROJECT_ROOT . "/php/power_control.php";

		system_reboot(3);

	}

	$roop_log .= "\n";

}

// 必要に応じてスケジュール情報を更新

// 1. リピート設定していないスケジュールは削除か無効化させる
// 2. 他に何かあれば変更

// オブジェクトをjsonで保存 (一応ファイルロック)

if ( $update === true ) {
	$result = file_put_contents($schedule_file, json_encode($schedule_object), LOCK_EX);
}

$result = file_put_contents(
	PROJECT_ROOT . "/log/cron.log",
	$roop_log,
	LOCK_EX
);

// cron で実行しているので何も返さない。
// ログが欲しいならファイルに保存させる

//echo json_encode($schedule_object);
//echo $result;

?>