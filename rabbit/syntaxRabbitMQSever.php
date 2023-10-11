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

	$stmt = $mydb->prepare("SELECT id, name, password FROM syntaxUsers WHERE name = ? AND password = ?");
       	$stmt->bind_param("ss", $username, $password); 

	$stmt->execute();
	$stmt->store_result();
	if ($mydb->errno != 0)
	{
		echo "failed to execute query:".PHP_EOL;
		echo __FILE__.':'.__LINE__.":error: ".$mydb->error.PHP_EOL;
		exit(0);
	}
	if($stmt->num_rows>0){
		$stmt->bind_result($id, $name, $password);
		$stmt->fetch();
	//	if(password_verify($password, $hashed_password)){
			$request = array();
			$request['Validated'] = true;
			$request['id'] = $id;
			$request['username'] = $username;
			print_r($request);
			return $request;		
	//	}
/*		else{
			 $request = array();
			 $request['Validated'] = true;
			 $request['1'] = 'one';
			 print_r($request);
		      	 return $request; 
 		}
*/	 
	}
	else{
		$request = array();
		$request['Validated'] = true;
		$request['2'] = 'two';
                         print_r($request);
                         return $request;


		}
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

