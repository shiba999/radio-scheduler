<?php

	// 文字化け対策

	//header('Content-Type: text/html; charset=UTF-8');
	header("Content-Type: application/json; charset=UTF-8");

	require_once dirname(__FILE__) . "/__definition__.php";

	$get_file = PROJECT_ROOT . "/json/clock_owm.json";
	$get_json = file_get_contents($get_file);
	$get_object = json_decode($get_json, true);

	$output = "";// ajaxで返す情報はここへ

	// 保存中の情報を読み込み openweather.json

	$json_file = PROJECT_ROOT . "/json/openweather.json";
	$json = file_get_contents($json_file);
	$temp_array = json_decode($json, true);

	// api アクセスに必要な情報が揃っているか確認 (キーと座標)

	$api_key = $get_object["openweather_api"] ?? "";
	$latitude = (float) $get_object["latitude"] ?? "";
	$longitude = (float) $get_object["longitude"] ?? "";

	if (
		trim($api_key) !== "" && trim($latitude) !== "" && trim($longitude) !== ""
		&& is_numeric($latitude) && is_numeric($longitude)
	) {

		// isset() はキーが存在して値が空の場合は true となる
		// キーの値が空の場合の判定も必要

	} else {

		// エラーと返して終了

		echo '{"update":"error"}';
		return;

	}

	$time_stamp = time();// 現在のタイムスタンプを取得
	$update = "noupdate";

	// 強制的更新を実行するパラーメータ受信用

	$maintenance_value = "";

	if ( ! empty($_POST["maintenance"]) ) {
		$maintenance_value = $_POST["maintenance"];
	}

	//echo "<pre>";
	//echo print_r($temp_array, true);
	//echo "</pre>";

	// 上記で取得した time_stamp と現在のタイムスタンプを比較。

	$time_stamp_old = (int) $temp_array["timestamp"];
	//$time_old = date("Y-m-d H:i:s", (int) $time_stamp_old);

	// 更新する目安の時間 (秒)
	// 新旧比較して時間経過していなければ更新しない (チェックを行わない)
	// 1分 (60), 1時間 (3600), 1日 (86400)

	$update_guideline = 3600;// 60分
	//$update_guideline = 300;// 5分

	// ファイルに保存されている タイムスタンプ + 待機時間 よりも経過していた場合

	if ( $time_stamp > ($time_stamp_old + $update_guideline) ) {
		$update = "update";
	}

	// 強制更新の変数が送られた場合 (現在は使用を想定していない)

	if ( $maintenance_value === "update" ) {
		$update = "forcibly";
	}

	// ファイルに保存されている タイムスタンプ + 待機時間 よりも経過していた場合

	if ( $update != "noupdate" ) {

		// OpenWeather の API へ問合せ
		// リリースするなら api key は個別取得してもらう必要がある
		// 取得座標も任意で設定させる必要がある

		$api_url = "https://api.openweathermap.org/data/2.5/weather?lat=" . $latitude . "&lon=" . $longitude . "&APPID=" . $api_key . "&lang=ja&cnt=1";

		$owm_json = file_get_contents($api_url);// OpenWeatherのjson文字列
		$owm_object = json_decode($owm_json, true);// jsonからオブジェクト情報に戻す (true必須)

		// ライブ動画と非ライブ動画の一つのオブジェクトに収納する
		// 外部から読み込み専用のファイルへ保存する情報
		// owm_object["list"][0] を保存したい

		$return_object = array(
			"cod" => $owm_object["cod"],
			"timestamp" => $time_stamp,
			"coord" => $owm_object["coord"],
			"name" => $owm_object["name"],
			"weather" => $owm_object["weather"],
			"main" => $owm_object["main"],
			"wind" => $owm_object["wind"],
			"datetime" => date("Y-m-d H:i:s", $time_stamp),
			"update" => ""
		);

		// ファイルに書き込み

		$fp = fopen($json_file, "w");// ファイルオープン
		flock($fp, LOCK_EX);// 排他ロック (処理が終わるまで他からの読み込みは待機)
		fwrite( $fp, json_encode($return_object) );// json で保存
		flock($fp, LOCK_UN);// ロック解除
		fclose($fp);

		// 書き込み完了したら表示

		$return_object["update"] = $update;
		$output = json_encode($return_object);

	} else {// ファイルに保存されている タイムスタンプ + 待機時間 よりも経過していなかった場合

		$temp_array["update"] = "no_update";// オブジェクトに「更新はしなかった」という目印を追加
		$output = json_encode($temp_array);

	}

	echo $output;

?>