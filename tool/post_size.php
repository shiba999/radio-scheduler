<!doctype html>
<html lang="ja">

<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width,initial-scale=1" />
	<link rel="stylesheet" href="../css/style.css" type="text/css" />
	<title>post_max_size 確認</title>
</head>

<body class="note">

<header>

	<h1>post_max_size 確認</h1>

</header>

<section class="card settings">

	<h2>post_max_size 確認</h2>

	<h3>ini_get("post_max_size")</h3>
	<p><b><?php

	$pms = ini_get("post_max_size");
	//$pms = "1K";

	echo $pms;

?></b></p>
	<h3>バイト数変換</h3>
	<p><b><?php

	function parse_ini_size($size_str) {

		$size_str = trim($size_str);
		$last = strtolower( $size_str[strlen($size_str) - 1] );
		$size = (int) $size_str;

		switch($last) {
			case "g": $size *= 1024;
			case "m": $size *= 1024;
			case "k": $size *= 1024;
		}

		return $size;

	}

	echo parse_ini_size($pms);

?></b></p>

</section>

</body>
</html>