
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


	export function test_func() {
		console.log("test_func: 999999");
	}


