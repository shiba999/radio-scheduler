<?php

header("Content-Type: application/json; charset=UTF-8");// json を受け取る場合は明示的にJSONと宣言

$log_file = "../log/radio.log";

$return_object = array(
	"status" => "stop",
	"channel" => ""
);

if ( ! file_exists($log_file) || filesize($log_file) === 0 ) {

	// 1. ログファイルが存在しない > 再生経験なし
	// 2. ログファイルが 0 バイト > 停止して内容が削除されている

	//echo "停止中";
	$return_object["status"] = "stop";

} else {

	// ログファイルに何らかの情報がある

	$log = file_get_contents($log_file);

	if ( strpos($log, "error:") !== false ) {

		// 何らかの原因で再生に失敗している

		$return_object["status"] = "stop";

	} else if ( strpos( $log, "Starting player:" ) !== false ) {

		// ログファイルに再生開始のログが存在する
		// しかしその後終了している場合もある

		if ( strpos( $log, "Player closed" ) !== false ) {

			// Player closed が存在する
			// 再生終了済み: 指定した再生時間が超過した

			$return_object["status"] = "stop";

		} else {

			// Player closed が存在しない
			// 再生中

			$return_object["status"] = "playing";

			// ログから再生中のチャンネルを抽出する

			if ( preg_match('/http:\/\/radiko\.jp\/#!\/live\/([^\r\n]+)/i', $log, $matches) ) {
				$return_object["channel"] = $matches[1];
			}

		}

	} else {

		// 他の要因は停止という事で

		$return_object["status"] = "stop";

	}

}

echo json_encode($return_object);

?>