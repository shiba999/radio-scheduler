<?php

// fetch で直接 json ファイルを読み込んでも良いが、PHPを経由して
// header で明示的に読み込む情報の形式を宣言すると確実性が増す

header("Content-Type: application/json; charset=UTF-8");// json を受け取る場合は明示的にJSONと宣言

if ( isset($_POST["file"]) ) {

	$filename = $_POST["file"];

	$file = "../json/" . $filename . ".json";
	$json = file_get_contents($file);

	if ( $json == "" ) {
		$json = json_encode(array());
	}

	echo $json;

}

?>