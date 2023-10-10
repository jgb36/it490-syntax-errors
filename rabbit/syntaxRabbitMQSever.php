#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function doLogin($username,$password)
{
    	$mydb = new mysqli('25.3.222.177','jay','syn490-jay-errors','syntaxErrors490');

	if ($mydb->errno != 0)
	{
		echo "failed to connect to database: ". $mydb->error . PHP_EOL;
		exit(0);
	}	

	echo "successfully connected to database".PHP_EOL;

	$query = "select * from students;";

	$response = $mydb->query($query);
	if ($mydb->errno != 0)
	{
		echo "failed to execute query:".PHP_EOL;
		echo __FILE__.':'.__LINE__.":error: ".$mydb->error.PHP_EOL;
		exit(0);
	}
    // check password
    return true;
    //return false if not valid
}

function requestProcessor($request)
{
  echo "received request".PHP_EOL;
  var_dump($request);
  if(!isset($request['type']))
  {
    return "ERROR: unsupported message type";
  }
  switch ($request['type'])
  {
  case "Login":
      return doLogin($request['username'],$request['password']);
    case "validate_session":
      return doValidate($request['sessionId']);
  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

$server = new rabbitMQServer("syntaxRabbitMQ.ini","syntaxServer");

echo "syntaxRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "syntaxRabbitMQServer END".PHP_EOL;
exit();
?>

