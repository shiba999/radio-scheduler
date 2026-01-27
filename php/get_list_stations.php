<?php


header("Content-Type: application/json; charset=UTF-8");// json を受け取る場合は明示的にJSONと宣言

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// エリアID を取得する関数

function radiko_auth(): array {

	$AUTHKEY = 'bcd151073c03b352e1ef2fd66c32209da9ca0afa';

	// Cookieを共有するための一時ファイル

	$cookie = sys_get_temp_dir() . '/radiko_cookie_' . getmypid() . '.txt';

	// --- auth1 ---
	$ch = curl_init('https://radiko.jp/v2/api/auth1');
	$headers = [
		'X-Radiko-App: pc_html5',
		'X-Radiko-App-Version: 0.0.1',
		'X-Radiko-User: dummy_user',
		'X-Radiko-Device: pc',
	];

	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER => true,// ← ヘッダも取得
		CURLOPT_HTTPHEADER => $headers,
		CURLOPT_COOKIEJAR => $cookie,
		CURLOPT_COOKIEFILE => $cookie,
		CURLOPT_TIMEOUT => 10
	]);

    $res = curl_exec($ch);

	if ($res === false) {
		throw new Exception('auth1 curl error: ' . curl_error($ch));
	}

	$hsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$res_headers = substr($res, 0, $hsize);
	curl_close($ch);

	// 必要ヘッダ抽出（大小無視）

	$authtoken = $keyoffset = $keylength = null;

	foreach (explode("\r\n", $res_headers) as $line) {
		if (stripos($line, 'x-radiko-authtoken:') === 0) {
			$authtoken = trim(substr($line, strlen('x-radiko-authtoken:')));
		} elseif (stripos($line, 'x-radiko-keyoffset:') === 0) {
			$keyoffset = (int)trim(substr($line, strlen('x-radiko-keyoffset:')));
		} elseif (stripos($line, 'x-radiko-keylength:') === 0) {
			$keylength = (int)trim(substr($line, strlen('x-radiko-keylength:')));
		}
	}

	if (!$authtoken || $keyoffset === null || $keylength === null) {
		throw new Exception("auth1 headers missing:\n".$res_headers);
	}

	// --- パーシャルキー生成 ---
	// 重要: substr（バイト/ASCII前提）を使う。mb_substrは使わない。

	$partial = substr($AUTHKEY, $keyoffset, $keylength);
	$partialkey = base64_encode($partial); // base64_encode は改行を入れません

	// --- auth2 ---

	$ch2 = curl_init('https://radiko.jp/v2/api/auth2');

	$headers2 = [
		'X-Radiko-AuthToken: ' . $authtoken,
		'X-Radiko-Partialkey: ' . $partialkey,
		'X-Radiko-User: dummy_user',
		'X-Radiko-Device: pc'
	];

	curl_setopt_array($ch2, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER => false,
		CURLOPT_HTTPHEADER => $headers2,
		CURLOPT_COOKIEJAR => $cookie,
		CURLOPT_COOKIEFILE => $cookie,
		CURLOPT_TIMEOUT => 10
	]);

	$areaInfo = curl_exec($ch2);

	if ($areaInfo === false) {
		throw new Exception('auth2 curl error: ' . curl_error($ch2));
	}

	curl_close($ch2);

	@unlink($cookie);

	$areaInfo = trim($areaInfo);// 例: "JP13,東京都,tokyo Japan"

	if (stripos($areaInfo, 'incorrect') !== false) {
		throw new Exception("auth2 failed: $areaInfo\n(partial=$partial, b64=$partialkey, offset=$keyoffset, len=$keylength)");
	}

    $areaId = explode(',', $areaInfo)[0] ?? '';

	if ($areaId === '') {
		throw new Exception("failed to parse areaId from: $areaInfo");
	}

	return [$authtoken, $areaId, $areaInfo];

}


// 正規表現で URL のみ置換（単語境界で安全に置き換え）

function replace_http_regex( string $text ): string {

	// http:// で始まる URL のみを https:// に置換

	return preg_replace('/\bhttp:\/\//i', "https://", $text);

}


// 放送局リスト取得

function get_list_broadcasting_stations($id) {

	// https://radiko.jp/v2/station/list/JP13.xml
	// https://radiko.jp/v2/station/list/JP1.xml

	$xml = file_get_contents("https://radiko.jp/v2/station/list/" . $id . ".xml");
	$stations = [];

	if ($xml) {

		$dom = new DOMDocument();
		$dom->loadXML($xml);

		foreach ($dom->getElementsByTagName("station") as $st) {

			$this_id = $st->getElementsByTagName("id")->item(0);
			$this_name = $st->getElementsByTagName("name")->item(0);
			$this_logo = $st->getElementsByTagName("logo_large")->item(0);
			$this_href = $st->getElementsByTagName("href")->item(0);

			// 取得したロゴの URL http を https に置き換え
			// http のロゴはブラウザで開いた場合 https へリダイレクトされていた
			// 置換関数へはノードの値を取り出してから送る

			$this_logo = replace_http_regex( $this_logo->nodeValue );

			// 抽出した情報を収納

			if ( $this_id && $this_name ) {

				$temp_array = array();

				$temp_array["id"] = $this_id->nodeValue;
				$temp_array["name"] = $this_name->nodeValue;
				$temp_array["logo"] = $this_logo;
				$temp_array["href"] = $this_href->nodeValue;
				//$stations[] = $id_node->nodeValue;

				array_push($stations, $temp_array);

			}

		}

	}

	return $stations;

}

// エリアID の取得

$radio_stations_object = array();

try {

	[$token, $area, $info] = radiko_auth();
	//echo "TOKEN: $token\nAREA: $area\nINFO: $info\n";
	//echo $info;// JP13,東京都,tokyo Japan

	// , で分割して JP** を取り出す

	$info_explode = explode(",", $info);
	$area_id = $info_explode[0];

	//echo $area_id;// JP13,東京都,tokyo Japan

	$radio_stations_object["stations"] = get_list_broadcasting_stations($area_id);
	$radio_stations_object["id"] = $area_id;
	$radio_stations_object["area_a"] = $info_explode[1];
	$radio_stations_object["area_b"] = $info_explode[2];

} catch (Exception $e) {

	//http_response_code(500);
	//echo nl2br(htmlspecialchars($e->getMessage()));

	$radio_stations_object["stations"] = array();
	$radio_stations_object["id"] = "";
	$radio_stations_object["area_a"] = "";
	$radio_stations_object["area_b"] = "";

}

// json で JavaScript へ返す

echo json_encode($radio_stations_object);

?>