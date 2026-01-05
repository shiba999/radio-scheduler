<?php

// fetch で直接 json ファイルを読み込んでも良いが、PHPを経由して
// header で明示的に読み込む情報の形式を宣言すると確実性が増す

header("Content-Type: application/json; charset=UTF-8");// json を受け取る場合は明示的にJSONと宣言

$schedule_file = "../json/test.json";

// c+ 読み書きモードで開く
// b json の場合はばいないモード推奨

$fp = @ fopen($schedule_file, "c+b");

$result = 0;

if ( ! $fp ) {

	// 存在しない場合やパーミッションが無い場合

	//die("ファイルを開けません");

	echo 0;
	return;

}

// 排他ロック取得

if ( flock($fp, LOCK_EX) ) {

	rewind($fp);// ファイル先頭に移動

	// ### 読込 ###

	$schedule_json = stream_get_contents($fp);
	$schedule_object = json_decode($schedule_json, true);

	// 受信した情報をまとめる

	$this_index = count($schedule_object);

	// filter_var() は "true" を true へと変換してくれる
	// 現在新規追加しかできない状態だが、既存情報の変更もできるようにしたい。
	// 新規追加の場合は、受け取った id を 0 としているが
	// 0 以外の場合は、対象となる index と置き換える様に改良すること。
	// 新規追加は 0 ではなく new でも良いと思う。

	$post_array = array(
		"id" => $_POST["id"],
		"time" => $_POST["time"],
		"channel" => $_POST["channel"],
		"action" => $_POST["action"],
		"repeat" => filter_var($_POST["repeat"] ?? "", FILTER_VALIDATE_BOOLEAN),
		"week" => json_decode($_POST["week"]),
		"enabled" => filter_var($_POST["enabled"] ?? "", FILTER_VALIDATE_BOOLEAN)
	);

	//$this_id = "new";

	if ( $_POST["id"] === "new" ) {// 新規追加

		//$this_id = $this_index + 1;

		array_push($schedule_object, $post_array);// 末尾に追加

	} else {// 既存情報の修正

		$this_id = (int) $_POST["id"];

		$schedule_object[$this_id] = $post_array;// n番目を置換

	}

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