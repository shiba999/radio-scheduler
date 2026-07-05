<?php

// oauth_setup.php（初回認証用・ブラウザからアクセスして実行）

require_once dirname(__FILE__) . "/__definition__.php";

$sendmail_file = PROJECT_ROOT . "/json/sendmail.json";
$sendmail_json = file_get_contents($sendmail_file);
$sendmail_object = json_decode($sendmail_json, true);

/*echo "<pre>";
echo print_r($_SERVER, true);
echo "</pre>";
echo "<pre>";
echo print_r($sendmail_object, true);
echo "</pre>";*/

$protocol = ( ! empty($_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== "off") ? "https://" : "http://";
$host = $_SERVER["HTTP_HOST"];
$directory_path = dirname($_SERVER["SCRIPT_NAME"]);
$redirect_uri = $protocol . $host . $directory_path . "/gmail_oauth_setup.php";

define("CLIENT_ID", trim($sendmail_object["gapi_cid"]) ?? "");
define("CLIENT_SECRET", trim($sendmail_object["gapi_cs"]) ?? "");
define("REDIRECT_URI", $redirect_uri);
define("TOKEN_FILE", dirname(dirname(__FILE__)) . "/json/gmail_token.json");

// トークン保存ファイルパーミッションチェック

/*if ( is_writable(TOKEN_FILE) ) {
	echo "<p>TOKEN_FILE: 書き込み可能</p>";
} else {
	echo "<p>TOKEN_FILE: 書き込み不可</p>";
}*/

// 認証コードが返ってきた場合 → トークン交換

if ( isset($_GET["code"]) ) {

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

// 諸情報が存在する・トークン保存先が書き込み可能であれば実行可能

if ( CLIENT_ID !== "" && CLIENT_SECRET !== "" && is_writable(TOKEN_FILE) ) {

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

} else {

	echo "<pre>トークン取得に必要な準備ができていない様です。トークン保存先の書き込み権限も確認してください。<br />";
	echo "CLIENT_ID: " . CLIENT_ID . "<br />";
	echo "CLIENT_SECRET: " . CLIENT_SECRET . "<br />";
	echo "REDIRECT_URI: " . REDIRECT_URI . "<br />";
	echo "TOKEN_FILE: " . TOKEN_FILE . "</pre>";

}

?>