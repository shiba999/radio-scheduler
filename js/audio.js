

	const audio_box = document.getElementById("audio_box");
	const audio_bg = document.getElementById("audio_bg");
	const audio_name = document.getElementById("ab_name");
	const audio_bar_bg = document.getElementById("ad_bar_bg");
	const audio_bar_value = document.getElementById("ad_bar_value");
	const audio_time = document.getElementById("ab_time_num");
	const audio_pause = document.getElementById("ab_pause");
	const audio_stop = document.getElementById("ab_stop");


	// 秒数を HH:mm:ss または mm:ss 形式に変換する
 	// @param {number|string} seconds - mpvから取得した秒数
 	// @returns {string} フォーマットされた文字列
 
	function format_time(seconds) {

		const s = Math.floor(parseFloat(seconds || 0));

		const hh = Math.floor(s / 3600);
		const mm = Math.floor((s % 3600) / 60);
		const ss = s % 60;

		// 各要素を2桁にパディング（ゼロ埋め）
		const pad = (num) => String(num).padStart(2, '0');

		if (hh > 0) {
			// 1時間以上の場合: HH:mm:ss
			return `${pad(hh)}:${pad(mm)}:${pad(ss)}`;
		} else {
			// 1時間未満の場合: mm:ss
			return `${pad(mm)}:${pad(ss)}`;
		}

	}


	// 音声ファイル停止時に各ボタンを調整
	// 音声ファイル削除ボタンは無効を解除
	// 再生ボタンのスタイルを初期化

	function audio_stop_button_control(arg_var) {

		clearInterval( arg_var.v.set_interval.shift() );

		const play_btn_array = document.getElementsByClassName("audio_play");
		const del_btn_array = document.getElementsByClassName("audio_del");

		for ( let n = 0; n < play_btn_array.length; n++ ) {
			play_btn_array[n].style.backgroundColor = null;
			play_btn_array[n].style.opacity = null;
		}

		for ( let m = 0; m < del_btn_array.length; m++ ) {
			del_btn_array[m].style.opacity = null;
			del_btn_array[m].style.pointerEvents = null;
		}

		arg_var.v.audio_play = false;
		arg_var.e.up_msg.innerHTML = "スケジュール再生に使用する音声ファイルをここで登録可能です。";

	}

	// プレイヤーステータス変数

	let player_info = {
		filename: "",
		total: 0,
		current: 0,
		pause: false
	};

	// プレイヤーの表示を初期化

	const icon_pause = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 85.87 79.37"><rect y="0" width="31.18" height="79.37"/><rect x="54.69" y="0" width="31.18" height="79.37"/></svg>';// 一時停止アイコン
	const icon_play = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 85.87 79.37"><polygon points="80.14 39.69 11.4 0 11.4 79.37 80.14 39.69"/></svg>';// 再生アイコン

	function player_ini() {

		player_info.filename = "*****";
		player_info.total = 0;
		player_info.current = 0;
		player_info.pause = false;

		audio_name.innerHTML = player_info.filename;
		audio_time.innerHTML = format_time(player_info.total) + " / " + format_time(player_info.current);
		audio_bar_value.style.width = "0px";
		audio_pause.innerHTML = icon_pause;

	}

	// プレイヤーが再生中かをチェックする関数
	// 音声ファイルを再生しているケースとラジオを再生しているケースと
	// 何も再生していないケースと３通り存在する

	export async function check_player(arg_var, arg_fnc) {

		player_ini();// プレイヤー初期化

		let return_array = {
			type: "",
			file: ""
		};

		// php の Socket でプレイヤー情報を取得

		// media-title: ストリームなら http: が含まれる , mp3 ならファイル名が表示される

		const send_params_title = {
			type: "get",
			property: "media-title",
			id: 1
		};

		const query_string_title = new URLSearchParams(send_params_title).toString();
		const title_array = await arg_fnc.fetch_template("./php/socket_control.php", query_string_title);

		//console.log(title_array);

		// ここで動いていなかったらストップでも良い

		if ( title_array.error === "not_working" ) {

			console.log("not_working");
			audio_box.classList.remove("is-visible");

			return return_array;

		}

		// duration: ストリームなら 0.000 , mp3 なら音声の長さが返される

		const send_params_duration = {
			type: "get",
			property: "duration",
			id: 2
		};

		const query_string_duration = new URLSearchParams(send_params_duration).toString();
		const duration_array = await arg_fnc.fetch_template("./php/socket_control.php", query_string_duration);

		//console.log(duration_array);

		// filename: ストリームなら - , mp3 ならファイル名が返される

		const send_params_filename = {
			type: "get",
			property: "filename",
			id: 3
		};

		const query_string_filename = new URLSearchParams(send_params_filename).toString();
		const filename_array = await arg_fnc.fetch_template("./php/socket_control.php", query_string_filename);

		//console.log(filename_array);

		// 音声ファイルかラジオか識別

		let audio_type;

		if ( ! title_array.data.includes("http") || filename_array.data.includes(".mp3") || Number(duration_array.data) > 0 ) {

			audio_type = "file";
			player_info.filename = title_array.data;// ファイル名保存

			// 音声ファイルの場合はここで総時間を取得して保存しておく

			const send_params_duration = {
				type: "get",
				property: "duration",
				id: 4
			};

			const query_string_duration = new URLSearchParams(send_params_duration).toString();
			const duration_array = await arg_fnc.fetch_template("./php/socket_control.php", query_string_duration);

			player_info.total = duration_array.data;// 総時間保存

			audio_box.classList.add("is-visible");// プレイヤー表示

		} else {

			audio_type = "radio";
			//audio_stop_button_control(arg_var);// set_interval 削除

		}

		// 返す配列を整理

		return_array.type = audio_type;

		if ( audio_type === "radio" ) {
			const match = title_array.data.match(/\/#!\/live\/([^/]+)/);
			const channel = match ? match[1] : null;
			return_array.file = channel;
		} else {
			return_array.file = title_array.data;
		}

		//console.log(audio_type);

		//return audio_type;
		return return_array;

	}

	// 音声ファイルの再生状況を監視する関数
	// 戻り値で player_state.state === true なら再生中
	// player_state.state === false なら停止

	export async function check_audio(arg_var, arg_fnc) {

		//console.log("check_audio() -----");

		// ファイル名

/*		const send_params_filename = {
			type: "get",
			property: "filename",
			id: 1
		};

		const query_string_filename = new URLSearchParams(send_params_filename).toString();
		const filename_array = await arg_fnc.fetch_template("./php/socket_control.php", query_string_filename);

		audio_name.innerHTML = filename_array.data;*/

		// 一時停止状態
		// この値を保存しておけば、一時停止実行時にもっとスムーズになると思う。

		const send_params_pause = {
			type: "get",
			property: "pause",
			id: 2
		};

		const query_string_pause = new URLSearchParams(send_params_pause).toString();
		const pause_array = await arg_fnc.fetch_template("./php/socket_control.php", query_string_pause);

		// ここで動いていなかったらストップでも良い

		if ( pause_array.error === "not_working" ) {
			console.log("not_working");
			//audio_stop_button_control(arg_var);
			clearInterval( arg_var.v.set_interval.shift() );// check_audio 停止
			audio_box.classList.remove("is-visible");// プレイヤー非表示
			return;
		}

		player_info.pause = pause_array.data;// 値を変数に保存

		// プレイヤーのアイコン変更

		//console.log(pause_array.data);

		if ( player_info.pause ) {
			audio_pause.innerHTML = icon_play;
		} else {
			audio_pause.innerHTML = icon_pause;
		}

		// 音声の長さ（秒）

/*		const send_params_duration = {
			type: "get",
			property: "duration",
			id: 3
		};

		const query_string_duration = new URLSearchParams(send_params_duration).toString();
		const duration_array = await arg_fnc.fetch_template("./php/socket_control.php", query_string_duration);*/

		// 音声の現在位置（秒）

		const send_params_time = {
			type: "get",
			property: "time-pos",
			id: 4
		};

		const query_string_time = new URLSearchParams(send_params_time).toString();
		const time_array = await arg_fnc.fetch_template("./php/socket_control.php", query_string_time);

		player_info.current = time_array.data;// 値を変数に保存

		// プレイヤーへ反映

		audio_name.innerHTML = player_info.filename;// ファイル名

		const this_duration = Math.round(player_info.total);
		const this_time = Math.round(player_info.current);

		audio_time.innerHTML = format_time(this_time) + " / " + format_time(this_duration);// 再生時間

		// 時間割合で、シークバーの長さを調整

		const bar_max = audio_bar_bg.clientWidth;// シークバーの元の長さ

		//console.log(bar_max);

		const this_bar_width = this_time / this_duration * bar_max;

		audio_bar_value.style.width = this_bar_width + "px";

/*		const player_state = await arg_fnc.fetch_template("./php/audio_check.php");

		//console.log( JSON.stringify(player_state) );

		if ( player_state.state === true ) {// 再生中だった場合は停止
			audio_play_button_control(arg_var, player_state.file);
		} else if ( player_state.state === false ) {// 停止中だった場合は再生
			audio_stop_button_control(arg_var);
		}*/

	}

	// 音声ファイル再生時に各ボタンを調整
	// 再生中は音声ファイル削除ボタンは無効

	function audio_play_button_control(arg_var, filename) {

		//filename = decodeURI(filename);
		//filename = encodeURI(filename);

		//console.log(filename);

		const play_btn_array = document.getElementsByClassName("audio_play");
		const del_btn_array = document.getElementsByClassName("audio_del");

		for ( let m = 0; m < del_btn_array.length; m++ ) {
			del_btn_array[m].style.opacity = "0.5";
			del_btn_array[m].style.pointerEvents = "none";
		}

		for ( let n = 0; n < play_btn_array.length; n++ ) {

			const this_filename = play_btn_array[n].dataset.name;

			// PHP から返されるファイル名は URL エンコードされているので戻す。

			if ( this_filename === filename ) {
				//console.log( filename + "\n" + this_filename );
				play_btn_array[n].style.backgroundColor = "#fff";
				play_btn_array[n].style.opacity = "1";
				arg_var.v.audio_play = true;
				arg_var.e.up_msg.innerHTML = '<spam class="playing"></spam>' + decodeURI(this_filename) + " (再度 Play ボタンで停止)";
			}

		}

	}

	// 音声ファイル関係ボタンの挙動: class によって挙動分岐
	// bind で引数を受け取っているので event は末尾に変更される

	async function audio_list_click_event(ctx, event) {

		// アップロードした音声ファイルの削除

		if ( event.target.classList.contains("audio_del") === true ) {

			let this_name = event.target.dataset.name;

			if ( confirm( "このファイルを削除します [" + this_name + "]" ) ) {

				const send_params = {
					file: this_name
				};

				const query_string = new URLSearchParams(send_params).toString();
				const del_result = await ctx.fnc.fetch_template("./php/delete_audio_file.php", query_string);

				ctx.var.e.up_msg.innerHTML = del_result[1];

				if ( del_result[0] === "success" ) {
					ctx.sch.update_schedule_audio_html(ctx);
				} else {
					ctx.var.e.up_msg.innerHTML = del_result[1];
				}

			}

		}

		// アップロードした音声ファイルを再生

		if ( event.target.classList.contains("audio_play") === true ) {

			//const play_btn_array = document.getElementsByClassName("audio_play");
			//const del_btn_array = document.getElementsByClassName("audio_del");

			let this_name = event.target.dataset.name;

			const send_params = {
				file: decodeURI(this_name)
			};

			const play_string = new URLSearchParams(send_params).toString();
			const play_result = await ctx.fnc.fetch_template("./php/player_audio.php", play_string);

			console.log(play_result);

			// 再生されたかの確認 (少しづつ待機しながら確認)

			let count = 1;
			let success_flag = false;

			for ( let n = 0; n < 10; n++ ) {

				await ctx.fnc.js_sleep(500);// 少し待機

				// Socket でプレイヤーが起動しているか確認

				const send_params_result = {
					type: "get",
					property: "filename",
					id: 1
				};

				const query_string_result = new URLSearchParams(send_params_result).toString();
				const result_array = await ctx.fnc.fetch_template("./php/socket_control.php", query_string_result);

				//console.log(result_array);

				// 再生が確認できたらファイル名を保存

				if ( result_array.error === "success" ) {

					player_info.filename = result_array.data;// ファイル名
					success_flag = true;

					break;

				}

				count++;

			}

			// 再生が確認できたら総時間を保存して UI 表示

			//if ( play_result === "playing" ) {
			if ( success_flag === true ) {

				const send_params_duration = {
					type: "get",
					property: "duration",
					id: 2
				};

				const query_string_duration = new URLSearchParams(send_params_duration).toString();
				const duration_array = await ctx.fnc.fetch_template("./php/socket_control.php", query_string_duration);

				player_info.total = duration_array.data;// 総時間

				audio_box.classList.add("is-visible");// プレイヤー表示

				// 音声ファイル再生状況監視開始

				//check_audio(ctx.var, ctx.fnc);// すぐ実行だと Socket が間に合わない模様
				ctx.var.v.set_interval.push( setInterval(check_audio, 1000, ctx.var, ctx.fnc) );

			} else {

				ctx.var.e.up_msg.innerHTML = "[再生失敗] " + this_name;

			}

/*			if ( ctx.var.v.audio_play === false ) {

				let this_name = event.target.dataset.name;

				const send_params = {
					file: decodeURI(this_name)
				};

				const play_string = new URLSearchParams(send_params).toString();
				const play_result = await ctx.fnc.fetch_template("./php/player_audio.php", play_string);

				console.log(play_result);

				if ( play_result === "playing" ) {

					//audio_play_button_control(ctx.var, this_name);// 再生開始: ボタンスタイル調整

					//ctx.con.init_play_info(ctx.var);// ラジオ情報初期化

					audio_box.classList.add("is-visible");// プレイヤー表示

					// 音声ファイル再生状況監視開始

					//check_audio(ctx.var, ctx.fnc);// すぐ実行だと Socket が間に合わない模様
					ctx.var.v.set_interval.push( setInterval(check_audio, 1000, ctx.var, ctx.fnc) );

				} else {

					ctx.var.e.up_msg.innerHTML = "[再生失敗] " + this_name;

				}

			} else {

				const result = await ctx.fnc.fetch_template("./php/player_stop.php");

				//console.log(result);

				// 再生停止: ボタンスタイル調整

				if ( result == "stopped" ) {
					audio_stop_button_control(ctx.var);
				} else {
					ctx.var.e.up_msg.innerHTML = "*** 再生停止できませんでした ***";
				}

			}*/

		}

	}

	// アップロードしたファイルを取得する関数

	let audio_list_click_handler = null;

	export async function get_audio_list(ctx) {

		const upload_result = await ctx.fnc.fetch_template("./php/get_list_audio.php");

		//console.log(upload_result);

		let html = "";
		let return_array = new Array();

		if ( upload_result.length > 0 ) {

			html = "<ul>";

			for ( let n = 0; n < upload_result.length; n++ ) {

				const this_audio_name = upload_result[n];
				const this_audio_url = "./upload/" + encodeURI(this_audio_name);

				html +=
					'<li><a href="' + this_audio_url + '" target="_blank">' + upload_result[n]
					+ '</a><span class="audio_del" data-name="' + encodeURI(this_audio_name) + '">Del</span><span class="audio_play" data-name="' + encodeURI(this_audio_name) + '">Play</span>' + "</li>"
				;

			}

			html += "</ul>";

		} else {

			html += "<p>No audio files have been registered yet.</p>";

		}

		ctx.var.e.audio_list.innerHTML = html;// リスト html を表示

		// 初回のみ handler 生成 (以降再利用)

		if ( ! audio_list_click_handler ) {

			audio_list_click_handler = audio_list_click_event.bind(null, ctx);

			// プレイヤー内のボタン挙動 (再生停止)

			audio_stop.addEventListener("click", async function() {

				// プレイヤー初期化

				audio_name.innerHTML = "----";
				audio_time.innerHTML = "00:00 / 00:00";
				audio_bar_value.style.width = "0px";

				const result = await ctx.fnc.fetch_template("./php/player_stop.php");

				//console.log(result);

				// 再生停止: ボタンスタイル調整

				if ( result == "stopped" ) {
					clearInterval( ctx.var.v.set_interval.shift() );// check_audio 停止
					audio_box.classList.remove("is-visible");// プレイヤー非表示
				} else {
					//ctx.var.e.up_msg.innerHTML = "*** 再生停止できませんでした ***";
				}

			});

			// プレイヤー内のボタン挙動 (一時停止・一時停止解除)

			audio_pause.addEventListener("click", async function() {

				// 一時停止状態の確認

/*				const send_params_pause = {
					type: "get",
					property: "pause",
					id: 1
				};

				const query_string_pause = new URLSearchParams(send_params_pause).toString();
				const pause_array = await ctx.fnc.fetch_template("./php/socket_control.php", query_string_pause);*/

				//console.log(pause_array);
				//console.log(pause_array.data);

				let pause_value = false;

				let send_params_pause_set = {
					type: "set",
					property: "pause",
					value: pause_value,
					id: 2
				};

				// 一時停止中ではないなら一時停止に

				if ( player_info.pause === false ) {
					send_params_pause_set.value = true;
					pause_value = true;
				}

				//console.log(send_params_pause_set);

				// 一時停止・解除の実行

				const query_string_pause_set = new URLSearchParams(send_params_pause_set).toString();
				const pause_set_array = await ctx.fnc.fetch_template("./php/socket_control.php", query_string_pause_set);

				//console.log( "pause: " + pause_set_array.error );

				if ( pause_set_array.error === "success" ) {

					// 一時停止状態の変更に成功した場合は変数に保存

					player_info.pause = pause_value;

					// アイコンの変更

					if ( player_info.pause === false ) {
						audio_pause.innerHTML = icon_play;
					} else {
						audio_pause.innerHTML = icon_pause;
					}

				}

			});

		}

		ctx.var.e.audio_list.removeEventListener("click", audio_list_click_handler);
		ctx.var.e.audio_list.addEventListener("click", audio_list_click_handler);

		// ファイルリストを返す

		return upload_result;

	}

	// ファイルアップロードに使用する各イベントのセットアップ
	// このイベントは index.html に記載された要素からのイベントなので index.html からイベント登録

	export async function audio_event_setup(ctx) {

		// ファイルドラッグアンドドロップの制御
		// http://iwb.jp/return-false-preventdefault-stoppropagation/

		// Drag and drop イベント
		// http://dresscording.com/blog/html5/drag_drop.html

		// ファイルドラッグアンドドロップの制御
		// http://iwb.jp/return-false-preventdefault-stoppropagation/

		ctx.var.e.up_zone.addEventListener("dragover", function(event) {

			event.stopPropagation();
			event.preventDefault();
			event.dataTransfer.dropEffect = "copy";// 明示的にこれは copy であることを示します。

			ctx.var.e.up_zone.classList.add("drag_and_drop");// クラス追加

		}, false);

		ctx.var.e.up_zone.addEventListener("dragleave", function(event) {

			ctx.var.e.up_zone.classList.remove("drag_and_drop");// クラス削除

		}, false);

		ctx.var.e.up_zone.addEventListener("drop", async function(event) {

			event.stopPropagation();// イベントの伝搬を止める
			event.preventDefault();// イベント本来の一般的動作止める

			const target_files = event.dataTransfer.files;// ファイルリストオブジェクト

			ctx.var.e.up_zone.classList.remove("drag_and_drop");// クラス削除

			// 複数ファイル ajax アップロード処理

			const upload_result = await ctx.fnc.fetch_upload(target_files, ctx.var.e.up_msg);

			// 処理が終わったらスケジュールと音声ファイル関係の表示を更新

			if ( upload_result.length > 0 ) {
				ctx.sch.update_schedule_audio_html(ctx);
			}

		}, false);

	}

