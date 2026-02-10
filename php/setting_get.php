<?php

// fetch で直接 json ファイルを読み込んでも良いが、PHPを経由して
// header で明示的に読み込む情報の形式を宣言すると確実性が増す

header("Content-Type: application/json; charset=UTF-8");// json を受け取る場合は明示的にJSONと宣言

$setting_file = "../json/settings.json";
$setting_json = file_get_contents($setting_file);

if ( $setting_json == "" ) {
	$setting_json = json_encode(array());
}

echo $setting_json;

?>