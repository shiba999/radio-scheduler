<?php

// oauth_setup.php（初回認証用・ブラウザからアクセスして実行）

define("CLIENT_ID", "");
define("CLIENT_SECRET", "");
define("REDIRECT_URI", "");
define("TOKEN_FILE", "");

// 認証コードが返ってきた場合 → トークン交換

if (isset($_GET["code"])) {

	$response = file_get_contents("https://oauth2.googleapis.com/token", false,
		stream_context_create(["http" => [
			"method"  => "POST",
			"header"  => "Content-Type: application/x-www-form-urlencoded",
			"content" => http_build_query([
				"code" => $_GET["code"],
				"client_id" => CLIENT_ID,
				"client_secret" => CLIENT_SECRET,
				"redirect_uri" => REDIRECT_URI,
				"grant_type" => "authorization_code",
			])
		]])
	);

	$token = json_decode($response, true);
	file_put_contents(TOKEN_FILE, json_encode($token));

	echo "トークン取得完了。このファイルは削除してください。";
	exit;

}

// 認証URLを生成してリダイレクト

$auth_url = "https://accounts.google.com/o/oauth2/auth?" . http_build_query([
	"client_id" => CLIENT_ID,
	"redirect_uri" => REDIRECT_URI,
	"response_type" => "code",
	"scope" => "https://www.googleapis.com/auth/gmail.send",
	"access_type" => "offline",// リフレッシュトークンを取得するために必要
	"prompt" => "consent"// 毎回同意画面を出してリフレッシュトークンを確実に取得
]);

header("Location: " . $auth_url);

?>