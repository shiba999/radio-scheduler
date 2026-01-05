<?php


	// オリジナルは U:\var\www\html\fitness-panda\admin\file_manager\ajax_php になります


	// flush() を使用して処理の途中で出力を行う
	// バッファ文字数を確保するため半角スペースを一定数付加しています

	function flush_output($string) {

		echo $string . str_pad(" ", 1024);
		@ob_flush();
		@flush();

	}


	// base64情報からファイルデータを取り出す
	// base64情報ではなかった場合は false を返す
	// 正常に取り出せた場合はファイル情報を返す

	function base64_to_file($base64) {

		$data_parts = explode(',', $base64, 2);// limit 2で配列を作成

		$pattern = "/^data:/";// 正規表現用文字列

		if (preg_match($pattern, $data_parts[0])) {
			$result = base64_decode($data_parts[1]);// file data
		} else {
			$result = false;
		}

		return $result;

	}


	// ローカル領域にファイルを保存する

	function save_file_to_local_server($file, $path) {

		$fp = @ fopen($path, "w");

		// 開けなかった場合は false を返す

		if ( $fp === false ) {
			return false;
		}

		$result = @ fwrite($fp, $file);
		@ fclose($fp);

		// fwrite が失敗した場合も false

		if ( $result === false ) {
			return false;
		}

		return $result;

	}


	// ローカル領域に保存されたファイルを削除する

	function delete_local_server_file($file, $path) {

		$fp = fopen($path, "w");
		$result = fwrite($fp, $file);
		fclose($fp);

		return $result;

	}



	// MIME TYPE から拡張子を特定させる関数
	// こうすることにより拡張子偽造を無効する
	// 特定できなかったファイルは許可しない

	function extension_search($mime_type) {

		// https://colo-ri.jp/develop/2011/04/uploader-fileformat-detection.html

		switch ($mime_type) {

			case "image/gif":
			case "image/x-xbitmap":
			case "image/gi_":

				$extension = "gif";
				$mode = "binary";
				break;

			case "image/jpeg":
			case "image/jpg":
			case "image/jp_":
			case "application/jpg":
			case "application/x-jpg":
			case "image/pjpeg":
			case "image/pipeg":
			case "image/vnd.swiftview-jpeg":

				$extension = "jpg";
				$mode = "binary";
				break;

			case "image/png":
			case "application/png":
			case "application/x-png":

				$extension = "png";
				$mode = "binary";
				break;

			case "image/bmp":
			case "image/x-bmp":
			case "image/x-bitmap":
			case "image/x-xbitmap":
			case "image/x-win-bitmap":
			case "image/x-windows-bmp":
			case "image/ms-bmp":
			case "image/x-ms-bmp":
			case "application/bmp":
			case "application/x-bmp":
			case "application/x-win-bitmap":

				$extension = "bmp";
				$mode = "binary";
				break;

			case "application/pdf":
			case "application/x-pdf":
			case "application/acrobat":
			case "applications/vnd.pdf":
			case "text/pdf":
			case "text/x-pdf":

				$extension = "pdf";
				$mode = "binary";
				break;

			case "text/html":

				$extension = "html";
				$mode = "ascii";
				break;

			case "text/plain":

				$extension = "txt";
				$mode = "ascii";
				break;

			default:

				$extension = false;
				$mode = false;
				break;

		}

		$return_array = array(
			"extension" => $extension,
			"mode" => $mode
		);

		return $return_array;

	}


	// ファイルサイズ計算

	function byte_calculation($file_size) {

		if ( $file_size > 1024 ) {
			$file_size_k = round( $file_size / 1024 , 0 );// B > KB
			if ( $file_size_k > 1024 ) {
				$file_size_m = round( $file_size_k / 1024 , 1 );// KB > MB
				return number_format($file_size_m) . "MB";
			} else {
				return number_format($file_size_k) . "KB";
			}
		} else {
			return number_format($file_size) . "Byte";
		}

	}


	// マルチバイトのファイル名をハッシュ化する関数
	// 長すぎるファイル名は短くする (MAX: 25文字)
	// その後調べてマルチバイトが含まれていたらハッシュ化を行う

	function file_name_correction($string) {


		// 長すぎる文字列は削ってしまう ** 文字を上限にする

		$string = mb_substr($string, 0, 25);


		// 不具合ありそうな文字列(半角記号)を予めアンダーバー "_" 置き換える

		// 置換え対象となる文字列群

		$replace_string_array = array(
			" ", "!", "\"", "#", "$",
			"%", "&", "'", "(", ")",
			"=", "~", "|", "^", "\\",
			"`", "{", "}", "+", "*",
			"@", "[", "]", ";", ":",
			"<", ">", "?", ",", ".",
			"/"
		);

		$string = str_replace($replace_string_array, "_", $string);


		// 特定の文字列以外使ってないか？

		if ( preg_match("/^[a-zA-Z0-9-_]+$/", $string) ) {

			// 特定の文字列以外存在しない
			return $string;

		} else {

			// 特定の文字列以外の文字が使われている
			return md5($string);// ハッシュ化
			
		}

	}


	// 一時的にローカルサーバーに保存を行い、正しい拡張子を調査する
	// MIME/TYPE から正確な拡張子から許可された拡張子か確認を行う。
	// ファイル転送時には重複しない様なファイル名を付与する
	// 一次領域に保存できたかの結果は連想配列で返す
	// とりあえずこの関数ではファイル名の付与のみとする

	function append_unique_file_name($filename) {


		$log = "";


		// 現在のファイル名を調査 (現在の拡張子を取得)

		//$extension_parts = explode('.', $array["name"]);// 拡張子を取りだすために配列に入れる (オリジナルファイルを取り出すときにも使用)
		$extension_parts = explode('.', $filename);// 拡張子を取りだすために配列に入れる (オリジナルファイルを取り出すときにも使用)
		$extension = end($extension_parts);// 配列の最後が拡張子名
		$log .= "orgn: " . $extension;


		// 正しい拡張子を調べるため、一度手元のサーバーに保存する。
		// 一時ファイルの設定

		//$temp_file_folder = "../temp_file";// 保存先 (ローカルサーバー)

		$random_string = mt_rand(10000,99999);// ランダム数値
		//$temp_file_name = $temp_file_folder . "/file_" . time() . "_" . $random_string . "." . $extension;
		$temp_file_name = time() . "_" . $random_string . "." . $extension;


		return $temp_file_name;


/*
		// オブジェクト内からファイル情報を取り出す (base64)

		$data_parts = explode(',', $array["data"], 2);// limit 2で配列を作成
		$file_data = base64_decode($data_parts[1]);// file data


		// 取り出された情報のチェック: $data_parts[0] -> data: が含まれているか

		$pattern = "/^data:/";// 正規表現用文字列
		$correct_flag = true;// true: エラー無し , false: エラー在り

		if ( !preg_match($pattern, $data_parts[0]) ) {
			//array_push($temp_error_array, "This is an illegal file... [" . $file["name"] . "]");
			$correct_flag = false;
			$log .= ", NO base64 data";
		}


		// 一時ファイル保存
		// http://php.net/manual/ja/function.fopen.php
		// http://www.findxfine.com/programming/javascript/995557527.html

		if ($correct_flag === true) {

			$fp = fopen($temp_file_name, "w");
			$fwrite_result = fwrite($fp, $file_data);
			fclose($fp);

			if ($fwrite_result) {
				$log .= ", fwrite: " . $fwrite_result;
			} else {
				$log .= ", fwrite: failure...";
				$correct_flag = false;
			}

		}


		return [$array["task"], $array["name"], $temp_file_name, $log];
*/


/*




			// ローカルサーバー保存確認

			if (count($temp_error_array) === 0 && !$fwrite_result) {
				array_push($temp_error_array, "Failed to save temporary file... [" . $file["name"] . "]");
			}


			// エラーが無ければ保存した一時ファイルから MIME TYPE を取得する
			// MIME TYPE を取得できたら $mime_type がアップロード許可された形式か確認

			if (count($temp_error_array) === 0) {
				$file_string_orgn = shell_exec('file -bi ' . escapeshellcmd($temp_file_name));
				$file_string = trim($file_string_orgn);
				$mime_type = explode("; ", $file_string);
				$extension_array = extension_search($mime_type[0]);// 拡張子が許可されているか確認
			}


			// 許可された MIME TYPE であれば 次の手順へ進む
			// アップロードしようとしているディレクトリが存在するか確認

			if (count($temp_error_array) === 0 && $extension_array["extension"] !== false) {

				// オリジナルファイル名 (拡張子を削除)

				array_pop($extension_parts);// 末尾の要素(拡張子)を削除
				$file_name_orgn = implode(".", $extension_parts);// 配列を再結合

				// ファイル名の処理 - 半角英数文字以外使用されていた場合ファイル名を変更する

				// 拡張子を除いてファイル名を修正
				// ファイル名の制限を設けて加工してゆく
				// 文字数制限 20文字未満は削除
				// 半角文字列の記号はアンダーバー(_)に一部置き換え

				$file_name = file_name_correction($file_name_orgn);

				// アップロードファイルのディレクトリ

				$upload_file_directory = $upload_root_directory . $_POST["directory"];

				// アップロードファイルパス

				$new_file_name = $file_name . "." . $extension_array["extension"];
				$upload_file_path = $upload_file_directory . "/" . $new_file_name;

				// ディレクトリの確認

				$result_directory = $instance_ftp->check_existence($upload_directory);

			} else {

				array_push($temp_error_array, "This file format is not allowed... (許可されていないファイル形式です) [" . $file["name"] . "]");

			}
*/

	}


	// ディレクトリ用に受け取ったjson変数を戻したときに配列か否か調べる
	// 配列でかつ1つ以上要素がある場合は文字列整形を行う
	// json -> array() -> "" (空の配列なら空欄を返す)
	// json -> array("aaa", "bbb") -> /aaa/bbb (要素が含まれているなら/で繋ぎ先頭にも/を付ける)

	function json_path_shaping($json) {

		if (
			is_array( json_decode($json) )// jsonから戻した変数は配列である
			&&
			count( json_decode($json) ) !== 0// jsonから戻した配列内には1つ以上値がある
		) {

			// 有効な json -> ディレクトリ情報
			// jsonから配列に戻してディレクトリ文字列を整形

			$string = "/" . implode("/", json_decode($json));

		} else {

			// 有効ではない値は空欄

			$string = "";

		}

		return $string;

	}



	// 指定のディレクトリからファイルリストを取得する
	// リストを取得する前にディレクトリが存在するか確認を行う

	function get_file_list_html_set($current_path) {


		global $instance_ftp;
		global $upload_root_directory;

		$return_html = "";
		$file_list_html = "";
		$file_list_array = array();// ファイルリスト収納先

		// ディレクトリの存在確認

		$result_existence = $instance_ftp->check_existence($upload_root_directory . $current_path);

		if ($result_existence) {

			// ディレクトリが存在すればファイル一覧を取得

			$ftp_file_array = $instance_ftp->get_list($upload_root_directory . $current_path);// ファイル一覧取得
			//$next_flag = true;

		} else {

			// ディレクトリが存在しない場合

			$return_html = '<p>Directory does not exist</p>';

		}

		// ディレクトリが存在していれば、ファイルリストのHTMLを整形

		if (count($ftp_file_array) > 0 && $ftp_file_array) {

			foreach ($ftp_file_array as $value) {

				// 取得例
				// [0] => -rw-r--r--   1 user   host      1012 Sep 22  2016 image.jpg

				// 2つ以上の半角スペースを一つにする

				$value = preg_replace("/[　\s]+/", " ", $value);

				// 半角スペース基準で文字列を分割

				$value_array = explode(" ", $value);

				// [0] パーミッション
				// [1] グループ
				// [2] ユーザー名
				// [3] サーバー名
				// [4] ファイルサイズ
				// [5] ファイルの日付
				// [6] ファイルの日付
				// [7] ファイルの日付
				// [8] ファイル名

				// ファイルかディレクトリ化の判別
				// $value_array[0] パーミッションで判断
				// drwxr-xr-x ... 先頭に "d" が付いているのはディレクトリ
				// -rw-r--r-- ... 先頭に "d" が付いていないのはファイル

				if (substr($value_array[0], 0, 1) === "d") {

					// ディレクトリ

					// $value_array[8] ファイル名で判断
					// "." ".." は無視

					if ($value_array[8] !== "." && $value_array[8] !== "..") {

						$temp_array = array(
							"name" => $value_array[8],
							"type" => "directory",
							"size" => $value_array[4]
						);
						array_push($file_list_array, $temp_array);// ファイル用配列に収納

					}

				} else {

					// ファイル

					// クロスドメイン経由でファイルを開く行為を禁止しているサーバーもあるので注意

					$temp_array = array(
						"name" => $value_array[8],
						"type" => "file",
						"size" => $value_array[4]
					);
					array_push($file_list_array, $temp_array);// ファイル用配列に収納

				}

			}// foreach ($ftp_file_array as $value)

			// # SETP 3
			// ↑でファイル一覧が取得できていれば出力開始 (ここでチェックすること)

			// 取得したオブジェクトを"type"基準でソート
			// array_multisort (PHP5.5以降)
			// http://php.net/manual/ja/function.array-multisort.php

			array_multisort(array_column($file_list_array, "type"), SORT_ASC, $file_list_array);

			// ファイル一覧出力

			$file_list_html .= '<p class="current_path"><span class="fm_list_update"><i class="fas fa-folder-open"></i></span>';
			if ($current_path) {
				$file_list_html .= $current_path;
			} else {
				$file_list_html .= "/";
			}
			$file_list_html .= '<span class="list_reload"><i class="fas fa-sync-alt"></i></span></p>' . PHP_EOL;

			$file_list_html .= '<ul class="file_list" id="file_list">' . PHP_EOL;

			// ルートディレクトリ以外の時はひと階層上に戻るボタン
			// 受け取った $current_path に情報があるか否か

			if ($current_path != "") {
				$file_list_html .= '<li><span class="move_to_upper_level"><i class="fas fa-arrow-left"></i></span></li>' . PHP_EOL;
			}

			if (count($file_list_array) === 0) {

				$file_list_html .= "<li>File not found</li>" . PHP_EOL;

			} else {

				// 取得したリストを出力

				global $upload_root_url;
				$site_upload_directory = $upload_root_url;// 仮: 現状のディレクトリ位置が挟まれます

				foreach ( $file_list_array as $array ) {

					$file_link = $site_upload_directory . $current_path . "/" . $array["name"];

					if ($array["type"] === "directory") {// ディレクトリの場合

						$file_list_html .= '<li data-type="directory" data-source="' . $file_link . '" data-name="' . $array["name"] . '" class="fm_directory">'
							//. '<span class="directory_change" data-position="' . $current_path . "/" . $array["name"] . '" title="' . "/" . $array["name"] . '"><i class="fas fa-folder-open"></i>' . $array["name"] . '</span>'
							. '<span class="directory_change" data-position="' . $array["name"] . '" title="' . $current_path . "/" . $array["name"] . '"><i class="fas fa-folder-open"></i>' . $array["name"] . '</span>'
							. '<span class="fm_rename button">rename</span><span class="fm_delete button">delete</span>'
							. "</li>" . PHP_EOL
						;

					} else {// その他 (ファイル)

						$file_list_html .= '<li data-type="file" data-source="' . $file_link . '" data-name="' . $array["name"] . '" class="fm_file" title="' . byte_calculation((int) $array["size"]) . '">'
							. '<a href="' . $file_link . '" target="_blank"><i class="fas fa-file"></i>' . $array["name"] . '</a>'
							. '<span class="fm_value_set button">set</span><span class="fm_rename button">rename</span><span class="fm_delete button">delete</span>'
							. "</li>" . PHP_EOL
						;

					}

				}

			}

			$file_list_html .= "</ul>" . PHP_EOL;

			// ディレクトリ作成ボタン, ステータスバーなど

			$file_list_html .= '<p class="status_bar"><i class="far fa-folder"></i>' . $upload_root_directory . "<b>" . $current_path . '</b><span class="directory_creation"><i class="fas fa-folder-plus"></i></span></p>' . PHP_EOL;

			$return_html = $file_list_html;

		}// if ($next_flag === true)

		return $return_html;

	}


?>