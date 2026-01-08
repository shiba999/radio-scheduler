<?php

	if ( ! defined("PROJECT_ROOT") ) {

		// サーバーのIP

		define( "SERVER_IP", $_SERVER["SERVER_ADDR"] );

		// システムの root ディレクトリ

		define( "PROJECT_ROOT", dirname( dirname(__FILE__) ) );

	}

?>