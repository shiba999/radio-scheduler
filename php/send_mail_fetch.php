<?php

// json を受け取る場合は明示的にJSONと宣言

header("Content-Type: application/json; charset=UTF-8");

require_once dirname(__FILE__) . "/send_mail.php";

$result = system_mail();

echo $result;

?>