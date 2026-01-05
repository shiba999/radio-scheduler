<!doctype html>
<html lang="ja">

<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width,initial-scale=1" />
	<link rel="stylesheet" href="../css/style.css" type="text/css" />
	<title>timezone 確認</title>
</head>

<body class="note">

<header>

	<h1>timezone 確認</h1>

</header>

<section class="card settings">

	<h2>サーバーデフォルト timezone</h2>

	<h3>date_default_timezone_get()</h3>
	<p><b><?php echo date_default_timezone_get(); ?></b></p>
	<h3>date("Y/m/d (D) G:i:s")</h3>
	<p><b><?php echo date("Y/m/d (D) G:i:s"); ?></b></p>

	<h2>設定した timezone</h2>

	<h3>date_default_timezone_get()</h3>
	<p><b><?php

	$schedule_file = "../json/settings.json";
	$schedule_json = file_get_contents($schedule_file);
	$schedule_object = json_decode($schedule_json, true);

	// $schedule_object["timezone"]

	$timezone_text = "UTC";

	if ( isset($schedule_object["timezone"]) ) {
		$timezone_text = $schedule_object["timezone"];
	}

	date_default_timezone_set($timezone_text);

	echo $timezone_text;

?></b></p>
	<h3>date("Y/m/d (D) G:i:s")</h3>
	<p><b><?php echo date("Y/m/d (D) G:i:s"); ?></b></p>

</section>

</body>
</html>