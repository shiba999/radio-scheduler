<?php

	require_once dirname(__FILE__) . "/__definition__.php";

	header("Content-Type: application/json; charset=UTF-8");

	$server_array = array(
		"ip" => SERVER_IP,
		"root_path" => PROJECT_ROOT
	);

	echo json_encode($server_array);

?>