
	// スケジュール管理 UI の html を生成する関数

	export function generate_schedule_list_html(schedule, station, audio) {

		let schedule_html = "<table>";

		schedule_html += '<thead><tr>';
		schedule_html += '<th class="column_id">ID</th>';
		schedule_html += '<th class="column_time">Time</th>';
		schedule_html += '<th class="column_action">Action</th>';
		schedule_html += '<th class="column_channel">Channel</th>';
		schedule_html += '<th class="column_repeat">Repeat</th>';
		schedule_html += '<th class="column_week">Week</th>';
		schedule_html += '<th class="column_enabled">Enabled</th>';
		schedule_html += '<th class="column_edit">Edit</th>';
		schedule_html += '<th class="column_del">Del</th>';
		schedule_html += '</tr></thead>';

		schedule_html += "<tbody>";

		if ( schedule.length > 0 ) {

			let schedule_index = 0;

			for ( let n = 0; n < schedule.length; n++ ) {

				schedule_html += "<tr>";

				schedule_html += "<th>" + (schedule_index + 1) + "</th>";
				schedule_html += "<td>" + schedule[n]["time"] + "</td>";
				schedule_html += "<td>" + schedule[n]["action"] + "</td>";
				schedule_html += "<td>" + schedule[n]["channel"] + "</td>";
				schedule_html += "<td>" + schedule[n]["repeat"] + "</td>";
				schedule_html += "<td>" + schedule[n]["week"] + "</td>";
				schedule_html += '<td><button data-index="' + schedule_index + '" data-enabled="' + schedule[n]["enabled"] + '" class="favorite styled schedule_enab" type="button">▶</button></td>';
				schedule_html += '<td><button data-index="' + schedule_index + '" class="favorite styled schedule_edit" type="button">Edit</button></td>';
				schedule_html += '<td><button data-index="' + schedule_index + '" class="favorite styled schedule_dell" type="button">Del</button></td>';

				schedule_html += "</tr>";

				schedule_index++;

			}

		} else {

			schedule_html += "<tr>";
			schedule_html += '<td colspan="9" style="padding: 1.5em;">スケジュールが登録されていません。</td>';
			schedule_html += "</tr>";

		}

		schedule_html += "</tbody>";

		// teble 閉じる

		//schedule_html += '</th>';
		//schedule_html += "</tr>";
		//schedule_html += "</tfoot>";

		schedule_html += "</table>";

		// スケジュール編集UI

		//schedule_html += '<div id="schedule_ui" style="display: none;">';
		schedule_html += '<div id="schedule_ui">';

		//schedule_html += '<tfoot id="schedule_ui" style="display: none;">';
		//schedule_html += "<tr>";
		//schedule_html += '<th colspan="9" id="schedule_ui_box">';

		//schedule_html += '<table></table>';

		// 時間, アクション, チャンネル, 繰り返し, 曜日

		// 編集中のID (newなら新規)

		schedule_html += '<span class="edit"><span id="edit_index" data-index="new">new</span></span>';

		// time

		schedule_html += '<span class="edit partition"><input type="time" id="set_time" name="set_time" required /></span>';

		// action

		schedule_html += '<span class="edit partition">';
		schedule_html += '<input type="radio" id="play" name="action" value="play" checked /><label for="play" class="check">Play</label>';
		schedule_html += '<input type="radio" id="audio" name="action" value="audio" checked /><label for="audio" class="check">Audio</label>';
		schedule_html += '<input type="radio" id="stop" name="action" value="stop" /><label for="stop" class="check">Stop</label>';
		schedule_html += '<input type="radio" id="reboot" name="action" value="reboot" /><label for="reboot" class="check">Reboot</label>';
		schedule_html += '</span>';

		// channel (Action が play の場合)
		// station は IndexedDB に保存されたチャンネルの配列

		//console.log(station);

		schedule_html += '<span id="channel_set" class="edit partition">';

		for ( let n = 0; n < station.length; n++ ) {

			schedule_html += '<input type="radio" id="' + station[n]["id"] + '" name="channel" value="' + station[n]["id"] + '"';

			if ( n === 0 ) {
				schedule_html += " checked";
			}

			schedule_html += ' /><label for="' + station[n]["id"] + '" class="check">' + station[n]["name"] + '</label>';

		}

		schedule_html += '</span>';

		// 音声ファイル一覧 (action が audio の場合)
		// audio の中身を展開

		//console.log(audio);

		schedule_html += '<span id="audio_set" class="edit partition">';

		if ( audio.length > 0 ) {

			for ( let n = 0; n < audio.length; n++ ) {

				schedule_html += '<input type="radio" id="' + audio[n] + '" name="audio" value="' + audio[n] + '" /><label for="' + audio[n] + '" class="check">' + audio[n] + '</label>';

			}

		} else {

			schedule_html += "<p>音声ファイルが登録されていません。</p>";

		}

		schedule_html += '</span>';

		// repeat

		schedule_html += '<span class="edit partition"><input type="checkbox" id="set_repeat" name="set_repeat" /><label for="set_repeat" class="check">Repeat</label></span>';

		// week

		schedule_html += '<span id="week_set" class="edit partition">';
		schedule_html += '<input type="checkbox" id="sun" name="week" value="sun" /><label for="sun" class="check">Sunday</label>';
		schedule_html += '<input type="checkbox" id="mon" name="week" value="mon" /><label for="mon" class="check">Monday</label>';
		schedule_html += '<input type="checkbox" id="tue" name="week" value="tue" /><label for="tue" class="check">Tuesday</label>';
		schedule_html += '<input type="checkbox" id="wed" name="week" value="wed" /><label for="wed" class="check">Wednesday</label>';
		schedule_html += '<input type="checkbox" id="thu" name="week" value="thu" /><label for="thu" class="check">Thursday</label>';
		schedule_html += '<input type="checkbox" id="fri" name="week" value="fri" /><label for="fri" class="check">Friday</label>';
		schedule_html += '<input type="checkbox" id="sat" name="week" value="sat" /><label for="sat" class="check">Saturday</label>';
		schedule_html += '</span>';

		// 保存ボタン

		schedule_html += '<span id="submit_set" class="edit" style="display: none;"><button id="schedule_set" type="submit">Submit</button><span id="schedule_message"></span></span>';

		// 閉じるボタン

		schedule_html += '<span id="ui_close">close</span>';

		schedule_html += '</div>';

		// Add schedule ボタン追加

		schedule_html += '<span id="add_button_box"><button id="add_button" type="submit">Add schedule</button></span>';

		return schedule_html;

	}


	// fetch のテンプレート function() の手前に async を付けること

	export async function fetch_template(source, send, timeout = 10000) {

		let result = "";

		try {

			const controller = new AbortController();
			const timeout_id = setTimeout(() => controller.abort(), timeout);

			const response = await fetch(source, {
				method: "POST",
				headers: {
					"Content-Type": "application/x-www-form-urlencoded;charset=UTF-8"
				},
				body: send,
				signal: controller.signal
			});

			clearTimeout(timeout_id);

			if ( ! response.ok ) {
				throw new Error( "HTTP error! status: " + response.status );
			}

			// text / json の判定

			// * 左辺が falsy なら右辺を返す

			const content_type = response.headers.get("Content-Type") || "";

			//console.log(content_type);
			//console.log(content_type.includes("application/json"));

			if ( content_type.includes("application/json") ) {
				//console.log("json");
				result = await response.json();
			} else {
				//console.log("text");
				result = await response.text();
			}

			//console.log(result);

			return result;// Promiseで値を返す

		} catch (error) {

			if (error.name === "AbortError") {
				console.error("fetch_template(): The process timed out.");
				result = "timeout";
			} else {
				console.error("fetch_template(): ", error);
				result = "error";
			}

			throw error;// エラーも投げる
			return result;

		}

	}


	// ### 複数ファイルアップロード処理 ###

	export async function fetch_upload(target_files, element) {

		//console.log(target_files);

		let upload_count = 0;

		const total_files = target_files.length;// 処理するファイル数

		element.innerHTML = "アップロード処理中です... [" + upload_count + "/" + total_files + "]";

		let upload_message = new Array();
		let up_count_success = 0;
		let up_count_failed = 0;
		let promises = new Array();// Promise配列

		for ( let n = 0; n < total_files; n++ ) {

			const this_up_file = target_files[n];
			const upload_name = this_up_file.name;

			const this_promise = new Promise(async function(resolve) {

				const reader = new FileReader();

				reader.onload = async function(this_file) {

					const this_array = {
						name: upload_name,// ファイル名
						data: this_file.target.result// base64
					};

					const upload_result = await fetch_template( "./php/ajax_upload.php", "files=" + encodeURIComponent( JSON.stringify(this_array) ) );

					//console.log(upload_result);

					upload_count++;

					element.innerHTML = "アップロード処理中です... [" + upload_count + "/" + total_files + "]";

					// 成功数と失敗数をカウント

					if ( upload_result["result"] === "success" ) {
						up_count_success++;
					} else {
						up_count_failed++;
					}

					upload_message.push(
						upload_count + ". " + upload_result["name"] + " . . . " + upload_result["message"]
					);

					// すべて完了

					if ( total_files === upload_count ) {
						element.innerHTML = upload_message.join("<br />");
						//return true;
					}

					resolve(upload_result);// 各ファイルの結果を解決

				};// reader.onload

				reader.readAsDataURL(this_up_file);// 読み込み開始

			});// new Promise

			promises.push(this_promise);

		}

		const all_results = await Promise.all(promises);

		return all_results;// 全結果を返す

	}


	// アップロードしたファイルを取得する関数

	export async function get_audio_list(element) {

		const upload_result = await fetch_template( "./php/get_list_audio.php", "");

		//console.log(upload_result);

		let audio_list_html = "";
		let return_array = new Array();

		if ( upload_result.length > 0 ) {

			audio_list_html = "<ul>";

			for ( let n = 0; n < upload_result.length; n++ ) {

				const this_audio_name = upload_result[n];
				const this_audio_url = "./upload/" + encodeURI(this_audio_name);

				audio_list_html +=
					'<li><a href="' + this_audio_url + '" target="_blank">' + upload_result[n]
					+ '</a><span class="audio_del" data-name="' + encodeURI(this_audio_name) + '">Del</span><span class="audio_play" data-name="' + encodeURI(this_audio_name) + '">Play</span>' + "</li>";

			}

			audio_list_html += "</ul>";

		} else {

			audio_list_html += "<p>No audio files have been registered yet.</p>";

		}

		element.innerHTML = audio_list_html;// リスト html を表示

		return upload_result;

	}


	// PWA から端末にアクセスできなかった場合に表示させる html

	export function change_to_offline_view() {

		const html = `
<header>
	<h1>Radio Scheduler</h1>
</header>

<section class="card">

	<h2>Currently offline</h2>

	<p class="ascii_art offline">■　　　■
■　　　■
■■■■■
■　　　■
■　　　■

■■■■■
　　■
　　■
　　■
　　■

■■■■■
　　■
　　■
　　■
　　■

■■■■
■　　　■
■■■■
■
■

　■■■
■　　■■
■　■　■
■■　　■
　■■■</p>

	<h3>HTTP: 0</h3>

	<p class="kaomoji">∧＿∧
（ o . o ）
^</p>
	<p>サーバーと同じネットワーク内で使用してください。</p>
	<p style="margin: 1.5em 0;"><button onclick="window.location.reload();" type="button">リロード</button></p>

</section>

<footer>
	<p class="copy">らぢ助 - Radio Scheduler -<br />&copy; 2025 <a href="https://weblabyrinth.net" target="_blank">WebLabyrinth</a></p>
</footer>

`;

		return html;

	}


