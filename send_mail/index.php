<?php

$file_path = __DIR__ . "/gmail_oauth_setup.php";

if ( file_exists($file_path) ) {
	echo "API 認証ファイルがまだ存在していますので削除してください。";
} else {
	header("Location: ../");
	exit;
}

?>