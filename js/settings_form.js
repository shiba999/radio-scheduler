import * as fnc from "../js/function.js";
import * as s_fnc from "../js/settings_function.js";

// * * * * * * * * * * * * * * * * * * * * * *
// フォーム関係の要素
// * * * * * * * * * * * * * * * * * * * * * *

const set_form = document.getElementById("set_form");
const streamlink_path = document.getElementById("streamlink_path");
const sl_plugin_path = document.getElementById("sl_plugin_path");
const player = document.getElementById("player");
const player_path = document.getElementById("player_path");
const amixer_path = document.getElementById("amixer_path");
const reboot_path = document.getElementById("reboot_path");
const shutdown_path = document.getElementById("shutdown_path");
const playback_device = document.getElementById("playback_devices");
const playback_options = document.getElementById("playback_options");
const timezone = document.getElementById("timezone");
const set_msg = document.getElementById("setting_message");
const setting_set = document.getElementById("setting_set");

// ↓の情報取得までフォームを触れないようにする

set_form.style.pointerEvents = "none";
set_form.style.opacity = "0.38";

// 再生に利用可能なデバイスを取得

const audio_device_object = await fnc.fetch_template("../php/get_audio_devices.php");

//console.log(audio_device_object);

// * * * * * * * * * * * * * * * * * * * * * *
// デバイス用 html を生成
// * * * * * * * * * * * * * * * * * * * * * *

if ( audio_device_object ) {

	let audio_device_select = '<option value="">音声再生に使用したいデバイスを選択してください</option>';

	for ( let n = 0; n < audio_device_object.length; n++ ) {
		audio_device_select += '<option value="' + audio_device_object[n]["value"] + '">' + audio_device_object[n]["label"] + '</option>';
	}

	playback_device.innerHTML = audio_device_select;

} else {

	playback_device.innerHTML = '<option value="">音声デバイスを取得できませんでした。</option>';

}

// * * * * * * * * * * * * * * * * * * * * * *
// 現在保存中の設定情報を取得してフォーム項目に反映
// * * * * * * * * * * * * * * * * * * * * * *

async function get_setting_values() {

	const send_params_title = {
		file: "settings"// ファイル名を指定
	};

	const query_string_title = new URLSearchParams(send_params_title).toString();
	let setting_object = await fnc.fetch_template("../php/setting_get.php", query_string_title);

	//console.log(setting_object);

	if (setting_object == null) {
		setting_object = new Array();
	}

	if (setting_object.streamlink_path) {
		streamlink_path.value = setting_object.streamlink_path;
	}

	if (setting_object.sl_plugin_path) {
		sl_plugin_path.value = setting_object.sl_plugin_path;
	}

	if (setting_object.player) {
		player.value = setting_object.player;
	}

	if (setting_object.player_path) {
		player_path.value = setting_object.player_path;
	}

	if (setting_object.amixer_path) {
		amixer_path.value = setting_object.amixer_path;
	}

	if (setting_object.reboot_path) {
		reboot_path.value = setting_object.reboot_path;
	}

	if (setting_object.shutdown_path) {
		shutdown_path.value = setting_object.shutdown_path;
	}

	if (setting_object.playback_device) {
		playback_device.value = setting_object.playback_device;// e.value で select を選択状態にできるのか...
	}

	if (setting_object.playback_options) {
		playback_options.value = setting_object.playback_options;
	}

/*	if (setting_object.openweather_api) {
		openweather_api.value = setting_object.openweather_api;
	}

	if (setting_object.latitude) {
		latitude.value = setting_object.latitude;
	}

	if (setting_object.longitude) {
		longitude.value = setting_object.longitude;
	}

	if ( setting_object.clock_display == "" || setting_object.clock_display == "true" ) {
		setting_object.clock_display = "full";// 旧バージョンからのコンバート
	}

	if (setting_object.clock_display) {
		document.querySelector('input[name="clock_display"][value="' + setting_object.clock_display + '"]').checked = true;
	}

	if ( setting_object.date_size != "" ) {
		date_size.value = setting_object.date_size;
		date_size.previousElementSibling.previousElementSibling.querySelector(".sf_value").textContent = setting_object.date_size;
	}

	if (setting_object.clock_size) {
		clock_size.value = setting_object.clock_size;
		clock_size.previousElementSibling.previousElementSibling.querySelector(".sf_value").textContent = setting_object.clock_size;
	}

	if (setting_object.weather_size) {
		weather_size.value = setting_object.weather_size;
		weather_size.previousElementSibling.previousElementSibling.querySelector(".sf_value").textContent = setting_object.weather_size;
	}

	if (setting_object.opacity) {
		opacity.value = setting_object.opacity;
		opacity.previousElementSibling.previousElementSibling.querySelector(".sf_value").textContent = setting_object.opacity;
	}*/

	if (setting_object.timezone) {
		timezone.value = setting_object.timezone;
	}

	// 情報取得完了したらフォームを触れるように復旧

	set_form.style.pointerEvents = null;
	set_form.style.opacity = null;

}

get_setting_values();

// * * * * * * * * * * * * * * * * * * * * * *
// 設定項目を保存する関数
// * * * * * * * * * * * * * * * * * * * * * *

setting_set.addEventListener("click", async function(event) {

	const sl_path_value = streamlink_path.value;
	const sl_plugin_path_value = sl_plugin_path.value;
	const player_value = player.value;
	const p_path_value = player_path.value;
	const amixer_path_value = amixer_path.value;
	const reboot_path_value = reboot_path.value;
	const shutdown_path_value = shutdown_path.value;
	const playback_device_value = playback_device.value;
	const playback_options_value = playback_options.value;
/*	const openweather_api_value = openweather_api.value;
	const latitude_value = latitude.value;
	const longitude_value = longitude.value;
	const clock_display_value = document.querySelector('input[name="clock_display"]:checked').value;
	const date_size_value = date_size.value;
	const clock_size_value = clock_size.value;
	const weather_size_value = weather_size.value;
	const opacity_value = opacity.value;*/
	const timezone_value = timezone.value;

	//const value_array = new Array(sl_path_value, player_value, p_path_value, amixer_path_value, reboot_path_value, shutdown_path_value, playback_options_value, openweather_api_value, latitude_value, longitude_value, timezone_value);
	const value_array = new Array(sl_path_value, player_value, p_path_value, amixer_path_value, reboot_path_value, shutdown_path_value, playback_options_value, timezone_value);

	//console.log(value_array);

	// 入力値のチェック (マルチバイトが混入していないか確認)

	let false_count = 0
	let false_value_array = new Array();

	for ( let n = 0; n < value_array.length; n++ ) {

		const this_result = s_fnc.validate_input(value_array[n]);

		//console.log(this_result);

		if ( this_result.result === false ) {
			//console.log(value_array[n]);
			false_value_array.push(value_array[n]);
			false_count++;
		}

	}

	if ( false_count === 0 ) {

		// 保存処理へ

		const send_object = {
			streamlink_path: sl_path_value,
			sl_plugin_path: sl_plugin_path_value,
			player: player_value,
			player_path: p_path_value,
			amixer_path: amixer_path_value,
			reboot_path: reboot_path_value,
			shutdown_path: shutdown_path_value,
			playback_device: playback_device_value,
			playback_options: playback_options_value,
/*			openweather_api: openweather_api_value,
			latitude: latitude_value,
			longitude: longitude_value,
			clock_display: clock_display_value,
			date_size: date_size_value,
			clock_size: clock_size_value,
			weather_size: weather_size_value,
			opacity: opacity_value,*/
			timezone: timezone_value
		};

		const send_json = JSON.stringify(send_object);

		const send_params = {
			file: "settings",
			json: send_json
		};

		console.log(send_params);

		const query_string = new URLSearchParams(send_params).toString();
		const save_result = await fnc.fetch_template( "../php/setting_set.php", query_string );

		//console.log(save_result);

		if ( save_result > 0 ) {
			set_msg.innerHTML = "設定を保存しました。";
			get_setting_values();
		} else {
			set_msg.innerHTML = "設定の保存に失敗しました。";
		}

	} else {

		set_msg.innerHTML = "不正な文字列が含まれています。" + false_value_array.join(", ");

	}

});
