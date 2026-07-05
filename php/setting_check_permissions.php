<?php

// fetch で直接 json ファイルを読み込んでも良いが、PHPを経由して
// header で明示的に読み込む情報の形式を宣言すると確実性が増す

header("Content-Type: application/json; charset=UTF-8");// json を受け取る場合は明示的にJSONと宣言

$check_array = array(
	"../json/settings.json",// 基本設定
	"../json/clock_owm.json",// 時計・天気預保設定
	"../json/sendmail.json",// メール送信設定
	"../json/gmail_token.json",// Gmail API のトークン保存先
	"../log/settings.log",// オーディオパーミッションチェックで使用 (デバッグ用)
	"../log/player.log",// ラジオ・音声ファイル再生時のデバッグ用
	"../log/volume.log",// 現在の音量
	"../log/cron.log",// cron 実行ログ保存先
	"../json/schedule.json",// スケジュール保存先
	"../json/clock_action.json",// 時計画面のアクション登録用
	"../socket",// Socket 領域
	"../upload_tmp",// アップロード処理領域
	"../upload",// アップロードファイル保存先
	"../json/openweather.json"// 天気予報 api 受信情報保存先
);
$result_array = array();

foreach ( $check_array as $target ) {

	$temp = array(
		"result" => false,
		"target" => $target
	);

	if ( is_writable($target) ) {
		$temp["result"] = true;
	} else {
		$temp["result"] = false;
	}

	array_push($result_array, $temp);

}

echo json_encode($result_array);

?>