<?php

require_once dirname(__FILE__) . "/send_mail.php";

$result = system_mail("システム調整報告です", "");

echo "\n\n" . $result . "\n\n";

?>