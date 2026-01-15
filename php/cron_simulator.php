<?php

// cron から実行されるファイル (1分に1回実行)
// スケジュール情報を読み込み、現在の時間と一致したスケジュールを実行する。

// fetch で直接 json ファイルを読み込んでも良いが、PHPを経由して
// header で明示的に読み込む情報の形式を宣言すると確実性が増す

//header("Content-Type: application/json; charset=UTF-8");// json を受け取る場合は明示的にJSONと宣言

require_once dirname(__FILE__) . "/__definition__.php";

$schedule_file = PROJECT_ROOT . "/json/schedule.json";
$schedule_json = file_get_contents($schedule_file);

$schedule_object = json_decode($schedule_json, true);

echo "<hr />";

$test_array = $schedule_object[0];

echo "<pre>";
echo print_r($test_array, true);
echo "</pre>";

$action = $test_array["action"];
$channel = $test_array["channel"];

include_once PROJECT_ROOT . "/php/player_control.php";

if ( $action === "play" ) {

	echo "<hr />";

	echo "<pre>";
	echo print_r("play >>> " . $channel, true);
	echo "</pre>";

	$result = radio_play($channel);

} else if ( $action === "audio" ) {

	echo "<hr />";

	$file = PROJECT_ROOT . "/upload/" . $channel;

	echo "<pre>";
	echo print_r("audio >>> " . $file, true);
	echo "</pre>";

	$result = audio_play($file);

} else if ( $action === "stop" ) {

	$result = player_kill();

} else if ( $action === "reboot" ) {

	include_once PROJECT_ROOT . "/php/power_control.php";

	system_reboot(3);

}

echo "<pre>";
echo print_r($result, true);
echo "</pre>";

echo "<hr />";

echo "<pre>";
echo print_r($schedule_object, true);
echo "</pre>";

?>