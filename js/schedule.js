

	// スケジュール管理 UI の html を生成する関数

	//function generate_schedule_list_html(schedule, station, audio) {
	function generate_schedule_list_html(schedule, arg_var) {

		let html = "<table>";

		html += '<thead><tr>';
		html += '<th class="column_id">ID</th>';
		html += '<th class="column_time">Time</th>';
		html += '<th class="column_action">Action</th>';
		html += '<th class="column_channel">Channel</th>';
		html += '<th class="column_volume">Volume</th>';
		html += '<th class="column_repeat">Repeat</th>';
		html += '<th class="column_week">Week</th>';
		html += '<th class="column_enabled">Enabled</th>';
		html += '<th class="column_edit">Edit</th>';
		html += '<th class="column_del">Del</th>';
		html += '</tr></thead>';

		html += "<tbody>";

		if ( schedule.length > 0 ) {

			let schedule_index = 0;

			for ( let n = 0; n < schedule.length; n++ ) {

				html += "<tr>";

				html += "<th>" + (schedule_index + 1) + "</th>";
				html += "<td>" + schedule[n]["time"] + "</td>";
				html += "<td>" + schedule[n]["action"] + "</td>";
				html += "<td>" + schedule[n]["channel"] + "</td>";
				html += "<td>" + schedule[n]["volume"] + "</td>";
				html += "<td>" + schedule[n]["repeat"] + "</td>";
				html += "<td>" + schedule[n]["week"] + "</td>";
				html += '<td><button data-index="' + schedule_index + '" data-enabled="' + schedule[n]["enabled"] + '" class="favorite styled schedule_enab" type="button">▶</button></td>';
				html += '<td><button data-index="' + schedule_index + '" class="favorite styled schedule_edit" type="button">Edit</button></td>';
				html += '<td><button data-index="' + schedule_index + '" class="favorite styled schedule_dell" type="button">Del</button></td>';

				html += "</tr>";

				schedule_index++;

			}

		} else {

			html += "<tr>";
			html += '<td colspan="10" style="padding: 1.5em;">スケジュールが登録されていません。</td>';
			html += "</tr>";

		}

		html += "</tbody>";

		// teble 閉じる

		html += "</table>";

		// スケジュール編集UI

		html += '<div id="schedule_ui">';

		// 時間, アクション, チャンネル, 繰り返し, 曜日

		// 編集中のID (newなら新規)

		html += '<span class="edit"><span id="edit_index" data-index="new">new</span></span>';

		// time

		html += '<span class="edit partition"><input type="time" id="set_time" name="set_time" required /></span>';

		// action

		html += '<span class="edit partition">';
		html += '<input type="radio" id="play" name="action" value="play" checked /><label for="play" class="check">Play</label>';
		html += '<input type="radio" id="audio" name="action" value="audio" checked /><label for="audio" class="check">Audio</label>';
		html += '<input type="radio" id="stop" name="action" value="stop" /><label for="stop" class="check">Stop</label>';
		html += '<input type="radio" id="reboot" name="action" value="reboot" /><label for="reboot" class="check">Reboot</label>';
		html += '</span>';

		// channel (Action が play の場合)
		// station は IndexedDB に保存されたチャンネルの配列

		//console.log(station);

		html += '<span id="channel_set" class="edit partition">';

		const station = arg_var.v.channels;

		for ( let n = 0; n < station.length; n++ ) {

			html += '<input type="radio" id="' + station[n]["id"] + '" name="channel" value="' + station[n]["id"] + '"';

			if ( n === 0 ) {
				html += " checked";
			}

			html += ' /><label for="' + station[n]["id"] + '" class="check">' + station[n]["name"] + '</label>';

		}

		html += '</span>';

		// 音声ファイル一覧 (action が audio の場合)
		// audio の中身を展開

		//console.log(audio);

		html += '<span id="audio_set" class="edit partition">';

		const audio = arg_var.v.audio_files;

		if ( audio.length > 0 ) {

			for ( let n = 0; n < audio.length; n++ ) {

				html += '<input type="radio" id="' + audio[n] + '" name="audio" value="' + audio[n] + '" /><label for="' + audio[n] + '" class="check">' + audio[n] + '</label>';

			}

		} else {

			html += "<p>音声ファイルが登録されていません。</p>";

		}

		html += '</span>';

		// volume

		html += '<span id="volume_set" class="edit partition"><input type="range" id="set_volume" name="set_volume" min="0" max="100" value="80" /><label for="set_volume">Volume</label></span>';

		// repeat

		html += '<span class="edit partition"><input type="checkbox" id="set_repeat" name="set_repeat" /><label for="set_repeat" class="check">Repeat</label></span>';

		// week

		html += '<span id="week_set" class="edit partition">';
		html += '<input type="checkbox" id="sun" name="week" value="sun" /><label for="sun" class="check">Sunday</label>';
		html += '<input type="checkbox" id="mon" name="week" value="mon" /><label for="mon" class="check">Monday</label>';
		html += '<input type="checkbox" id="tue" name="week" value="tue" /><label for="tue" class="check">Tuesday</label>';
		html += '<input type="checkbox" id="wed" name="week" value="wed" /><label for="wed" class="check">Wednesday</label>';
		html += '<input type="checkbox" id="thu" name="week" value="thu" /><label for="thu" class="check">Thursday</label>';
		html += '<input type="checkbox" id="fri" name="week" value="fri" /><label for="fri" class="check">Friday</label>';
		html += '<input type="checkbox" id="sat" name="week" value="sat" /><label for="sat" class="check">Saturday</label>';
		html += '</span>';

		// 保存ボタン

		html += '<span id="submit_set" class="edit" style="display: none;"><button id="schedule_set" type="submit">Submit</button><span id="schedule_message"></span></span>';

		// 閉じるボタン

		html += '<span id="ui_close">close</span>';

		html += '</div>';

		// Add schedule ボタン追加

		html += '<span id="add_button_box"><button id="add_button" type="submit">スケジュール追加</button></span>';

		return html;

	}

	// * * * regist_schedule_event() の中で実行されるイベント * * *

	// time に変化があるごとに発火 > submit の表示 / 非表示

	function set_time_change(event) {

		//console.log("time");

		if ( event.target.value !== "" ) {
			document.getElementById("submit_set").style.display = "Block";
		} else {
			document.getElementById("submit_set").style.display = "none";
		}

	}

	// action に変化があるごとに発火 > channel の表示 / 非表示

	function set_action_change(event) {

		//console.log("action");

		if ( event.target.value === "play" ) {
			document.getElementById("channel_set").style.display = "block";
			document.getElementById("audio_set").style.display = "none";
			document.getElementById("volume_set").style.display = "block";
		} else if ( event.target.value === "audio" ) {
			document.getElementById("channel_set").style.display = "none";
			document.getElementById("audio_set").style.display = "block";
			document.getElementById("volume_set").style.display = "block";
		} else {
			document.getElementById("channel_set").style.display = "none";
			document.getElementById("audio_set").style.display = "none";
			document.getElementById("volume_set").style.display = "none";
		}

	}

	// repeat に変化があるごとに発火 > week の表示 / 非表示

	function set_repeat_change(event) {

		//console.log("repeat");

		if ( event.target.checked === true ) {
			document.getElementById("week_set").style.display = "block";
		} else {
			document.getElementById("week_set").style.display = "none";
		}

	}

	// 要素 arg_var.e.sche_list 内をクリックした場合のイベント処理
	// スケジュール関係ボタンの挙動: class によって挙動分岐
	// bind で引数を受け取っているので event は末尾に変更される

	async function sche_list_click_event(ctx, event) {

		//console.log("sche_list_click");

		let schedule_message = document.getElementById("schedule_message");

		// add_button でスケジュール追加 UI 表示 and add_button ボタンは非表示
		// 同時に各パラメータを初期化させる

		if ( event.target.id === "add_button" ) {

			// 各パラメータを初期化

			// ID は new に戻す

			document.getElementById("edit_index").innerHTML = "new";
			document.getElementById("edit_index").dataset.index = "new";

			// time は初期化

			document.getElementById("set_time").value = "";

			// action は play に

			document.getElementById("play").checked = true;

			// チャンネルは1番目に移動

			const channel_selector = document.querySelectorAll('input[name="channel"]');

			if ( channel_selector.lenth > 0 ) {
				channel_selector[0].checked = true;
			}

			// チャンネル項目を表示

			document.getElementById("channel_set").style.display = "block";

			// repeat はチェック外す

			document.getElementById("set_repeat").checked = false;

			// week は全て解除

			const week_selector = document.querySelectorAll('input[name="week"]');

			for ( let n = 0; n < week_selector.length; n++ ) {
				week_selector[n].checked = false;
			}

			// チャンネル項目を表示

			document.getElementById("week_set").style.display = "none";

			// submit 非表示

			document.getElementById("submit_set").style.display = "none";

			// UI 表示・追加ボタン非表示

			document.getElementById("schedule_ui").classList.add("schedule_ui_visible");
			document.getElementById("add_button_box").style.display = "none";

		}

		// スケジュール UI を閉じるボタン

		if ( event.target.id === "ui_close" ) {
			document.getElementById("schedule_ui").classList.remove("schedule_ui_visible");
			document.getElementById("add_button_box").style.display = "Block";
		}

		// スケジュールの新規追加・修正

		if ( event.target.id === "schedule_set" ) {

			// 各パラメータチェック

			// id: 新規の場合は new 修正の場合は対象となるid値 (数字)

			const set_id_value = document.getElementById("edit_index").dataset.index;

			// セット時間

			let set_time_value = document.getElementById("set_time").value;

			if ( ! set_time_value ) {
				schedule_message.innerHTML = "Set the schedule time.";
				return;
			}

			// アクション

			let action_value_object = document.getElementsByName("action");

			let action_value = "";

			for ( let i = 0; i < action_value_object.length; i++ ) {
				if ( action_value_object[i].checked ) {
					action_value = action_value_object[i].value;
				}
			}

			//console.log(action_value);

			// チャンネル

			let channel_value_object = document.getElementsByName("channel");

			let channel_value = "";

			for ( let i = 0; i < channel_value_object.length; i++ ) {
				if ( channel_value_object[i].checked ) {
					channel_value = channel_value_object[i].value;
				}
			}

			// 音声ファイル

			let audio_value_object = document.getElementsByName("audio");

			let audio_value = "";

			for ( let i = 0; i < audio_value_object.length; i++ ) {
				if ( audio_value_object[i].checked ) {
					audio_value = audio_value_object[i].value;
				}
			}

			// 音量

			let volume_value = document.getElementById("set_volume").value;

			// リピート

			let repeat_value = document.getElementById("set_repeat").checked;

			// 曜日 (リピート用)

			let week_value_object = document.getElementsByName("week");

			let week_value_array = [];

			for (let i = 0; i < week_value_object.length; i++) {
				if ( week_value_object[i].checked ) {
					week_value_array.push(week_value_object[i].value);
				}
			}

			// 送信情報の整理

			// アクションが stop または reboot の場合はチャンネル情報は不要
			// 音量も不要

			if ( action_value === "stop" || action_value === "reboot" ) {
				channel_value = "";
				volume_value = "";
			}

			// アクションが audio の場合はチャンネル情報へ音声ファイル情報を入れる
			// channel に URL を収納させる

			if ( action_value === "audio" ) {
				channel_value = audio_value;
			}

			// リピートが false の場合は曜日情報は不要

			if ( repeat_value === false ) {
				week_value_array = [];
			}

			// 保存処理

			const send_params = {
				id: set_id_value,
				time: set_time_value,
				action: action_value, 
				channel: channel_value,
				volume: volume_value,
				repeat: repeat_value,
				week: JSON.stringify(week_value_array),
				enabled: true
			};

			const query_string = new URLSearchParams(send_params).toString();

			const schedule_set_result = await ctx.fnc.fetch_template("./php/schedule_set.php", query_string);

			// 書込みに成功したら書き込んだバイト数が、失敗なら 0 が返って来る。

			if ( schedule_set_result !== 0 ) {
				update_schedule_audio_html(ctx);
			} else {
				ctx.fnc.msg_fade_in("スケジュールの保存に失敗しました。");
			}

		}

		// スケジュールの既存情報編集のための情報セット

		if ( event.target.classList.contains("schedule_edit") === true ) {

			// スケジュール追加 UI 表示 and add_button ボタンは非表示

			document.getElementById("schedule_ui").classList.add("schedule_ui_visible");
			document.getElementById("add_button_box").style.display = "none";

			const this_index = Number(event.target.dataset.index);

			const schedule_object = await ctx.fnc.fetch_template("./php/schedule_get.php", "");

			// オブジェクトの中から対象IDの情報を取り出す

			const index_object = schedule_object[this_index];

			// id と data-index 変更

			document.getElementById("edit_index").innerHTML = "Index: " + this_index + " (ID: " + (this_index + 1) + ")";
			document.getElementById("edit_index").dataset.index = this_index;

			// set_time 変更

			document.getElementById("set_time").value = index_object["time"];

			// play / stop 切り替え

			document.getElementById(index_object["action"]).checked = true;

			// channel 切り替え

			let channel_value_object = document.getElementsByName("channel");

			for ( let n = 0; n < channel_value_object.length; n++ ) {
				channel_value_object[n].checked = false;
			}

			if ( index_object["channel"] !== "" ) {
				document.getElementById(index_object["channel"]).checked = true;
			}

			// 音量値割り当て

			document.getElementById("set_volume").value = index_object["volume"];

			// action が play の場合は channel_set は表示 audio_set は非表示 set_volume は表示
			// action が audio の場合は channel_set は非表示 audio_set は表示 set_volume は表示
			// action が stop or reboot の場合は channel_set audio_set 両方非表示 set_volume も非表示

			if ( index_object["action"] === "play" ) {
				document.getElementById("channel_set").style.display = "block";
				document.getElementById("audio_set").style.display = "none";
				document.getElementById("volume_set").style.display = "block";
			} else if ( index_object["action"] === "audio" ) {
				document.getElementById("channel_set").style.display = "none";
				document.getElementById("audio_set").style.display = "block";
				document.getElementById("volume_set").style.display = "block";
			} else {
				document.getElementById("channel_set").style.display = "none";
				document.getElementById("audio_set").style.display = "none";
				document.getElementById("volume_set").style.display = "none";
			}

			// repeat

			document.getElementById("set_repeat").checked = index_object["repeat"];

			// 曜日 (リピート用)

			let week_value_object = document.getElementsByName("week");

			// repeat が true の場合は week_set は表示
			// repeat が false の場合は week_set は非表示

			if ( index_object["repeat"] === true ) {
				document.getElementById("week_set").style.display = "block";			
			} else {
				document.getElementById("week_set").style.display = "none";		
			}

			let week_value_array = [];

			// 一旦チェックを解除

			for ( let i = 0; i < week_value_object.length; i++ ) {
				week_value_object[i].checked = false;
			}

			// 一致した曜日だけチェック入れる

			const this_week_array = index_object["week"];

			for ( let d = 0; d < this_week_array.length; d++ ) {
				document.getElementById(this_week_array[d]).checked = true;
			}

			// 保存は、ここではなく if ( event.target.id === "schedule_set" ) 側で実行する
			// 保存ボタンは表示

			document.getElementById("submit_set").style.display = "Block";

			// UI を表示させる

			document.getElementById("schedule_ui").style.display = "Block";

		}

		// スケジュールの削除

		if ( event.target.classList.contains("schedule_dell") === true ) {

			const this_index = Number(event.target.dataset.index);

			if ( confirm( "スケジュールを削除します ID: " + (this_index + 1) ) ) {

				const send_params = {
					index: this_index
				};

				const query_string = new URLSearchParams(send_params).toString();
				const schedule_delete_result = await ctx.fnc.fetch_template("./php/schedule_delete.php", query_string);

				if ( schedule_delete_result !== 0 ) {
					update_schedule_audio_html(ctx);
				} else {
					arg_func.msg_fade_in("スケジュールの削除に失敗しました。");
				}

			}

		}

		// スケジュールの有効化・無効化

		if ( event.target.classList.contains("schedule_enab") === true ) {

			const this_index = Number(event.target.dataset.index);
			const this_enabled = event.target.dataset.enabled;

			let confirm_text = "";
			let enabled_value = "";

			if ( this_enabled === "true" ) {

				// 現在 true の場合 > false に変更

				confirm_text = "スケジュールを無効化します ID: " + (this_index + 1);
				enabled_value = "false";

			} else {

				// 現在 false の場合 > true に変更

				confirm_text = "スケジュールを有効化します ID: " + (this_index + 1);
				enabled_value = "true";

			}

			if ( confirm(confirm_text) ) {

				const send_params = {
					index: this_index,
					enabled: enabled_value
				};

				const query_string = new URLSearchParams(send_params).toString();
				const schedule_enabled_result = await ctx.fnc.fetch_template("./php/schedule_enabled.php", query_string);

				if ( schedule_enabled_result !== 0 ) {
					update_schedule_audio_html(ctx);
				} else {
					arg_func.msg_fade_in("スケジュールの更新に失敗しました。");
				}

			}

		}

	}

	// スケジュール管理用 UI と音声ファイル関係の html を更新する関数
	// 1. スケジュール情報を確認
	// 2. 最新の音声ファイル情報に更新
	// 3. スケジュール管理 UI の一覧を更新

	let sche_list_click_handler = null;

	export async function update_schedule_audio_html(ctx) {

		// 保存されているスケジュール情報を取得

		const schedule_object = await ctx.fnc.fetch_template("./php/schedule_get.php", "");

		// 保存されている音声ファイルを変数に保存

		ctx.var.v.audio_files = await ctx.aud.get_audio_list(ctx);

		// スケジュール関係の html を更新

		schedule_list.innerHTML = generate_schedule_list_html(schedule_object, ctx.var);

		// デフォルトでは repeat は false のため week_set は非表示にしておく

		document.getElementById("week_set").style.display = "none";

		// 同じく action は play のため audio も非表示とする

		document.getElementById("audio_set").style.display = "none";

		// 各イベント登録
		// update_schedule_audio_html() を実行するたびにイベントが重複して登録されてしまうため
		// 事前に削除を行ってから再登録を行う

		// time イベントの再登録

		const set_time_handler = set_time_change;

		document.getElementById("set_time").removeEventListener("change", set_time_handler);
		document.getElementById("set_time").addEventListener("change", set_time_handler);

		// action イベントの再登録

		document.querySelectorAll('input[name="action"]').forEach( function(radio) {

			const set_action_handler = set_action_change;

			radio.removeEventListener("change", set_action_handler);
			radio.addEventListener("change", set_action_handler);

		});

		// repeat イベントの再登録

		document.querySelectorAll('input[name="set_repeat"]').forEach( function(radio) {

			const set_repeat_handler = set_repeat_change;

			radio.removeEventListener("change", set_repeat_handler);
			radio.addEventListener("change", set_repeat_handler);

		});

		// 要素 arg_var.e.sche_list 内をクリックした場合のイベントの再登録
		// event 以外に引数を渡したいので bind を使用

		// 初回のみ handler 生成 (以降再利用)

		if ( ! sche_list_click_handler ) {
			sche_list_click_handler = sche_list_click_event.bind(null, ctx);
		}

		ctx.var.e.sche_list.removeEventListener("click", sche_list_click_handler);
		ctx.var.e.sche_list.addEventListener("click", sche_list_click_handler);

	}

