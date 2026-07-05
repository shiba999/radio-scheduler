<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function system_mail($subject = "", $body = "") {

	// 引数の整理 ()

	$current_time = date("Y年m月d日 H時i分s秒");// スパム認定対策の時刻表記
	$token = bin2hex(random_bytes(16));// スパム認定対策のトークン

	$subject = trim($subject) === ""
		? "【らぢ助】 システム通知テスト (" . $current_time . ")"
		: $subject
	;

	$body = trim($body) === ""
		? "らぢ助 (Radio Scheduler) をご利用いただきありがとうございます。

当アプリは、ラジオの操作・予約再生が可能なアプリです。
設定されたメールサーバーから正常にメールが送信できることを確認しました。

* 送信日時: " . $current_time . "
* 送信トークン: " . $token . "

このメールが届いていれば、cron実行時などシステムエラーや不具合が発生した場合、
同じ設定でエラー通知メールが届くようになります。

--------------------------------------------------
ラジオの操作・予約再生が可能なアプリ
らぢ助 (Radio Scheduler)
Github: https://github.com/shiba999/radio-scheduler
--------------------------------------------------"
		: $body
	;

/*	echo "<pre>";
	echo $subject;
	echo "</pre>";
	echo "<pre>";
	echo $body;
	echo "</pre>";*/

	$permission = false;

	$return_object = array(
		"result" => false,
		"message" => "メールの設定に不備があるようです"
	);

	// メール設定の読み込み

	//echo dirname(dirname(__FILE__)) . "/json/sendmail.json";

	$sendmail_file = dirname(dirname(__FILE__)) . "/json/sendmail.json";
	$sendmail_json = file_get_contents($sendmail_file);
	$sendmail_object = json_decode($sendmail_json, true);

/*	echo "<pre>";
	echo print_r($sendmail_object, true);
	echo "</pre>";*/

	// メール送信に必要な情報が揃っているか確認 (送信先, 送信元, 送信方法)

	$to = $sendmail_object["sm_address"] ?? "";// 送信先
	$admin_mail = $sendmail_object["sm_from"] ?? "";// 送信元
	$admin_name = $sendmail_object["sm_from_name"] ?? "らぢ助 Administrator";// 送信者名
	$method = $sendmail_object["sm_method"] ?? "";// 送信方法

	$flow = $admin_mail . " > " . $to;

	if ( trim($to) !== "" && trim($admin_mail) !== "" && trim($method) !== "" ) {
		$permission = true;
	} else {
		$return_object["message"] = "設定が不足しています";
	}

	// 送信方法による分岐

	if ( $permission && trim($method) == "phpm" ) {

		$host = $sendmail_object["phpm_host"] ?? "";// サーバーHOST
		$user = $sendmail_object["phpm_user"] ?? "";// サーバーユーザー名
		$pass = $sendmail_object["phpm_pass"] ?? "";// サーバーパスワード
		$auth = $sendmail_object["phpm_auth"] ?? false;// AUTH (SMTP認証)
		$security = $sendmail_object["phpm_secu"] ?? "";// SSL / TLS / NO
		$port = $sendmail_object["phpm_port"] ?? 587;// サーバーPORT

		$phpmailer_dir = __DIR__ . "/php-mailer/src";
		$main_file = $phpmailer_dir . "/PHPMailer.php";

		// 設定項目が設定されている事と PHPMailer ライブラリが存在することが条件

		if ( trim($host) != "" && trim($user) !== "" && trim($pass) !== "" && $port && file_exists($main_file) ) {

			// ***** PHPMailer でメール送信 *****

			require $phpmailer_dir . "/Exception.php";
			require $phpmailer_dir . "/PHPMailer.php";
			require $phpmailer_dir . "/SMTP.php";

			$mail = new PHPMailer(true);

			try {

				// SMTP設定

				$mail->isSMTP();
				$mail->Host = $host;
				$mail->SMTPAuth = $auth;
				$mail->Username = $user;
				$mail->Password = $pass;
				$mail->SMTPSecure = $security;
				$mail->Port = $port;

				$mail->CharSet = "UTF-8";

				// 送信者・宛先 $admin

				$mail->setFrom($admin_mail, $admin_name);
				$mail->addAddress($to);// 通知先

				// 本文

				$mail->Subject = $subject;
				$mail->Body = $body;

				$mail->send();

				$return_object = array(
					"result" => true,
					"message" => "success"
				);

				$return_object["result"] = true;
				$return_object["message"] = "メールを送信しました ( PHPMailer ) " . $flow;

				//return true;

			} catch (Exception $e) {

				$return_object["message"] = "メールの送信に失敗しました ( PHPMailer ) " . $flow . " " . $mail->ErrorInfo;
				//return false;

			}

		}

	} else if ( $permission && trim($method) == "gapi" ) {

		// ***** Gmail api でメール送信 *****

		$client_id = $sendmail_object["gapi_cid"] ?? "";// サーバーHOST
		$client_secret = $sendmail_object["gapi_cs"] ?? "";// サーバーユーザー名
		$token_file = dirname(dirname(__FILE__)) . "/json/gmail_token.json";

		//echo $client_id;
		//echo $client_secret;
		//echo $token_file;

		if ( is_writable($token_file) != "" && trim($client_id) !== "" && trim($client_secret) !== "" ) {

			// アクセストークンの自動更新用関数

			function get_access_token($token_file, $client_id, $client_secret): string {

				$token = json_decode(file_get_contents($token_file), true);

				// 有効期限チェック ( expires_in は 3600秒 )

				if ( !isset($token["expires_at"]) || time() >= $token["expires_at"] - 60 ) {

					// リフレッシュトークンで新しいアクセストークンを取得

					$response = file_get_contents("https://oauth2.googleapis.com/token", false,
						stream_context_create(["http" => [
							"method" => "POST",
							"header" => "Content-Type: application/x-www-form-urlencoded",
							"content" => http_build_query([
								"client_id" => $client_id,
								"client_secret" => $client_secret,
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

			$access_token = get_access_token($token_file, $client_id, $client_secret);// トークン更新チェック

			// メール送信者情報

			$encoded_name = "=?UTF-8?B?" . base64_encode($admin_name) . "?=";
			$from = $encoded_name . " <" . $admin_mail . ">";

			// RFC2822形式のメール文字列を作成

			$raw_mail = "To: " . $to . "\r\n"
				. "From: " . $from . "\r\n"
				. "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n"
				. "Content-Type: text/plain; charset=UTF-8\r\n"
				. "MIME-Version: 1.0\r\n"// MIMEバージョンがあるとより安全
				. "\r\n"
				. $body
			;

			// Base64url エンコード (Gmail API 仕様)

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

			//return $response !== false;

			if ($response) {
				$return_object["result"] = true;
				$return_object["message"] = "メールを送信しました ( Gmail API ) " . $flow;
			} else {
				$return_object["message"] = "メールの送信に失敗しました ( Gmail API ) " . $flow;
			}

		}

	} else if ( $permission && trim($method) == "mbsm" ) {

		// ***** mb_send_mail() でメール送信 *****

		// 文字化け対策

		mb_language("Japanese");
		mb_internal_encoding("UTF-8");

		// ヘッダー情報

		$headers = [
			"From" => mb_encode_mimeheader($admin_name) . " <" . $admin_mail . ">",
			"Reply-To" => $admin_mail,
			"MIME-Version" => "1.0",
			"Content-Type" => "text/plain; charset=UTF-8",
			"Content-Transfer-Encoding" => "8bit",
			"X-Mailer" => "PHP/" . phpversion()
		];

		// 配列を改行コード (CRLF) で結合

		$header_string = "";

		foreach ($headers as $key => $value) {
			$header_string .= $key . ": " . $value . "\r\n";
		}

		// 追加のコマンドラインパラメータ (-fフラグ)
		// サーバーに設定されたドメイン ( apache@localhost とか ) を宛先と判断されることを防ぎ
		// 受信側サーバーが誤認することを防ぐ設定
		// -f の後にスペースを入れないという諸説もある (ここではスペースを入れている)

		$additional_params = "-f " . $admin_mail;

		// 送信実行

		$result = mb_send_mail($to, $subject, $body, $header_string, $additional_params);

		if ($result) {
			$return_object["result"] = true;
			$return_object["message"] = "メールを送信しました ( mb_send_mail() ) " . $flow;
		} else {
			$return_object["message"] = "メールの送信に失敗しました ( mb_send_mail() ) " . $flow;
		}

	}

/*	echo "<pre>";
	echo print_r($return_object, true);
	echo "</pre>";*/

	return json_encode($return_object);

}

?>