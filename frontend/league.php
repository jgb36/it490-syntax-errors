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
	case "isOwner":
		$client = new rabbitMQClient("testRabbitMQ.ini", "syntaxServer");
                session_start();
                $request['uname']=$_SESSION['uname'];
		$response = $client->send_request($request);
		break;
	case 'setLeagueDraftDone':
		$client = new rabbitMQClient("testRabbitMQ.ini", "syntaxServer");
		$response = $client->send_request($request);
		break;
	case 'checkUserDraft':
		session_start();
		$requst['uname']=$_SESSION['uname'];
		$client = new rabbitMQClient("testRabbitMQ.ini", "syntaxServer");
		$response = $client->send_request($request);
		break;
	case 'getTeamData':
		$client = new rabbitMQClient("testRabbitMQ.ini", "syntaxServer");
                $response = $client->send_request($request);
		break;
	case 'draft':
		session_start();
                $requst['uname']=$_SESSION['uname'];
		$client = new rabbitMQClient("testRabbitMQ.ini", "syntaxServer");
                $response = $client->send_request($request);
                break;

}
echo json_encode($response);
exit(0);

?>

