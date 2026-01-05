<?php


	//header("Content-Type: text/html; charset=UTF-8");
	header("Content-Type: application/json; charset=UTF-8");// json を受け取る場合は明示的にJSONと宣言
	include_once "./tool.php";


	// 受信した情報の確認 ( $_POST["json"] はデコードしたらオブジェクトであること )

	//echo $_POST["files"];
	//echo json_encode($_POST);

	$received_object = json_decode($_POST["files"], true);

	// json文字列の中身はこのような感じでファイル情報が入っている
	//
	//		{
	//			name: aaa.jpg,
	//			data: {base64}
	//		}

	// ファイル名から拡張子を除去する関数

	function remove_extension($filename) {

		$name_array = explode(".", $filename);
		array_pop($name_array);

		return implode(".", $name_array);

	}

	$file_data = base64_to_file( $received_object["data"] );// base64 -> file

	// ファイル名調整 (マルチバイト部分だけ rawurlencode)

	$safe_file_name = preg_replace_callback('/[^\x01-\x7F]+/u', function($matches) {
		return rawurlencode($matches[0]);
	}, $received_object["name"]);


	// ファイルの mimeタイプを調査するために upload_tmp へ一時的に保存
	// その中で判別して問題無ければ upload へ移動する

	$upload_tmp_path = "../upload_tmp/" . $safe_file_name;
	$upload_file_path = "../upload/" . $safe_file_name;

	// return_array

	$return_array = array(
		"result" => "failed",
		"name" => $safe_file_name,
		"message" => ""
	);

	// アップロードファイルの上限値取得とアップロードファイルの post サイズの整理

	$post_max_size = ini_get("post_max_size");// サーバーの post_max_size サイズ値

	// ファイルの一時保存

	if ( save_file_to_local_server($file_data, $upload_tmp_path) === false ) {

		// 一時保存失敗 > ここで終わらせる

		$return_array["result"] = "failed";
		$return_array["message"] = "ファイルの一時保存に失敗しました。 ファイル転送上限 (post_max_size) は " . $post_max_size . " です。 ファイルは転送時に３割程度肥大化するため、超過した可能性があります。";

		echo json_encode($return_array);

		return;

	}

	// fileinfo を使って MIME タイプ取得

	$finfo = new finfo(FILEINFO_MIME_TYPE);
	$mime = $finfo->file($upload_tmp_path);

	// MP3として許可したいMIMEを列挙

	$allowed_mimes = [
		"audio/mpeg",// 標準的なMP3
		"audio/mp3",// 一部環境で報告されることあり [web:1028] [web:1030]
		"application/octet-stream"
	];

	$error_count = 0;

	// mimeタイプ判定

	if ( ! in_array($mime, $allowed_mimes, true) ) {
		$return_array["message"] = "MP3 以外のファイルはアップロードできません。";
		$error_count++;
	}

	// ファイルの重複チェック

	if ( $error_count === 0 && file_exists($upload_file_path) ) {
		$return_array["message"] = "同名のファイルが既に存在します。";
		$error_count++;
	}

	// ファイルの保存 (一時領域から本番領域へ移動)

	if ( $error_count === 0 ) {

		// エラーが無ければ保存

		$result = @ rename($upload_tmp_path, $upload_file_path);

		if ($result) {

			$return_array["result"] = "success";
			$return_array["message"] = "ファイルアップロード成功。";

		} else {// ディレクトリが無かったり、パーミッションが足りない場合。

			$return_array["message"] = "ファイルのアップロードに失敗しました。";

			@ unlink($upload_tmp_path);

		}

	} else {

		@ unlink($upload_tmp_path);

	}

	echo json_encode($return_array);

?>