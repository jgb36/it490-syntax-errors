#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function doLogin($username,$password)
{
	$logger = new rabbitMQClient("syntaxRabbitMQ.ini","logger");

    	$mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');

	if ($mydb->errno != 0)
	{
		echo "failed to connect to database: ". $mydb->error . PHP_EOL;
		$log = array();
                $log['where']="listener: doLogin";
                $log['error']="failed to connect to database: ". $mydb->error . PHP_EOL;
		$logger->publish($log);
		exit(0);
	}	

	echo "successfully connected to database(login)".PHP_EOL;

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
		$log = array();
                $log['where']="listener: doLogin";
                $log['error']="failed to execute query: ". $mydb->error . PHP_EOL;
                $logger->publish($log);

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

		//Calls sessionAdd
                	sessionAdd($username);
			return $request;		
		}

		else{
			 $log = array();
			 $log['Validated'] = false;

              	         $log['where']="listener: doLogin";
        	         $log['error']="password failed to verify";
        	         $logger->publish($log);
	                 print_r($log);

		      	 return $log; 
		}
	 
	}
	else{
		$log = array();
                $log['Validated'] = false;

                $log['where']="listener: doLogin";
                $log['error']="password failed to verify";
                $logger->publish($log);
                print_r($log);

                return $log;  

}


}

function userRegistration($username, $email, $password)
{
	//DB connection
        $logger = new rabbitMQClient("syntaxRabbitMQ.ini","logger");

        $mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');

        if ($mydb->errno != 0)
        {
                echo "failed to connect to database: ". $mydb->error . PHP_EOL;
		$log = array();
                $log['where']="listener: userRegistration";
                $log['error']="failed to connect to databse: ". $mydb->error . PHP_EOL;
                $logger->publish($log);

		exit(0);
        }

	echo "successfully connected to database(regis)".PHP_EOL;

	//Check for duplicate entry
	$checkDups = $mydb->prepare("SELECT *  FROM syntaxUsers WHERE name = ? OR email = ?");
	$checkDups->bind_param("ss", $username, $email);
	$checkDups->execute();

	$dup_DB_Result = $checkDups->get_result();

	//Check the database data
	if($dup_DB_Result-> num_rows > 0) {	
		//		$checkDups.close();
		$log = array();
                $log['where']="listener: userRegistration";
                $log['error']="existing account with given name: ";
                $logger->publish($log);

	return array('created' => false, 'message'=>"Registration Failed, try again");
	
	

	} 

	else {
//		$checkDups.close();

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
	//		$request['created'] = 'true';
	//		$request['uname'] = 'true';

		//Calls sessionAdd
			sessionAdd($username);
	  	return array('created' => true,'uname' => $username, 'message'=>"Registration Successfull for $username");
		}	

		else {
			//Registration fails
			$stmt->close();
			
			
			$log = array();
        	        $log['where']="listener: userRegistration";
        	        $log['error']="Registration Failed When Inserting ". $mydb->error . PHP_EOL;
	                $logger->publish($log);

		  return array('created' => false, 'message'=>"Registration Failed, try again");
		}
	
	}
}


//Session add
function sessionAdd($username) {

	//DB connection
	
        $logger = new rabbitMQClient("syntaxRabbitMQ.ini","logger");

        $mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');

        if ($mydb->errno != 0)
        {
                echo "failed to connect to database: ". $mydb->error . PHP_EOL;
		$log = array();
                $log['where']="listener: doLogin";
                $log['error']="failed to connect to database: ". $mydb->error . PHP_EOL;
                $logger->publish($log);

		exit(0);
        }

	echo "successfully connected to database(add)".PHP_EOL;

	$creationDate = time();
//        $creationDate = (string)$creationDate;
	$date = date('Y-m-d H:i:s', $creationDate);

	
	//Inserts the user info into the DB
        $stmt = $mydb->prepare("INSERT INTO sessions(userName, creationDate) VALUES (?, ?)");
	$stmt->bind_param("ss",$username, $date);
	if($stmt->execute()) {
//		$stmt.close();
//		$mydb.close();
	}
	else {
		$log = array();
                $log['where']="listener: sessionAdd";
                $log['error']="failed to insert session into db: ". $mydb->error . PHP_EOL;
                $logger->publish($log);

		echo "failed to create session for $username: ". $mydb->error . PHP_EOL;
	}

} //function add end bracket



//function delete
function sessionDelete($username) {

	//DB connection
        $logger = new rabbitMQClient("syntaxRabbitMQ.ini","logger");

        $mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');

        if ($mydb->errno != 0)
        {
                echo "failed to connect to database: ". $mydb->error . PHP_EOL;
		$log = array();
                $log['where']="listener: sessionDelete";
                $log['error']="failed to connect to database: ". $mydb->error . PHP_EOL;
                $logger->publish($log);

		exit(0);
        }

        echo "successfully connected to database(del)".PHP_EOL;


        //DELETES the user info into the DB
	$stmt = $mydb->prepare("DELETE FROM sessions WHERE userName = ?");
	$stmt->bind_param("s", $username);
	if($stmt->execute()) {	
//		$stmt->store_result();
//              $stmt.close();
//		$mydb.close();
        }
	else {
		$log = array();
                $log['where']="listener: sessionDelete";
                $log['error']="failed to delete session for $username: ";
                $logger->publish($log);

                echo "failed to delete session for $username: ". $mydb->error . PHP_EOL;
        }


} //function del end bracket


//function validation
function doValidate($username)
{
	//DB connection
        $logger = new rabbitMQClient("syntaxRabbitMQ.ini","logger");

        $mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');

        if ($mydb->errno != 0)
        {
		echo "failed to connect to database: ". $mydb->error . PHP_EOL;
		$log = array();
                $log['where']="listener: doValidate";
                $log['error']="failed to connect to database: ". $mydb->error . PHP_EOL ;
                $logger->publish($log);

                exit(0);
        }

        echo "successfully connected to database(valid)".PHP_EOL;

        //Check for duplicate entry
        $checkDups = $mydb->prepare("SELECT *  FROM sessions WHERE userName = ?");
        $checkDups->bind_param("s", $username);
        $checkDups->execute();

        $dup_DB_Result = $checkDups->get_result();

        //Check the database data
        if($dup_DB_Result-> num_rows > 0) {
//              $checkDups.close();
		return array('Validated' => true, 'message'=>"Session is Active");



	}
	else{
		$log = array();
                $log['where']="listener: doValidate";
                $log['error']="failed to locate a session: " ;
                $logger->publish($log);



		return array('Validated' => false, 'message'=>"No Sesssion Found");
	}

}
function createLeague($username, $leagueName){
       $logger = new rabbitMQClient("syntaxRabbitMQ.ini","logger");

       $mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');
       $stmt = $mydb->prepare("INSERT INTO league(ownerName, leagueName) VALUES ( ?, ?)");
       $stmt->bind_param("ss", $username, $leagueName);
       if($stmt->execute()) {
	       //Registration works
	       $lastInsertedPK = $mydb->insert_id;
	       echo "inserted id is: $lastInsertedPK";
	       $query2 = "INSERT INTO participants (playerName, leagueID) VALUES (?, ?)";
	       $stmt2 = $mydb->prepare($query2);
	       $stmt2->bind_param("si", $username, $lastInsertedPK);
	       if($stmt2->execute()){
		return array('createLeague' => true, 'message'=>"League Registration Successfull for $username");
	       }
	       else{
		$log = array();
                $log['where']="listener: createLeague";
                $log['error']="failed to create league for $username:   1 ";
                $logger->publish($log);
                return array('createLeague' => true, 'message'=>"League Registration Successfull for $username");
                echo "failed to create league for $username: ". $mydb->error . PHP_EOL;
	       }
               
    
         }else {
                $log = array();
                $log['where']="listener: createLeague";
                $log['error']="failed to create league for $username:  2 ";
                $logger->publish($log);
                return array('createLeague' => true, 'message'=>"League Registration Successfull for $username");
                echo "failed to create league for $username: ". $mydb->error . PHP_EOL;
        }


	


}

function leagueList($username){
	$mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');

	$stmt = $mydb->prepare("SELECT l.leagueName, l.draftStart, l.draftDone  from participants as p JOIN league AS l ON p.leagueID = l.id WHERE p.playerName = ? ");
        $stmt->bind_param("s", $username);
	//Check the database data
	$leagues = array();
        if($stmt->execute()) {
		$result = $stmt->get_result();
		if ($result->num_rows > 0) {

    			while ($row = $result->fetch_assoc()) {
        			$leagues[] = array(
					"leagueName" => $row["leagueName"],
					"draftStart" => $row["draftStart"],
					"draftDone" => $row["draftDone"],
        			);
			}
			return $leagues;
		}
	       	else {
    				echo "No leagues found for player: $username";
			}
        }
        else{
                $log = array();
                $log['where']="listener: listLeagues";
                $log['error']="failed to connect to DB " ;
                $logger->publish($log);
                return array('Validated' => false, 'message'=>"No Sesssion Found");
        }
}


// main function
function requestProcessor($request)
{
  echo "received request".PHP_EOL;
  var_dump($request);
  if(!isset($request['type']))
  {
	  $log = array();
          $log['where']="listener: requestProcessor";
          $log['error']="Message type not found" ;
          $logger->publish($log);

    return "ERROR: unsupported message type";
  }
  switch ($request['type'])
  {
    case "login":
	    return doLogin($request['uname'], $request['pword']);
    case "validate_session":
	    if (isset($request['uname'])){
		return doValidate($request['uname']);
	    }
	    else
	    {
		print_r(array('Validated' => false, 'message'=>"Session is Not Active"));

		return array('Validated' => false, 'message'=>"Session is Not Active");

	    }
    case 'register':
	    return userRegistration($request['uname'], $request['email'], $request['pword']);
    case 'logout':
	    return sessionDelete($request['uname']);
    case 'createLeague':
	    return createLeague($request['uname'],$request['leagueName']);
    case 'leagueList':
	    return leagueList($request['uname']);	    
  }

  $log = array();
  $log['where']="listener: requestProcessor";
  $log['error']="Unsupported message type" ;
  $logger->publish($log);

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


