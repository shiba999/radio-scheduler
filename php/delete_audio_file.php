<?php

// json を受け取る場合は明示的にJSONと宣言
header("Content-Type: application/json; charset=UTF-8");

$audio_directory = "../upload/";

// ディレクトリ内のファイル (パス付き) を取得

$file_array = glob( $audio_directory . "*" );

// ファイル名のみ抽出

$file_name_array = array_map("basename", $file_array);

// 送られたファイル名が存在しているか確認 (ファイル名はURLエンコードされているので戻す)

$target_file_name = urldecode($_POST["file"]) ?? "";

if ( empty($target_file_name) ) {

	echo json_encode(["error", "ファイル名が指定されていません。 " . $target_file_name]);

	return;

}

if ( ! in_array($target_file_name, $file_name_array) ) {

	echo json_encode(["error", "指定されたファイルが見つかりません。 " . $target_file_name]);

	return;

} else {

	//echo json_encode(["exist", "指定されたファイルが見つかりました。 " . $target_file_name]);

	$delete_path = $audio_directory . $target_file_name;

	if ( @ unlink($delete_path) ) {
		echo json_encode(["success", "ファイルを削除しました。 " . $target_file_name]);
	} else {
		echo json_encode(["error", "ファイルの削除に失敗しました。 " . $target_file_name]);
	}

}



//foreach ( $file_name_array as $file ) {
//	echo "<p>" . $file . "</p>";
//}

//echo json_encode($file_name_array);

?>