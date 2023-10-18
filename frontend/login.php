<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_POST))
{
	$msg = "NO POST MESSAGE SET, POLITELY FUCK OFF";
	echo json_encode($msg);
	exit(0);
}
$request = $_POST;
$response = "unsupported request type, politely FUCK OFF";
switch ($request["type"])
{
	case "login":
		$response = "login, yeah we can do that";
		$client = new rabbitMQClient("testRabbitMQ.ini","syntaxServer");
		$response = $client->send_request($request);
		'<script>console.log(JSON.parse($response)); </script>';
		if($response['Validated'] === true)
		{
		        $_SESSION['id'] = $response['id'];
		        $_SESSION['uname'] = $response['uname'];
		        session_start();
		}
		else
		{
		        echo '<script>console.log(response); </script>';
		}
		//$response = $client->publish($request);
		break;
}
echo json_encode($response);
exit(0);

?>
