<?php

// json を受け取る場合は明示的にJSONと宣言
header("Content-Type: application/json; charset=UTF-8");

$audio_directory = "../upload/";

// ディレクトリ内のファイル (パス付き) を取得

$file_array = glob( $audio_directory . "*" );

// ファイル名のみ抽出

$file_name_array = array_map("basename", $file_array);

//foreach ( $file_name_array as $file ) {
//	echo "<p>" . $file . "</p>";
//}

echo json_encode($file_name_array);

?>