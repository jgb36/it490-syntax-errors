#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
error_reporting(E_ALL);
ini_set('display_errors', 1);

		$client = new rabbitMQClient("syntaxRabbitMQ.ini","dmz");
		
$request['type']='getData';
echo "before sent";
		$response = $client->send_request($request);
             echo var_dump($response);

?>
