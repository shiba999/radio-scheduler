<?php

	if ( ! defined("PROJECT_ROOT") ) {

		// サーバーのIP

		// ip addr show | grep 'inet ' | grep -v '127.0.0.1' | awk '{print $2}' | cut -d/ -f1 | tr '\n' '|'

		// IPを全て表示した情報 ip addr show
		// grep 'inet ' IPv4 のみ抽出
		// ループバックアドレス (127.0.0.1) を除外 grep -v '127.0.0.1'
		// スペースで区切られた情報の2番目を取得 awk '{print $2}' 結果 192.168.1.10/24 のような情報を取り出せる
		// / で区切られた情報の1番目を取得 cut -d/ -f1 結果 192.168.1.10 のような情報を取り出せる
		// 文字列前後の改行やスペースを整理する xargs

		// その後 trim()
		// スペース区切りで表示される

		define( "SERVER_IP", trim( shell_exec("ip addr show | grep 'inet ' | grep -v '127.0.0.1' | awk '{print $2}' | cut -d/ -f1 | xargs") ) );

		// システムの root ディレクトリ

		define( "PROJECT_ROOT", dirname( dirname(__FILE__) ) );

	}

?>