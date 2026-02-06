<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");// json を受け取る場合は明示的にJSONと宣言

$settings_json = file_get_contents("../json/settings.json");
$settings_object = json_decode($settings_json, true);
$amixer_path = $settings_object["amixer_path"];

$name = $_POST["name"];
$value = $_POST["value"];
$type = $_POST["type"];

$exec_command = $amixer_path . " -c 0 -M sset '" . $name . "' " . $value;

if ( $type == "volume" ) {
	$exec_command .= "%";
}

$output = exec($exec_command);

$return_array = array(
	"result" => "error",
	"mute" => "",
	"volume" => ""
);

if ( preg_match_all('/\[(on|off)\]/i', $output, $matches) ) {
	$return_array["result"] = "success";
	$return_array["mute"] = $matches[1][0];
}

if ( preg_match_all('/\[(\d+)%\]/', $output, $matches) ) {
	$return_array["result"] = "success";
	$return_array["volume"] = $matches[1][0];
}

echo json_encode($return_array);

?>