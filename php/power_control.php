<?php

// このコードの実行には www-data ユーザーに sudo reboot をパスワード無しで許可する必要があります。
// "sudo visudo" を実行し
// "www-data ALL=(ALL) NOPASSWD: /sbin/reboot"
// "www-data ALL=(ALL) NOPASSWD: /sbin/shutdown"
// を追加します。

// 再起動関数

function system_reboot( $delay = 0 ) {

	// タイムゾーン取得

	$schedule_file = PROJECT_ROOT . "/json/settings.json";
	$schedule_json = file_get_contents($schedule_file);
	$schedule_object = json_decode($schedule_json, true);

	// タイムゾーンは設定されていなくても "Asia/Tokyo" となるようにしている
	// "Asia/Tokyo" 以外に設定されていれば、対応したタイムゾーンになる
	// このソフトを日本以外で使う人は居ないとは思うけど

	$timezone_text = "UTC";

	if ( isset($schedule_object["timezone"]) ) {
		$timezone_text = $schedule_object["timezone"];
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
		//exec("echo 'sudo /sbin/reboot' | at now + " . $delay . " seconds 2>&1", $output, $return_var);
		exec("sh -c 'sleep " . $delay . " && sudo /sbin/reboot' & 2>&1", $output, $return_var);
	} else {
		exec("sudo /sbin/reboot & 2>&1", $output, $return_var);
	}

	$message = "";// これは別の場所から再起動した場合用のコールバック用

	if ( $return_var === 0 ) {// 再起動が受理された

		if ($log_storage) {
			file_put_contents($log_file, $log_text);
		}

		$message = "再起動を実行しました。";

	} else {// 何らかの要因で再起動失敗

		$message = "再起動の実行に失敗しました。<br />時間をおいて再度実行してください。<br />" . json_encode($output);

	}

	echo $message;

}

// 電源オフ関数

function system_power_off() {

	exec("sudo /sbin/shutdown -h now 2>&1", $output, $return_var);

	$message = "";// コールバック用

	if ( $return_var === 0 ) {// シャットダウンが受理された
		$message = "シャットダウンを実行しました。<br />このページを再度使用する場合は、再度端末の電源を起動してください。";
	} else {// 何らかの要因でシャットダウン失敗
		$message = "シャットダウンの実行に失敗しました。時間をおいて再度実行してください。<br />" . $output;
	}

	echo $message;

}

?>