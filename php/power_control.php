<?php

// 設定ファイル読み込み関数

function get_settings() {

	$get_file = PROJECT_ROOT . "/json/settings.json";
	$get_json = file_get_contents($get_file);
	$get_object = json_decode($get_json, true);

	return $get_object;

}

// 再起動関数

function system_reboot( $delay = 0 ) {

	// 設定取得

	$settings_object = get_settings();
	$reboot_path = $settings_object["reboot_path"];

	$message = "";// これは別の場所から再起動した場合用のコールバック用

	if ( $reboot_path != "" ) {

		// タイムゾーンは設定されていなくても "Asia/Tokyo" となるようにしている
		// "Asia/Tokyo" 以外に設定されていれば、対応したタイムゾーンになる
		// このソフトを日本以外で使う人は居ないとは思うけど

		$timezone_text = "UTC";

		if ( isset($settings_object["timezone"]) ) {
			$timezone_text = $settings_object["timezone"];
		} else {
			$timezone_text = "Asia/Tokyo";
		}

		date_default_timezone_set($timezone_text);

		// 再起動のログ生成

		$log_file = PROJECT_ROOT . "/log/reboot.log";
		$log_text = "Latest restart time: " . date("Y/m/d (D) G:i:s");
		$log_storage = false;

		//$test = "delay: " . $delay . " ---> ";

		// at は標準では root 以外許可されていなかったので sleep を使用した。

		if ( $delay > 0 ) {
			$log_storage = true;
			exec("sh -c 'sleep " . $delay . " && sudo " . $reboot_path . "' & 2>&1", $output, $return_var);
		} else {
			exec("sudo " . $reboot_path . " & 2>&1", $output, $return_var);
		}

		$message = "";// これは別の場所から再起動した場合用のコールバック用

		if ( $return_var === 0 ) {// 再起動が受理された

			if ($log_storage) {
				file_put_contents($log_file, $log_text);
			}

			$message = "再起動を実行します。";

		} else {// 何らかの要因で再起動失敗

			$message = "再起動の実行に失敗しました。<br />時間をおいて再度実行してください。<br />" . json_encode($output);

		}

	} else {

		$message = "再起動の実行に失敗しました。<br />設定パスが見つかりませんでした。";

	}

	echo $message;

}

// 電源オフ関数

function system_power_off() {

	// 設定取得

	$settings_object = get_settings();
	$shutdown_path = $settings_object["shutdown_path"];

	$message = "";

	if ( $shutdown_path != "" ) {

	exec("sudo " . $shutdown_path . " -h now 2>&1", $output, $return_var);

		$message = "";// コールバック用

		if ( $return_var === 0 ) {// シャットダウンが受理された
			$message = "シャットダウンを実行しました。<br />このページを再度使用する場合は、再度端末の電源を起動してください。";
		} else {// 何らかの要因でシャットダウン失敗
			$message = "シャットダウンの実行に失敗しました。時間をおいて再度実行してください。<br />" . json_encode($output);
		}

	} else {

		$message = "シャットダウンの実行に失敗しました。<br />設定パスが見つかりませんでした。";

	}

	echo $message;

}

?>