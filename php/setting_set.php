<?php

ini_set("display_errors", 1);
error_reporting(E_ALL);

// fetch で直接 json ファイルを読み込んでも良いが、PHPを経由して
// header で明示的に読み込む情報の形式を宣言すると確実性が増す

header("Content-Type: application/json; charset=UTF-8");// json を受け取る場合は明示的にJSONと宣言

if ( isset($_POST["file"]) ) {

	// json を受け取っているかの確認

	json_decode($_POST["json"]);

	if ( json_last_error() !== JSON_ERROR_NONE ) {
		//echo 0;
		return 0;
	}

	$setting_json = $_POST["json"];
	$filename = $_POST["file"];

	$file = "../json/" . $filename . ".json";

	// 保存処理

	// c+ 読み書きモードで開く
	// b json の場合はバイナリモード推奨

	$fp = @ fopen($file, "c+b");
	$result = 0;

	if ( ! $fp ) {
		// 存在しない場合やパーミッションが無い場合
		echo $result;
		return;
	}

	// 排他ロック取得

	if ( flock($fp, LOCK_EX) ) {

		ftruncate($fp, 0);// ファイルサイズを 0 に
		rewind($fp);// ファイル先頭に移動
		//$result = fwrite($fp, json_encode($setting_object));// json にして保存
		$result = fwrite($fp, $setting_json);// json のまま

		flock($fp, LOCK_UN);// ロック解除

	}

	echo $result;

}

?>