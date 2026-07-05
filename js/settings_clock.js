import * as fnc from "../js/function.js";
import * as s_fnc from "../js/settings_function.js";

// * * * * * * * * * * * * * * * * * * * * * *
// フォーム関係の要素
// * * * * * * * * * * * * * * * * * * * * * *

const set_clock = document.getElementById("clock_form");
const openweather_api = document.getElementById("openweather_api");
const latitude = document.getElementById("latitude");
const longitude = document.getElementById("longitude");
const clock_display = document.getElementById("clock_display");
const date_size = document.getElementById("date_size");
const clock_size = document.getElementById("clock_size");
const weather_size = document.getElementById("weather_size");
const opacity = document.getElementById("opacity");
const clock_msg = document.getElementById("clock_message");
const setting_clock_set = document.getElementById("setting_clock_set");

// ↓の情報取得までフォームを触れないようにする

set_clock.style.pointerEvents = "none";
set_clock.style.opacity = "0.38";

// * * * * * * * * * * * * * * * * * * * * * *
// 現在保存中の設定情報を取得してフォーム項目に反映
// * * * * * * * * * * * * * * * * * * * * * *

async function get_clock_values() {

	const send_params_title = {
		file: "clock_owm"
	};

	const query_string_title = new URLSearchParams(send_params_title).toString();
	const clock_object = await fnc.fetch_template("../php/setting_get.php", query_string_title);

	//console.log(clock_object);

	if (clock_object.openweather_api) {
		openweather_api.value = clock_object.openweather_api;
	}

	if (clock_object.latitude) {
		latitude.value = clock_object.latitude;
	}

	if (clock_object.longitude) {
		longitude.value = clock_object.longitude;
	}

	if (clock_object.clock_display) {
		document.querySelector('input[name="clock_display"][value="' + clock_object.clock_display + '"]').checked = true;
	}

	if (clock_object.date_size) {
		date_size.value = clock_object.date_size;
	}

	if (clock_object.clock_size) {
		clock_size.value = clock_object.clock_size;
	}

	if (clock_object.weather_size) {
		weather_size.value = clock_object.weather_size;
	}

	if (clock_object.opacity) {
		opacity.value = clock_object.opacity;
	}

	if (clock_object.date_size) {
		date_size.value = clock_object.date_size;
		date_size.previousElementSibling.previousElementSibling.querySelector(".sf_value").textContent = clock_object.date_size;
	}

	if (clock_object.clock_size) {
		clock_size.value = clock_object.clock_size;
		clock_size.previousElementSibling.previousElementSibling.querySelector(".sf_value").textContent = clock_object.clock_size;
	}

	if (clock_object.weather_size) {
		weather_size.value = clock_object.weather_size;
		weather_size.previousElementSibling.previousElementSibling.querySelector(".sf_value").textContent = clock_object.weather_size;
	}

	if (clock_object.opacity) {
		opacity.value = clock_object.opacity;
		opacity.previousElementSibling.previousElementSibling.querySelector(".sf_value").textContent = clock_object.opacity;
	}

	// 情報取得完了したらフォームを触れるように復旧

	set_clock.style.pointerEvents = null;
	set_clock.style.opacity = null;

}

get_clock_values();

// <input type="range" /> 操作時に実行する関数

const update_range_value = function(event) {

	const change = event.currentTarget;// 操作されたスライダー自身

	// previousElementSibling > 一つ手前 (二つ使っている)

	const sf_value = change.previousElementSibling.previousElementSibling.querySelector('.sf_value');

	if (sf_value) {
		sf_value.textContent = change.value;
	}

};

// すべての <input type="range" /> にイベントを登録

const ranges = document.querySelectorAll(".range");

ranges.forEach( function(slider) {
	slider.addEventListener("change", update_range_value);
});


// <input type="range" /> の値をリセットする関数

const reset_range_value = function(event) {

	// 一つ下要素の値を 100 に変更

	const range = event.currentTarget.nextElementSibling;
	range.value = 100;

	// 一つ上にある値も変更

	const sf_value = event.currentTarget.previousElementSibling.querySelector('.sf_value');
	sf_value.textContent = 100;

};

const reset_buttons = document.querySelectorAll(".sf_reset");

reset_buttons.forEach( function(button) {
	button.addEventListener("click", reset_range_value);
});

// * * * * * * * * * * * * * * * * * * * * * *
// 設定項目を保存する関数
// * * * * * * * * * * * * * * * * * * * * * *

setting_clock_set.addEventListener("click", async function(event) {

	const owm_api_value = openweather_api.value;
	const latitude_value = latitude.value;
	const longitude_value = longitude.value;
    const clock_display_value = document.querySelector('input[name="clock_display"]:checked').value;
	const date_size_value = date_size.value;
	const clock_size_value = clock_size.value;
	const weather_size_value = weather_size.value;
	const opacity_value = opacity.value;

	//console.log(phpm_auth_value);

    // マルチバイト混入チェック

	const value_array_mb = new Array(owm_api_value, latitude_value, longitude_value);

	//console.log(value_array_mb);

	// 入力値のチェック (マルチバイト混入)

	let false_count = 0
	let false_value_array = new Array();

	for ( let n = 0; n < value_array_mb.length; n++ ) {

		const this_result = s_fnc.validate_input(value_array_mb[n]);

		console.log(this_result);

		if ( this_result.result === false ) {
			//console.log(value_array_mb[n]);
			false_value_array.push(value_array_mb[n]);
			false_count++;
		}

	}

	console.log(false_count);

	if ( false_count === 0 ) {

		// 保存処理へ

		const send_object = {
			openweather_api: owm_api_value,
			latitude: latitude_value,
			longitude: longitude_value,
			clock_display: clock_display_value,
			date_size: date_size_value,
			clock_size: clock_size_value,
			weather_size: weather_size_value,
			opacity: opacity_value
		};

		const send_json = JSON.stringify(send_object);

		const send_params = {
			file: "clock_owm",
			json: send_json
		};

		//console.log(send_params);

		const query_string = new URLSearchParams(send_params).toString();
		const save_result = await fnc.fetch_template( "../php/setting_set.php", query_string );

		//console.log(save_result);

		if ( save_result > 0 ) {
			clock_msg.innerHTML = "設定を保存しました。";
			get_clock_values();
		} else {
			clock_msg.innerHTML = "設定の保存に失敗しました。";
		}

	} else {

		clock_msg.innerHTML = "不正な文字列が含まれています。" + false_value_array.join(", ");

	}

});
