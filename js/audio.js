

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

	// 音声ファイルの再生状況を監視する関数
	// 戻り値で player_state.state === true なら再生中
	// player_state.state === false なら停止

	export async function check_audio(arg_var, arg_fnc) {

		const player_state = await arg_fnc.fetch_template("./php/audio_check.php", "");

		//console.log( JSON.stringify(player_state) );

		if ( player_state.state === true ) {// 再生中だった場合は停止
			audio_play_button_control(arg_var, player_state.file);
		} else if ( player_state.state === false ) {// 停止中だった場合は再生
			audio_stop_button_control(arg_var);
		}

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

			const play_btn_array = document.getElementsByClassName("audio_play");
			const del_btn_array = document.getElementsByClassName("audio_del");

			if ( ctx.var.v.audio_play === false ) {

				let this_name = event.target.dataset.name;

				const send_params = {
					file: decodeURI(this_name)
				};

				const play_string = new URLSearchParams(send_params).toString();
				const play_result = await ctx.fnc.fetch_template("./php/player_audio.php", play_string);

				if ( play_result === "playing" ) {

					audio_play_button_control(ctx.var, this_name);// 再生開始: ボタンスタイル調整

					ctx.con.init_play_info(ctx.var);// ラジオ情報初期化

					// 音声ファイル再生状況監視開始

					check_audio(ctx.var, ctx.fnc);
					ctx.var.v.set_interval.push( setInterval(check_audio, 2000, ctx.var, ctx.fnc) );

				} else {

					ctx.var.e.up_msg.innerHTML = "[再生失敗] " + this_name;

				}

			} else {

				const result = await ctx.fnc.fetch_template("./php/player_stop.php", "");

				//console.log(result);

				// 再生停止: ボタンスタイル調整

				if ( result == "stopped" ) {
					audio_stop_button_control(ctx.var);
				} else {
					ctx.var.e.up_msg.innerHTML = "*** 再生停止できませんでした ***";
				}

			}

		}

	}

	// アップロードしたファイルを取得する関数

	let audio_list_click_handler = null;

	export async function get_audio_list(ctx) {

		const upload_result = await ctx.fnc.fetch_template( "./php/get_list_audio.php", "");

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
					+ '</a><span class="audio_del" data-name="' + encodeURI(this_audio_name) + '">Del</span><span class="audio_play" data-name="' + encodeURI(this_audio_name) + '">Play</span>' + "</li>";

			}

			html += "</ul>";

		} else {

			html += "<p>No audio files have been registered yet.</p>";

		}

		ctx.var.e.audio_list.innerHTML = html;// リスト html を表示

		// 初回のみ handler 生成 (以降再利用)

		if ( ! audio_list_click_handler ) {
			audio_list_click_handler = audio_list_click_event.bind(null, ctx);
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

