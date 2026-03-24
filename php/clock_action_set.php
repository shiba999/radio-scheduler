<?php

// fetch で直接 json ファイルを読み込んでも良いが、PHPを経由して
// header で明示的に読み込む情報の形式を宣言すると確実性が増す

header("Content-Type: application/json; charset=UTF-8");// json を受け取る場合は明示的にJSONと宣言

$ca_file = "../json/clock_action.json";

// c+ 読み書きモードで開く
// b json の場合はばいないモード推奨

$fp = @ fopen($ca_file, "c+b");

$result = 0;

if ( ! $fp ) {
	// 存在しない場合やパーミッションが無い場合
	echo "[]";
	return;
}

// 有効な json か判定する関数

function is_valid_json($string) {

	// 文字列が空でないか確認

	if (empty($string)) {
		return false;
	}

	// json_decode() を実行

	json_decode($string);

	// json_last_error() が JSON_ERROR_NONE か判定

	return (json_last_error() === JSON_ERROR_NONE);

}

// 排他ロック取得

if ( flock($fp, LOCK_EX) ) {

	rewind($fp);// ファイル先頭に移動

	// ### 読込 ###

	$ca_json = stream_get_contents($fp);

	if ( ! is_valid_json($ca_json) ) {
		$ca_json = "[]";
	}

	$ca_object = json_decode($ca_json, true);

	// cancel が送られてきたら配列は空にする

	$save_value = "";
	$save_flag = false;

	if ( $_POST["action"] === "cancel" ) {

		$save_flag = true;
		$save_value = "[]";

	} else {

		// 送られてきた値が既に存在するかを確認

		if ( ! in_array($_POST["action"], $ca_object) ) {

			// 存在しなければ配列に追加して保存

			array_push($ca_object, $_POST["action"]);

			$save_flag = true;
			$save_value = json_encode($ca_object);

		}

	}

	// ### 保存 ###

	if ($save_flag) {

		ftruncate($fp, 0);// ファイルサイズを 0 に
		rewind($fp);// ファイル先頭に移動
		$result = fwrite($fp, $save_value);// json にして保存

	}

	flock($fp, LOCK_UN);// ロック解除

}

echo $result;

?>