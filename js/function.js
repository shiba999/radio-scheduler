
	// タイマー関数: 設定した上限まで経過するか条件に合致すると停止する

	export function create_timer(callback, max = 60) {

		let count = 0;
		let should_stop = false;

		const interval = setInterval(function() {

			if ( should_stop ) {
				clearInterval(interval);
				return;
			}

			count++;
			callback(count);// コールバック実行（経過秒数と状態を渡す）

			// 上限到達で停止

			if (count >= max) {
				clearInterval(interval);
				callback(max, "timeout"); // 終了通知
			}

		}, 1000);

		window.stop_timer = function() {
			console.log("create_timer(): STOP");
			should_stop = true;
		};

		return interval;// 必要なら手動停止用

	}

	// fetch のテンプレート function() の手前に async を付けること

	export async function fetch_template(source, send = "", timeout = 10000) {

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
				console.error("fetch_template(): error");
				result = "error";
			}

			//throw error;// エラーも投げる
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

