<?php

header("Content-Type: application/json; charset=UTF-8");// json を受け取る場合は明示的にJSONと宣言

$area_id = $_POST["area"] ?? "JP1";

$xml_url = "https://radiko.jp/v2/information2/" . $area_id . ".xml";
$xml_string = file_get_contents($xml_url);
$xml = simplexml_load_string($xml_string);

$i = 0;

$info_object = array(
	"xml" => "",
	"info" => array()
);

$info_object["xml"] = $xml_url;

foreach ( $xml->info as $info ) {

	$category = (string) $info["category_id"];

	// <info category_id="maitenance" category_name="メンテナンス" id="2735" important="" priority="1" station_id="ALL" station_name="ALL">...</info>
	// メンテナンス情報のみ取り出す
	// <info category_id="info" category_name="お知らせ" id="2729" important="" priority="1" station_id="ALL" station_name="ALL">...</info>
	// こちらはお知らせ情報

	if ( $category === "maitenance" ) {

		$this_array = array(
			"title" => (string) $info->title,
			"body" => (string) $info->body
		);

		array_push($info_object["info"], $this_array);

		//echo "<h1>[" . $i . "]メンテナンス情報: " . (string)$info->title . "</h1>\n";
		//echo "<p>" . (string)$info->body . "</p>\n\n";

		$i++;

	}

	if ( $i >= 3 ) {
		break;
	}

}

echo json_encode($info_object);

?>