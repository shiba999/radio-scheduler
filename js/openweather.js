

	// ケルビンを摂氏に変換して少数1桁の文字列に変換して返す関数

	function kelvin_to_celsius(kelvin) {
		return (kelvin - 273.15).toFixed(1);
	}

	// 角度を矢印に変換して返す関数

	function deg_to_direction(deg) {
		const arrows8 = ['↑','↗','→','↘','↓','↙','←','↖'];
		const index = Math.round(deg / 45) % 8;
		return arrows8[index];
	}

	// openweathermap から天気予報情報を取得する関数
	// キャッシュ保存されているので 30分以内のアクセスはキャッシュからの取得となる
	// 使用している api は 2.5 を使用していますが、現在 3.0 が出ています。
	// https://openweathermap.org/

	export async function get_openweather(arg_var, arg_fnc) {

		const ow_object = await arg_fnc.fetch_template("./php/get_openweather_json.php");

		//console.log(ow_object);

		if ( ow_object.update === "error" ) {

			// 設定で key や座標が設定されていない場合は error が返ってくる
			// その場合は天気予報要素を非表示させて微調整

			document.getElementsByClassName("cw_weather")[0].style.display = "none";
			arg_var.e.cw_box.style.paddingBottom = "15%";

			return;// そしてここで終わり

		}

		const temp_set = ow_object.main;
		const weather = ow_object.weather[0];
		const wind = ow_object.wind;

		const temp = kelvin_to_celsius(temp_set.temp);// 気温
		const temp_max = kelvin_to_celsius(temp_set.temp_max);// 最高気温
		const temp_min = kelvin_to_celsius(temp_set.temp_min);// 最低気温
		const humidity = temp.humidity;// 「外気」の湿度 (%)
		//const pop = owm.pop;// 降水確率 (%)
		const weather_name = ow_object.name;// 取得位置
		const wind_speed = wind.speed;// 風速 (m/s)
		const wind_deg = wind.deg;// 風向 (度)

		let weather_html = "";

		// 風速: 小数点一位でラウンド

		const wind_speed_fix = Math.round( wind.speed * 10 ) / 10;

		//console.log(ow_object);
		console.log(
			"[openweather] " + ow_object.datetime + ": " + weather.description
			+ " (" + ow_object.cod + ") " + ow_object.update
		);

		// 各値を表示

		arg_var.e.cw_temp.innerHTML = temp;
		arg_var.e.cw_temp_max.innerHTML = temp_max;
		arg_var.e.cw_temp_min.innerHTML = temp_min;
		arg_var.e.cw_icon.style.backgroundImage = "url(https://openweathermap.org/img/wn/" + weather.icon + "@4x.png)";
		arg_var.e.cw_description.innerHTML = weather.description;
		arg_var.e.cw_position.innerHTML = weather_name;
		arg_var.e.cw_wind_deg.innerHTML = deg_to_direction( Number(wind_deg) );
		arg_var.e.cw_wind_deg.title = Number(wind_deg);
		arg_var.e.cw_wind_speed.innerHTML = wind_speed_fix;

	}

	let time_storage = false;

	export function time_and_date(arg_var, arg_fnc) {

		// 時間取得

		let dateObj = new Date();
		let my_year = dateObj.getFullYear();
		let my_month = dateObj.getMonth();
		let this_month = my_month + 1;
		let my_day = dateObj.getDate();
		let my_youbi = dateObj.getDay();
		let my_hour = dateObj.getHours();
		let my_minute = dateObj.getMinutes();
		let my_second = dateObj.getSeconds();
		let heisei_year_string = String(my_year).slice(2);
		let heisei_year_number = Number(heisei_year_string) + 12;
		let reiwa_year_string = String(my_year).slice(2);
		let reiwa_year_number = Number(heisei_year_string) - 18;

		//let my_youbi_str = ["日", "月", "火", "水", "木", "金", "土"][my_youbi];// 曜日 (文字列表記)
		let my_youbi_str = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"][my_youbi];// 曜日 (文字列表記)
		let my_month_str = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"][my_month];

		// 時刻の桁数合わせ 常に2桁

		if ( String(my_hour).length === 1 ) {
			my_hour = "0" + String(my_hour);
		}

		if ( String(my_minute).length === 1 ) {
			my_minute = "0" + String(my_minute);
		}

		if ( String(my_second).length === 1 ) {
			my_second = "0" + String(my_second);
		}

		// 日付更新

		arg_var.e.cw_reiwa.innerText = "Reiwa." + reiwa_year_number;
		arg_var.e.cw_year.innerText = my_year;
		arg_var.e.cw_month.innerText = my_month_str;
		arg_var.e.cw_day.innerText = my_day;
		arg_var.e.cw_youbi.innerText = my_youbi_str;

		// 土曜日と日曜日は文字の色を変更

		if ( my_youbi_str == "Sat" ) {
			arg_var.e.cw_youbi.style.color = "#10bcf0";
		} else if ( my_youbi_str == "Sun" ) {
			arg_var.e.cw_youbi.style.color = "#ef6109";
		}

		// 時間更新

		arg_var.e.cw_hour.innerText = my_hour;
		arg_var.e.cw_minute.innerText = my_minute;
		arg_var.e.cw_second.innerText = my_second;

		// 時間の my_hour を監視し、保存されている time_storage と比較して
		// 変化があった場合は天気予報を更新する (1時間に一回更新)

		let hour_num = Number(my_hour);

		if ( hour_num !== time_storage ) {
			get_openweather(arg_var, arg_fnc);
		}

		time_storage = hour_num;

	}

