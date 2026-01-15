<?php

// fetch で直接 json ファイルを読み込んでも良いが、PHPを経由して
// header で明示的に読み込む情報の形式を宣言すると確実性が増す

header("Content-Type: application/json; charset=UTF-8");// json を受け取る場合は明示的にJSONと宣言

$check_array = array(
	"../json/settings.json",
	"../log/settings.log",
	"../log/radio.log",
	"../log/player.log",
	"../log/audio.log",
	"../log/reboot.log",
	"../log/cron.log",
	"../json/schedule.json",
	"../upload_tmp",
	"../upload"
);
$result_array = array();

foreach ( $check_array as $target ) {

	$temp = array(
		"result" => false,
		"target" => $target
	);

	if ( is_writable($target) ) {
		$temp["result"] = true;
	} else {
		$temp["result"] = false;
	}

	array_push($result_array, $temp);

}

echo json_encode($result_array);

?>