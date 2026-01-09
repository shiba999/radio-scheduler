	//console.log("### indexeddb.js --------------------");


	// ServiceWorker で使用する IndexedDB の設定


	// IndexedDB 諸設定 inddb

	let inddb = {
		"name": "radiko_info",
		"version": 1,
		"table": "radiko",
		"db_open": false
	};


	// 各関数内で使用する関数

	// DB接続

	function idb_open(rw, keyname, set_value) {

		let open_promise = new Promise( function(resolve, reject) {

			//console.log("open #1");

			inddb.db_open = indexedDB.open(inddb.name, inddb.version);

			inddb.db_open.onerror = function(error) {

				//console.log("!!! indexeddb.js > open > onerror !!!");

				resolve(event);

			};

			inddb.db_open.onsuccess = function(event) {

				//console.log("indexeddb.js > open > onsuccess");

				let db = event.target.result;

				// 読み込み or 書き込み

				if ( rw == "r" ) {

					// 読み込み

					//console.log("indexeddb.js open > Read");

					let transaction = db.transaction([inddb.table], "readonly");
					let db_store = transaction.objectStore(inddb.table);

					let value_get_request;

					if ( keyname == "" ) {

						value_get_request = db_store.getAll();

					} else {

						value_get_request = db_store.get(keyname);

					}

					value_get_request.onsuccess = function(event) {
						resolve(event);
					}

					value_get_request.onerror = function(event) {
						resolve(event);
					}

					//db.close();// 接続を解除する

				} else if ( rw == "w" ) {

					// 書き込み

					//console.log("indexeddb.js open > Write");
					//console.log(inddb.argument);

					let transaction = db.transaction([inddb.table], "readwrite");
					let db_store = transaction.objectStore(inddb.table);

					let value_put_request = db_store.put(
						{name: keyname, value: set_value}
					);

					value_put_request.onsuccess = function(event) {
						resolve(event);
					}

					value_put_request.onerror = function(event) {
						resolve(event);
					}

					//db.close();// 接続を解除する

				} else if ( rw == "d" ) {

					// 削除

					//console.log("indexeddb.js open > delete");

					let transaction = db.transaction([inddb.table], "readwrite");
					let db_store = transaction.objectStore(inddb.table);

					let value_delete_request = db_store.delete(keyname);

					value_delete_request.onsuccess = function(event) {
						resolve(event);
					}

					value_delete_request.onerror = function(event) {
						resolve(event);
					}

				}

				db.close();// 接続を解除する

			};

			// indexedDB のバージョンが変更された場合に実行 (新規作成時含む)
			// このタイミングで必要な情報を保存する

			inddb.db_open.onupgradeneeded = function(event) {

				//console.log("indexeddb.js > open > onupgradeneeded");

				let db = event.target.result;
				let table_object = db.createObjectStore(inddb.table, { keyPath: "name" });

				let value_get_request = table_object.getAll();

				value_get_request.onsuccess = function(event) {
					//console.log("indexeddb.js open > (new) get Success!");
					resolve(event);
				}

				value_get_request.onerror = function(event) {
					resolve(event);
				}

				db.close();// 接続を解除する

			};

		});

		open_promise.then( function(value) {

			//console.log("open #2");
			//console.log(value);
			//console.log("open #END");

		});

		return open_promise;

	}


	// 読み込み時に実行 > 接続時にテーブルが無ければ生成

	function load_indexeddb( key = "" ) {

		let load_promise = new Promise( function(resolve, reject) {

			//console.log("load #1");
			resolve( idb_open("r", key) );

		}).then( function(value) {

			//console.log("load #2");
			//console.log(value.target.result);
			//console.log(value.result);

			return value.target.result;

		}).catch( function(error) {

			//console.log("load #ERROR");
			//console.log(error);

		});

		return load_promise;

	}


	// indexddb 値更新
	// 上の関数とタブってるコードがあるけどどうにかしたいな

	export function update_indexeddb(keyname, value) {

		let update_promise = new Promise( function(resolve, reject) {

			//console.log("update #1");

			//inddb.keyname = value;

			//console.log(keyname, value);
			//console.log(inddb);

			//resolve( idb_open("w", "") );
			resolve( idb_open("w", keyname, value) );

		}).then( function(value) {

			//console.log("update #2");
			//console.log(value.target.result);

			return value.target.result;

		});

		return update_promise;

	}


	// indexddb キーの削除

	export function delete_indexeddb(keyname) {

		let delete_promise = new Promise( function(resolve, reject) {

			resolve( idb_open("d", keyname) );

		}).then( function(value) {

			return value.target.result;

		}).catch( function(error) {
			//console.log(error);
		});

		return delete_promise;

	}

	// 取り出した IndexedDB から特定の値を取り出す関数
	// key が一致したら、その値を取り出す

	export function idb_finding_Values(object, key) {

		//console.log(object);
		//console.log(key);

		let return_value = "";

		for ( let n = 0; n < object.length; n++ ) {

			//console.log(object[n]);

			if ( object[n]["name"] === key ) {
				return_value = object[n]["value"];
			}

		}

		return return_value;

	}

	// IndexedDBから情報を取り出す関数

	export async function get_idb_object() {

		const load_idb_object = await load_indexeddb();

		//console.log(load_idb_object);

		// load_idb_object[0]: 何らかの情報が保存されていれば連想配列が入るが
		// 何も保存されていないと undefined になる

		let return_object;

		if ( load_idb_object[0] === undefined ) {
			return_object = new Array();
		} else {
			return_object = load_idb_object;
		}

		return return_object;

	}

