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
	case "createLeague":
		$response = "League has been successfully created";
		$client = new rabbitMQClient("testRabbitMQ.ini","syntaxServer");
		session_start();
                $request['uname']=$_SESSION['uname'];
		$response = $client->send_request($request);
                //$response = $client->publish($request);
		break;

	case "leagueList":
		$response = "Showing your current leagues";
		$client = new rabbitMQClient("testRabbitMQ.ini", "syntaxServer");
		session_start();
		$request['uname']=$_SESSION['uname'];
                $response = $client->send_request($request);
		break;
}
echo json_encode($response);
exit(0);

?>

