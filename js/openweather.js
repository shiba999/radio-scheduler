

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

	// 時計表示画面用アクションを実行する関数

	async function execute_action(fnc) {

		const cca_object = await fnc.fetch_template("./php/clock_action_exe.php");

		//console.log(cca_object);

		// reload が含まれていたらブラウザをリロード
		// 受信する際にはリロード要素を json から削除しないと
		// 無限リロード編に陥ってしまうため注意

		if ( cca_object.includes("reload") ) {
			window.location.reload(true);
		}

	}

	// openweathermap から天気予報情報を取得する関数
	// キャッシュ保存されているので 30分以内のアクセスはキャッシュからの取得となる
	// 使用している api は 2.5 を使用していますが、現在 3.0 が出ています。
	// https://openweathermap.org/

	export async function get_openweather(arg_var, arg_fnc) {

		const ow_object = await arg_fnc.fetch_template("./php/get_openweather_json.php");

		//console.log(ow_object);

		if ( ow_object.update != "error" )	{

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

			// Full

			//arg_var.e.cw_temp.innerHTML = temp;
			//arg_var.e.cw_temp_max.innerHTML = temp_max;
			//arg_var.e.cw_temp_min.innerHTML = temp_min;
			//arg_var.e.cw_icon.style.backgroundImage = "url(https://openweathermap.org/img/wn/" + weather.icon + "@4x.png)";
			//arg_var.e.cw_description.innerHTML = weather.description;
			//arg_var.e.cw_position.innerHTML = weather_name;
			//arg_var.e.cw_wind_deg.innerHTML = deg_to_direction( Number(wind_deg) );
			//arg_var.e.cw_wind_deg.title = Number(wind_deg);
			//arg_var.e.cw_wind_speed.innerHTML = wind_speed_fix;

			arg_var.e.cw_weather.innerHTML = '<span class="fs_weather">'
				+ '<span class="temp_set">'
					+ '<span class="cw_temp">' + temp + '</span><span class="celsius_big">℃</span>'
					+ '<span class="portrait"><br></span>'
					+ '<span class="cw_temp_max">' + temp_max + '</span><span class="celsius">℃</span>'
					+ '<span class="cw_temp_min">' + temp_min + '</span><span class="celsius">℃</span>'
				+ '</span>'
				+ '<span class="weather_set">'
					+ '<span class="cw_icon" style="background-image: url(&quot;https://openweathermap.org/img/wn/' + weather.icon + '@4x.png&quot;);"></span>'
					+ '<span class="cw_description">' + weather.description + '</span>'
				+ '</span>'
				+ '<span class="wind_set">'
					+ '<span class="cw_position">' + weather_name + '</span>'
					+ '<span class="cw_wind_deg" title="140">' + deg_to_direction( Number(wind_deg) ) + '</span><span class="cw_wind_speed">' + wind_speed_fix + '</span><span class="speed">m/s</span>'
				+ '</span>'
			+ '</span>';

			// Mid

			arg_var.e.cw_weather_mid.innerHTML = '<span class="fs_weather">'
				+ '<span class="mid_temp">' + temp + '</span>'
				+ '<span class="mid_celsius">&#8451;</span>'
				+ '<span class="mid_icon" style="background-image: url(https://openweathermap.org/img/wn/' + weather.icon + '@4x.png);"></span>'
				+ '<span class="mid_position">' + weather_name + '</span>'
			+ '</span>';

		}

	}

	let time_storage = false;
	let date_storage = false;
	let started = false;

	export function time_and_date(arg_var, arg_fnc, ini) {

		//console.log(ini.type);

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

		// 日付フォーマット更新関数

		function date_update() {

			// Full

			//arg_var.e.cw_reiwa.innerText = "Reiwa." + reiwa_year_number;
			//arg_var.e.cw_year.innerText = my_year;
			//arg_var.e.cw_month.innerText = my_month_str;
			//arg_var.e.cw_day.innerText = my_day;
			//arg_var.e.cw_youbi.innerText = my_youbi_str;

			arg_var.e.cw_date.innerHTML = '<span class="fs_date">'
				+ '<span class="month_day_set">'
					+ '<span class="month_day_box">'
						+ '<span class="cw_month">' + my_month_str + '</span>'
						+ '<span class="cw_day">' + my_day + '</span>'
						+ '<span class="cw_youbi">' + my_youbi_str + '</span>'
					+ '</span>'
				+ '</span>'
				+ '<span class="year_set">'
					+ '<span class="cw_reiwa">Reiwa.' + reiwa_year_number + '</span>'
					+ '<span class="cw_year">' + my_year + '</span>'
				+ '</span>'
			+ '</span>';

			// Mid

			// 日付の桁数合わせ 常に2桁

			if ( String(this_month).length === 1 ) {
				this_month = "0" + String(this_month);
			}

			if ( String(my_day).length === 1 ) {
				my_day = "0" + String(my_day);
			}

			arg_var.e.cw_date_mid.innerHTML = '<span class="fs_date">'
				+ '<span class="mid_year">' + my_year + '</span>'
					+ '<span class="mid_slash">/</span>'
					+ '<span class="mid_month">' + this_month + '</span>'
					+ '<span class="mid_slash">/</span>'
					+ '<span class="mid_day">' + my_day + '</span>'
					//+ '<span class="mid_brackets_l">(</span>'
					+ '<span class="mid_youbi">' + my_youbi_str + '</span>'
					//+ '<span class="mid_brackets_r">)</span>'
				+ '</span>'
			+ '</span>';

			// 土曜日と日曜日は文字の色を変更
			// .mid_youbi

			if ( my_youbi_str == "Sat" ) {

				//arg_var.e.cw_youbi.style.color = "#10bcf0";
				const cw_youbi = document.querySelector(".cw_youbi");
				const mid_youbi = document.querySelector(".mid_youbi");
				cw_youbi.style.color = "#10bcf0";
				mid_youbi.style.color = "#10bcf0";

			} else if ( my_youbi_str == "Sun" ) {

				//arg_var.e.cw_youbi.style.color = "#ef6109";
				const cw_youbi = document.querySelector(".cw_youbi");
				const mid_youbi = document.querySelector(".mid_youbi");
				cw_youbi.style.color = "#ef6109";
				mid_youbi.style.color = "#ef6109";

			}

		}

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

		// 時間更新

		//arg_var.e.cw_hour.innerText = my_hour;
		//arg_var.e.cw_minute.innerText = my_minute;
		//arg_var.e.cw_second.innerText = my_second;

		arg_var.e.cw_clock.innerHTML = '<span class="fs_clock">'
			+ '<span class="cw_hour">' + my_hour + '</span>'
			+ '<span class="colon">:</span>'
			+ '<span class="cw_minute">' + my_minute + '</span>'
			+ '<span class="colon portrait">:</span>'
			+ '<span class="cw_second" class="portrait">' + my_second + '</span>'
		+ '</span>';

		// 日付の my_day を監視し、保存されている date_storage と比較して
		// 変化があった場合は日付 (年-月-日-曜日) を更新する (1日に一回更新)
		// 但し表示タイプが mini の場合は時計のみなので実行しない。

		let date_num = Number(my_day);

		if ( date_num !== date_storage && ini.type !== "mini" ) {
			date_update();
		}

		date_storage = date_num;

		// 時間の my_hour を監視し、保存されている time_storage と比較して
		// 変化があった場合は天気予報を更新する (1時間に一回更新)
		// 但し表示タイプが mini の場合は時計のみなので実行しない。
		// [追加] 時計画面用アクションが設定されている場合は実行する

		let hour_num = Number(my_hour);

		if ( hour_num !== time_storage ) {

			// アクションが登録されていれば実行

			execute_action(arg_fnc);

			// 天気予報の更新 (mini以外)

			if ( ini.type !== "mini" ) {
				get_openweather(arg_var, arg_fnc);		
			}

		}

		time_storage = hour_num;

		// 起動時にスタイル調整して画面を表示

		if ( started === false ) {

			// スタイル (文字の大きさ) の調整

			const fs_sheet = font_size.sheet;

			for ( const rule of fs_sheet.cssRules ) {

				if ( rule.selectorText === ".fs_date" ) {
					rule.style.fontSize = ini.date_size + "%";
				}

				if ( rule.selectorText === ".fs_clock" ) {
					rule.style.fontSize = ini.clock_size + "%";
				}

				if ( rule.selectorText === ".fs_weather" ) {
					rule.style.fontSize = ini.weather_size + "%";
				}

			}

			console.log(fs_sheet);

			arg_var.e.cw_box.style.opacity = ini.opacity;

			started = true;

		}

	}

	//time_and_date();// まず実行 > その後一定時間毎に実行
	//setInterval("time_and_date()", 1000);// 1000ms > 1秒

