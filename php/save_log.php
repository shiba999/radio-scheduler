<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once dirname(__FILE__) . "/__definition__.php";

// ファイルが無くても保存してくれる。
// 但しファイルが無い場合は、保存先ディレクトリが書き込み権限が無いと保存できない。
// ディレクトリに許可を与えたくない場合は、予めファイルを用意して
// ファイル自体に書き込み権限が必要。

$log_file = PROJECT_ROOT . "/log/test.log";
$max_lines = 10;

$cron_log = date("Y-m-d H:i:s") . " cron executed" . PHP_EOL;

// 既存ログ取得

$lines = [];

if ( file_exists($log_file) ) {
	$lines = file($log_file, FILE_IGNORE_NEW_LINES);
}

// 新規ログ追加

$lines[] = trim($cron_log);

// 行数制限

if ( count($lines) > $max_lines ) {
	$lines = array_slice($lines, -$max_lines);
}

// 保存

$result = file_put_contents(
	$log_file,
	implode(PHP_EOL, $lines) . PHP_EOL,
	LOCK_EX
);

echo $result . "<br />" . $cron_log;

?>