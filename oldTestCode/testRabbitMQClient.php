#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$client = new rabbitMQClient("testRabbitMQ.ini","testServer");


$request = array();
$request['type'] = "Login";
$request['username'] = "jay";
$request['password'] = "bestPWNoPWisBetter";
$request['message'] = $msg;
print_r($response = $client->send_request($request));
if($response['Validated'] = 'true')
{
	$_SESSION['id'] = $response['id'];
        $_SESSION['username'] = $response['username'];
	session_start();
}
else
{
	echo "Invalid Session ";
}
$response = $client->publish($request);

echo "client received response: ".PHP_EOL;
print_r($response);
echo "\n\n";

echo $argv[0]." END".PHP_EOL;

