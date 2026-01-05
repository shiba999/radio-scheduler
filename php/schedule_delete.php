<?php

// fetch で直接 json ファイルを読み込んでも良いが、PHPを経由して
// header で明示的に読み込む情報の形式を宣言すると確実性が増す

header("Content-Type: application/json; charset=UTF-8");// json を受け取る場合は明示的にJSONと宣言

$schedule_file = "../json/schedule.json";

// c+ 読み書きモードで開く
// b json の場合はばいないモード推奨

$fp = @ fopen($schedule_file, "c+b");

$result = 0;

if ( ! $fp ) {
	// 存在しない場合やパーミッションが無い場合
	echo 0;
	return;
}

// 排他ロック取得

if ( flock($fp, LOCK_EX) ) {

	rewind($fp);// ファイル先頭に移動

	// ### 読込 ###

	$schedule_json = stream_get_contents($fp);
	$schedule_object = json_decode($schedule_json, true);

	// 受信した情報: 削除ID

	//$this_index = count($schedule_object);

	// $schedule_object から受け取った位置の要素を削除する

	$delete_index = (int) $_POST["index"];

	array_splice($schedule_object, $delete_index, 1);

	// time キー基準でソートを行う

	usort($schedule_object, function($a, $b) {
		return strcmp($a["time"], $b["time"]);
	});

	// ### 保存 ###

	ftruncate($fp, 0);// ファイルサイズを 0 に
	rewind($fp);// ファイル先頭に移動
	$result = fwrite($fp, json_encode($schedule_object));// json にして保存

	flock($fp, LOCK_UN);// ロック解除

}

//echo json_encode($schedule_object);
echo $result;

?>