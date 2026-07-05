<?php

require __DIR__ . "/settings.php";

// メール送信関数 1
// PHPMailer を使用した SMTP メール送信

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function smtp_mail( string $to, string $title, string $body, string $admin = FROM_NAME ): bool {

	//require "./php-mailer/src/Exception.php";
	//require "./php-mailer/src/PHPMailer.php";
	//require "./php-mailer/src/SMTP.php";

	require __DIR__ . "/php-mailer/src/Exception.php";
	require __DIR__ . "/php-mailer/src/PHPMailer.php";
	require __DIR__ . "/php-mailer/src/SMTP.php";

	$mail = new PHPMailer(true);

	try {

		// SMTP設定

		$mail->isSMTP();
		$mail->Host = SM_HOST;
		$mail->SMTPAuth = SM_AUTH;
		$mail->Username = SM_USER;
		$mail->Password = SM_PASS;
		$mail->SMTPSecure = SM_SECU;
		$mail->Port = SM_PORT;

		$mail->CharSet = "UTF-8";

		// 送信者・宛先 $admin

		$mail->setFrom(SM_FROM, $admin);
		$mail->addAddress($to);// 通知先

		// 本文

		$mail->Subject = $title;
		$mail->Body = $body;

		$mail->send();

		return true;

	} catch (Exception $e) {

		//error_log("メール送信失敗: " . $mail->ErrorInfo);
		return false;

	}

}

// メール送信関数 2
// Gmail api を使用する

// 予め Google Cloud console から、クライアントID と
// クライアントシークレットの取得が必要。
// 初回は gmail_oauth_setup.php から認証を行う必要あり

// アクセストークンの自動更新

function get_access_token(): string {

	$token_file = TOKEN_FILE;
	$token = json_decode(file_get_contents($token_file), true);

	echo "<pre>";
	echo print_r($token, true);
	echo "</pre>";

	// 有効期限チェック ( expires_in は 3600秒 )

	if ( !isset($token["expires_at"]) || time() >= $token["expires_at"] - 60 ) {

		// リフレッシュトークンで新しいアクセストークンを取得

		$response = file_get_contents("https://oauth2.googleapis.com/token", false,
			stream_context_create(["http" => [
				"method" => "POST",
				"header" => "Content-Type: application/x-www-form-urlencoded",
				"content" => http_build_query([
					"client_id" => CLIENT_ID,
					"client_secret" => CLIENT_SECRET,
					"refresh_token" => $token["refresh_token"],
					"grant_type" => "refresh_token"
				])
			]])
		);

		$new_token = json_decode($response, true);

		// 有効期限を付加して保存

		$token["access_token"] = $new_token["access_token"];
		$token["expires_at"] = time() + $new_token["expires_in"];
		file_put_contents($token_file, json_encode($token));

	}

	return $token["access_token"];

}

// メール送信関数

function gmail_api_mail( string $to, string $title, string $body, string $admin = FROM_NAME ): bool {

	$access_token = get_access_token();
	//$from = GM_FROM;

	// メール送信者情報

	$encoded_name = "=?UTF-8?B?" . base64_encode(FROM_NAME) . "?=";
	$from = $encoded_name . " <" . $admin . ">";

	// RFC2822形式のメール文字列を作成

	$raw_mail = "To: " . $to . "\r\n"
		. "From: " .$from . "\r\n"
		. "Subject: =?UTF-8?B?" . base64_encode($title) . "?=\r\n"
		. "Content-Type: text/plain; charset=UTF-8\r\n"
		. "MIME-Version: 1.0\r\n"// MIMEバージョンがあるとより安全
		. "\r\n"
		. $body
	;

	// Base64urlエンコード ( Gmail API の仕様 )

	$encoded = rtrim(strtr(base64_encode($raw_mail), "+/", "-_"), "=");

	$response = file_get_contents(
		"https://gmail.googleapis.com/gmail/v1/users/me/messages/send",
		false,
		stream_context_create(["http" => [
			"method" => "POST",
			"header" => implode("\r\n", [
				"Authorization: Bearer " . $access_token,
				"Content-Type: application/json",
			]),
			"content" => json_encode(["raw" => $encoded])
		]])
	);

	//echo "<pre>";
	//echo print_r($response, true);
	//echo "</pre>";

	return $response !== false;

}



?>