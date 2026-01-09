
	//console.log("functions.js ++++++");

	// よく使う関数関係

	// JavaScript 版 sleep

	export const js_sleep = function(time) {
		return new Promise(function(resolve) {
			setTimeout(resolve, time)
		});
	}

	// メッセージのフェードイン class 付与

	const msg_box = document.getElementById("message_box");
	const msg_bg = document.getElementById("message_bg");
	const msg_disp = document.getElementById("message_display");

	export function msg_fade_in(message) {
		msg_disp.innerHTML = message;
		msg_box.classList.add("is-visible");
	}

	// メッセージのフェードアウト class 削除

	export function msg_fade_out() {
		msg_box.classList.remove("is-visible");
	}

	// メッセージを背景クリックで非表示に

	msg_bg.addEventListener("click", async function() {
		msg_fade_out();
	});


	// チャンネル一覧のhtmlを stations から生成する関数

	export function generate_channels_html(stations) {

		let html = "";

		for ( let n = 0; n < stations.length; n++ ) {

			let this_id = stations[n]["id"];
			let this_name = stations[n]["name"];
			let this_logo = stations[n]["logo"];
			let this_site = stations[n]["href"];

			html += '<div class="station" id="' + this_id + '_box">';
			html += '<span class="logo"><img src="' + this_logo + '" title="' + this_name + '" alt="' + this_name + '" /></span>';
			html += '<button class="radiko_ch" data-ch="' + this_id + '">Play</button>';
			html += '<a href="' + this_site + '" target="_blank">Website</a>';
			html += '<span class="in_process" style="display: none;"><img src="./image/standby.gif" alt="in process" /></span>';
			html += '</div>';

		}

		return html;

	}

	// 再生中のラジオの背景を一括でクリアにする

	export function stations_bg_initialization() {

		// 再生中のアニメーションアイコンの非表示

		const process_e_array = document.getElementsByClassName("in_process");

		for ( let n = 0; n < process_e_array.length; n++ ) {
			process_e_array[n].style.display = "none";
		}

		// 背景色削除

		const station_e_array = document.getElementsByClassName("station");

		for ( let n = 0; n < station_e_array.length; n++ ) {
			station_e_array[n].style.backgroundColor = null;
		}

		// ボタンのスタイル削除

		const play_btn_e_array = document.getElementsByClassName("radiko_ch");

		for ( let n = 0; n < play_btn_e_array.length; n++ ) {
			play_btn_e_array[n].removeAttribute("style");
		}

	}

	// 停止した場合の処理
	// フッターの局情報と一覧を初期化する

	export function init_play_info() {

		// チャンネル一覧で再生済みとなっている要素の背景を初期化

		stations_bg_initialization();

		// 停止中という表示

		document.getElementById("play_info").innerHTML = '<span class="station_name">只今ラジオは停止中です</span>';

		// 停止ボタンは非表示

		radiko_stop.style.display = "none";

	}

	// 局のIDから諸情報を検索して表示を行う関数
	// 一覧から再生中の局の領域への変更も行ってみる
	// 局のIDを引数に入れれば動くようにしてみる

	export function show_playing_info(station_id, channel_object) {

		//console.log(playback_status_object.channel);

		let this_name = "";
		let this_image = "";
		let hit_id = false;// チャンネルIDがヒットしたか否か

		// 再生中の場合は手前でエリアIDから取得した各チャンネルの情報の中から該当するチャンネル名やサムネイルを拾い上げる

		for ( let n = 0; n < channel_object.length; n++ ) {

			if ( channel_object[n]["id"] == station_id ) {
				this_name = channel_object[n]["name"];
				this_image = channel_object[n]["logo"];
				hit_id = true;
			}

		}

		if ( hit_id ) {

			// 受け取ったIDの情報が stations 内に存在した場合

			// チャンネル情報をフッターに表示

			const html = '<span class="station_logo"><img src="' + this_image + '" alt="' + this_name + '" /></span><span class="station_name">' + this_name + '</span>';

			document.getElementById("play_info").innerHTML = html;

			// チャンネル一覧で再生済みとなっている要素の背景を初期化

			stations_bg_initialization();

			// 再生中のチャンネル要素の背景を変更

			console.log("*** Currently playing: " + station_id + " ***");

			const this_box = document.getElementById( station_id + "_box" );

			this_box.style.backgroundColor = "#999";

			// 再生中のチャンネル要素にアニメーションを表示

			const this_process = this_box.querySelector(".in_process");// 親要素内の .in_process を取得
			this_process.style.display = "flex";// .in_process 表示

			// 該当チャンネルのボタン class="radiko_ch" をクリック不可に変更
			// 色なども変更

			const this_play_btn = this_box.getElementsByClassName("radiko_ch");
			this_play_btn[0].style.pointerEvents = "none";
			this_play_btn[0].style.opacity = "0.15";
			this_play_btn[0].style.background = "#fff";
			this_play_btn[0].style.color = "#000";

			// 停止ボタンは表示させる

			radiko_stop.style.display = "inline-block";

		} else {

			// 受け取ったIDの情報が stations 内に存在しなかった場合

			init_play_info();

		}

	}

	// radiko 現在再生中か・どの局を再生中かを確認する

	export async function radiko_status_check(channel_object) {

		const playback_status_json = await fetch("./php/radiko_playback_status.php");
		const playback_status_object = await playback_status_json.json();

		//console.log(playback_status_object);

		// 再生している時としていない場合の表示切替

		if ( playback_status_object.status === "playing" ) {
			const this_id = playback_status_object.channel;
			show_playing_info(this_id, channel_object);
		} else {
			init_play_info();
		}

	}




	// 電源操作関係で使用
	// 経過秒数を表示させる

/*	export let device_started = false;

	export function standby_time_display() {

		console.log("*** standby time display ***");

		let seconds = 0;

		const interval_id = setInterval(function() {

			seconds++;

			console.log(seconds);

			// device_started === true なら停止
			// 時間かかりすぎたら停止しておこう (5分目安)

			if ( seconds >= 300 ) {

				clearInterval(interval_id);// タイマー停止
				msg_fade_in("起動に時間がかかりすぎているようです。<br />端末の電源を確認してください。");

			} else if ( device_started === true ) {

				clearInterval(interval_id);// タイマー停止
				msg_fade_out();

			} else {

				msg_fade_in("起動待機中: " + seconds + "秒経過");

			}

		}, 1000);

	}

	// 今は使ってないテスト関数

	export function test_func() {
		console.log("test_func: 999999");
	}
*/

