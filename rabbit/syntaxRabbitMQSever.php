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

	// Hashes the Password
	$hashPassword = password_hash($password, PASSWORD_DEFAULT);

	$stmt = $mydb->prepare("SELECT id, name, password FROM syntaxUsers WHERE name = ?");
	$stmt->bind_param("s", $username);	

	$stmt->execute();
	$stmt->store_result();
	if ($mydb->errno != 0)
	{
		echo "failed to execute query:".PHP_EOL;
		echo __FILE__.':'.__LINE__.":error: ".$mydb->error.PHP_EOL;
		exit(0);
	}
	if($stmt->num_rows>0){
		$stmt->bind_result($id, $name, $hashPassword);
		$stmt->fetch();
		if(password_verify($password, $hashPassword)){
			$request = array();
			$request['Validated'] = true;
			$request['id'] = $id;
			$request['uname'] = $username;
			print_r($request);
			return $request;		
		}

		else{
			 $request = array();
			 $request['Validated'] = false;
			 $request['1'] = 'one';
			 print_r($request);
		      	 return $request; 
		}
	 
	}
	else{
		$request = array();
		$request['Validated'] = 'false';
		$request['2'] = 'two';
                         print_r($request);
                         return $request;


	}
	$request = array();
                $request['Validated'] = 'false';
                $request['2'] = 'two';
                         print_r($request);
                         return $request;


}

function userRegistration($username, $email, $password)
{
        //DB connection
        $mydb = new mysqli('25.3.222.177','jay','syn490-jay-errors','syntaxErrors490');

        if ($mydb->errno != 0)
        {
                echo "failed to connect to database: ". $mydb->error . PHP_EOL;
                exit(0);
        }

	echo "successfully connected to database".PHP_EOL;

	//Hash the password for security
	$hashPassword = password_hash($password, PASSWORD_DEFAULT);

	//Inserts the user info into the DB
	$stmt = $mydb->prepare("INSERT INTO syntaxUsers(name, email, password) VALUES (?, ?, ?)");
	$stmt->bind_param("sss", $username, $email, $hashPassword);

	//Execute the statement to see if it works
	if($stmt->execute()) {
		//Registration works
		$stmt->close();
	//	$stmt->store_result();
		$mydb->close();
		$request['created'] = 'true';
		$request['uname'] = 'true';
	//	return true;
	//	print_r("working here");
	  return array('created' => true,'uname' => true, 'message'=>"Registration Successfull for $username");
	}
	else {
		//Registration fails
		$stmt->close();
		$stmt->close();
		$request['created'] = 'false';
	//	return false;
	  return array('created' => false, 'message'=>"Registration Failed, try again");
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
    case "login":
	    return doLogin($request['uname'], $request['pword']);
    case "validate_session":
	    return doValidate($request['sessionId']);
    case 'register':
  	    return userRegistration($request['uname'], $request['email'], $request['pword']); 	    
  }

  
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
  
}

//HOST SERVER
$server = new rabbitMQServer("syntaxRabbitMQ.ini","syntaxServer");

echo "syntaxRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "syntaxRabbitMQServer END".PHP_EOL;
exit();


//Code END



?>

