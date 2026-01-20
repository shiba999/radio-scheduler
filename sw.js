
	// キャッシュのバージョンを更新する場合は cv.txt の値を変更する事

	const CACHE_VERSION = new URL(location).searchParams.get("cv");

	const cache_files = [
		"./",
		"./index.html",
		"./css/style.css?v=" + CACHE_VERSION,
		"./js/function.js?v=" + CACHE_VERSION,
		"./js/indexeddb.js?v=" + CACHE_VERSION,
		"./js/audio.js?v=" + CACHE_VERSION,
		"./js/schedule.js?v=" + CACHE_VERSION,
		"./js/notice.js?v=" + CACHE_VERSION,
		"./js/control.js?v=" + CACHE_VERSION,
		"./js/openweather.js?v=" + CACHE_VERSION,
		"./image/standby.gif",
		"./image/playing.gif",
		"./page/settings.html",
		"./favicon.ico"
	];

	// [ indexedDB関係 ] ------------------------------------------

	import * as idb from "./js/indexeddb.js";

	// インストールイベント：必要なリソースをキャッシュ

	self.addEventListener("install", function(event) {

		console.log("*** sw.js: INSTALL ( CACHE VERSION: " + CACHE_VERSION + " ) ***");

		event.waitUntil( (async function() {

			// キャッシュの保存

			const cache = await caches.open(CACHE_VERSION);
			const promised_files = await cache.addAll(cache_files);

			// バージョンの保存

			await idb.update_indexeddb("version", CACHE_VERSION);

			// 強制的に更新

			self.skipWaiting();

		})() );

	});

	// 活性化イベント：不要な古いキャッシュを削除

	self.addEventListener("activate", function(event) {

		console.log("*** sw.js: activate ***");

		event.waitUntil( (async function() {

			// 古いキャッシュ取得

			const key_list = await caches.keys();

			// 古いキャッシュ削除

			await Promise.all( key_list.map(function(key) {
				if ( key !== CACHE_VERSION ) {
					return caches.delete(key);
				}
			}) );

			// クライアント通知（削除完了後）

			const clients = await self.clients.matchAll({
				//type: "window",
				includeUncontrolled: true
			});

			clients.forEach( function(client) {
				client.postMessage({
					type: "CACHE_UPDATE_COMPLETE",
					version: CACHE_VERSION,
					message: "サービスワーカーが更新されました。<br />Version: " + CACHE_VERSION
				});
			} );

		})() );

		self.clients.claim();

	});

	self.addEventListener("fetch", function(event) {

		const request = event.request;

		// 1. HTML ナビゲーション
		// ページ遷移, PWA起動など「HTMLナビゲーション」の場合
		// ブラウザがページを開こうとしているリクエスト (HTML のナビゲーション) だけ対象

		if ( request.mode === "navigate" ) {

			event.respondWith( (async function() {

				//console.log(request);

				// 1. キャッシュに保存された情報を優先して表示

				const cache = await caches.open(CACHE_VERSION);
				const cached = await cache.match(request);

				if (cached) {
					//console.log(request.url);
					return cached;
				}

				// 2. キャッシュになければネットワークから取得

				return await fetch(request);

			})() );

			return;// ここで終了

		}

		// 2. CSS / JS / 画像

		event.respondWith( (async function() {

			const cache = await caches.open(CACHE_VERSION);

			// [!] URLそのままで探す
			const cached = await cache.match(request);

			if (cached) {
				//console.log("* cached: " + request.url);
				return cached;
			}

			// オンラインなら取得

			try {
				//console.log("* online: " + request.url);
				return await fetch(request);
			} catch (e) {
				// オフライン時は何もしない
				return Response.error();
			}

		})() );

	});

