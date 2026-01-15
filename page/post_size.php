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

<section class="card tool">

	<h2>post_max_size</h2>

	<p>ini_get("post_max_size"): <b><?php

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

	$pms = ini_get("post_max_size");

	echo $pms;

?></b> ( <b><?php

	echo parse_ini_size($pms);// バイトになおすと？

?></b> byte )</p>

</section>

</body>
</html>