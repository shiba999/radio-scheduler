<?php

// json を受け取る場合は明示的にJSONと宣言

header("Content-Type: application/json; charset=UTF-8");

// 端末内の音声出力デバイスを取得する関数

function get_mpv_alsa_devices() {

	$output = shell_exec("mpv --audio-device=help | grep alsa 2>&1");

	//echo "<pre>";
	//echo print_r($output, true);
	//echo "</pre>";

	$lines  = preg_split('/\r\n|\r|\n/', trim($output));

	foreach ( $lines as $line ) {

		// 例: 'alsa/plughw:CARD=PCH,DEV=1' (HDA Intel PCH, ALC662 rev1 Digital/Hardware device with all software conversions)

		$pattern = "/'([^']+)'\s+\((.+)\)$/m";

		if ( preg_match($pattern, $line, $matches) ) {
			$devices[] = [
				"value" => $matches[1],// mpv --audio-device= に渡す文字列
				"label" => $matches[2],// UI表示用
			];
		}

	}

	return $devices;

}

// 取得したデバイスを主要なデバイスのみに絞り込む関数 [使用は任意]

function get_useful_audio_devices($devices) {

	$useful = array();

	foreach ($devices as $dev) {

		$value = $dev["value"];
		$label = $dev["label"];

		if ( strpos($value, "alsa/plughw:") === 0 ) {// 1. alsa/plughw:系（ハードウェア直結）
			$useful[] = $dev;
		} else if ( strpos($value, "hdmi") !== false ) {// 2. HDMI専用
			$useful[] = $dev;
		} else if ( strpos($value, "iec958") !== false ) {// 3. iec958（S/PDIF）
			$useful[] = $dev;
		}

	}

	return $useful;

}

$devices = get_mpv_alsa_devices();
//$useful = get_useful_audio_devices($devices);

echo json_encode($devices);

?>