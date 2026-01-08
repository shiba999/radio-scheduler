<?php

	require_once dirname(__FILE__) . "/__definition__.php";

	$server_array = array(
		"ip" => SERVER_IP,
		"root_path" => PROJECT_ROOT
	);

	echo serialize($server_array);

?>