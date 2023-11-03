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
		       checkForTeamPlayerData();
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

	$stmt = $mydb->prepare("SELECT l.id,l.leagueName, l.draftStart, l.draftDone  from participants as p JOIN league AS l ON p.leagueID = l.id WHERE p.playerName = ? ");
        $stmt->bind_param("s", $username);
	//Check the database data
	$leagues = array();
        if($stmt->execute()) {
		$result = $stmt->get_result();
		if ($result->num_rows > 0) {

    			while ($row = $result->fetch_assoc()) {
				$leagues[] = array(
					"id"=>$row["id"],
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
//function draftList(){
//	checkForTeamPlayerData()


//}

function checkForTeamPlayerData(){
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
        $checkForData = $mydb->prepare("SELECT *  FROM team");
        $checkForData->execute();
        $data_DB_Result = $checkForData->get_result();
	$request=array();
        $request['type']='getData';
	$dmz = new rabbitMQClient("syntaxRabbitMQ.ini","dmz");
	echo "before getting dmz response".PHP_EOL;

        $allTheData = $dmz->send_request($request);
	echo "after getting dmz response".PHP_EOL;
	$response = json_decode($allTheData);
	var_dump($response);
	if($data_DB_Result-> num_rows < 64) {
	 	foreach($response as $teamInfo){
			$teamName =$teamInfo->name;
			$teamAlias = $teamInfo->alias;
			$offenseDefense = $teamInfo->offenseDefense;
			$teamQuery = "INSERT INTO team(teamName,offenseDefense) values (?,?)";
			$stmt = $mydb->prepare($teamQuery);
			$stmt->bind_param('ss',$teamName,$offenseDefense);
			$stmt->execute();
			$teamId = $mydb->insert_id;
			foreach ($teamInfo->data as $playerData){
				$playerName = $playerData[0];
				$position = $playerData[1];
				$offenseDefense = $playerData[2];
				$jersey = $playerData[3];
				$playerQuery= "INSERT INTO player(playerName,position, offenseDefense,JerseyNum, teamId) VALUES (?,?,?,?,?)";
				$stmt=$mydb->prepare($playerQuery);
				$stmt->bind_param('sssii',$playerName,$position,$offenseDefense,$jersey,$teamId);
				$stmt->execute();
			}
		}
	}else if($data_DB_Result-> num_rows == 64){
		foreach($response as $teamInfo){
                        $teamName =$teamInfo['name'];       
                        foreach ($teamInfo['name'] as $playerData){

                                $playerName = $playerData[0];
                                $position = $playerData[1];
                                $offenseDefense = $playerData[2];
                                $jersey = $playerData[3];
                                $updatePlayer= "UPDATE player set position = ? WHERE playerName = ? AND JerseyNum = ?";
                                $stmt=$mydb->prepare($playerQuery);
                                $stmt->bind_param('ssi',$position,$playerName,$jersey);
                                $stmt->execute();
                        }
                }
	}
	$mydb->close();
	exit(0);
}


function showInvites($userName){
	$mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');

         $stmt = $mydb->prepare("SELECT i.id,l.leagueName,i.ownerName  from invites as i JOIN league AS l ON i.leagueId = l.id WHERE i.playerName = ? ");
         $stmt->bind_param("s", $username);
         //Check the database data
         $leagues = array();
         if($stmt->execute()) {
                 $result = $stmt->get_result();
                 if ($result->num_rows > 0) {
 
                         while ($row = $result->fetch_assoc()) {
                                 $invites[] = array(
                                         "id"=>$row["id"],
                                         "leagueName" => $row["leagueName"],
                                         "ownerName" => $row["ownerName"],
                                         
                                 );
                         }
                         return $invites;
                 }
                 else {
                                 echo "No invites found for player: $username";
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
function inviteSend($id,$invitedName){
		$mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');
		$teamQuery = "INSERT INTO invites(invitedName,leagueId) values (?,?)";
		$stmt = $mydb->prepare($teamQuery);
		$stmt->bind_param('si',$invitedName,$id);
                $stmt->execute();

	



}
function leagueDraftDone($leagueId){
	$mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');
	$query = "SELECT COUNT(DISTINCT id) as participants_count
          FROM participants
	  WHERE leagueID = $league_id";
	$result = $mydb->query($query);
	$row = $result->fetch_assoc();
	$participants_count = $row['participants_count'];
	$query = "SELECT COUNT(*) as drafts_count
              FROM user_drafts ud
              WHERE ud.league_id = $league_id";	
	$draftedCount = $mydb->query($query);
	$row = $result->fetch_assoc();
	$drafts_count = $row['participants_count'];
	$expected_drafts = $participants_count * 2;
	if ($drafts_count === $expected_drafts) {
		$query = "UPDATE league SET draftDone = 1 WHERE id = $league_id";
		if ($mydb->query($query) === true) {
			$return = array();
			$return['done']=true;
		} else {
    			echo "Error updating draftDone: " . $mydb->error;
		}
		$return = array();
                $return['done']=false;


	}
}

function isOwner($username,$leagueId){
	$mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');
	$query = "SELECT ownerName FROM league WHERE id = $league_id and ownerName = $username";
        $stmt = $mydb->prepare($query);
	$stmt->execute();

	if ($stmt->rowCount() > 0) {
    		$result=array();
		$result['Owner']=true;
    		return $result;
	} else {
    	
    		$result=array();
                $result['Owner']=false;
                return $result;
	}



}
function checkUserDraft($username,$leagueId){
	$mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');
	$query = "SELECT ownerName FROM user_drafts WHERE leagueId = $league_id and ownerName = $username";
	$stmt = $mydb->prepare($query);
	$stmt->execute();
	if($stmt->rowCount()>0){
		$result=array();
                $result['done']=true;
                return $result;
	}else{
		$result=array();
                $result['done']=true;
                return $result;

	}


}


function getTeamData($leagueId){
        $mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');
	$query = "SELECT id, teamName, offenseDefense
	FROM team
	WHERE id NOT IN (
    		SELECT DISTINCT offense_team_id FROM user_drafts
		WHERE offense_team_id IS NOT NULL
		AND leagueId = $leagueId
	)
	AND offenseDefense = 'offense'
	UNION
	SELECT id, teamName, offenseDefense
	FROM team
	WHERE id NOT IN (
    		SELECT DISTINCT defense_team_id FROM user_drafts
		WHERE defense_team_id IS NOT NULL
		AND leagueId = $leagueId
	)
	AND offenseDefense = 'defense';";
	$stmt = $mydb->prepare($query);
        $stmt->execute();
	$result = $stmt->get_results();
	$results = [];
	while ($row = $result->fetch_assoc()){
		$resultRow=[
			"id"=>$row["id"],
			"teamName"=>$row["teamName"],
			"offenseDefense"=>$row["offenseDefense"],
		];
		$results[]=$resultRow;
	}
	return $results;
}
function draft($username,$offenseId,$defenseId,$leagueId){
	$logger = new rabbitMQClient("syntaxRabbitMQ.ini","logger");

	$mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');
	$query = "INSERT into user_drafts(userName, offense_team_id, defense_team_id, leagueId) VALUES ($username,$offenseId,$defenseId,$leagueId)";
	if( $conn->connect_error){
		echo "failed to connect to database: ". $mydb->error . PHP_EOL;
                $log = array();
                $log['where']="listener: userRegistration";
                $log['error']="failed to connect to databse: ". $mydb->error . PHP_EOL;
                $logger->publish($log);

                exit(0);
		
		
	}
	$stmt = $mydb->prepare($query);
	if($stmt->execute()){
		$result=array();
                $result['done']=true;
                return $result;		
	}else{
		$result=array();
		$result['done']=true;
                return $result;
	}
}
// main function
function requestProcessor($request)
{
  $logger = new rabbitMQClient("syntaxRabbitMQ.ini","logger");

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
    case 'getOrUpdate':
	    return checkForTeamPlayerData();
    case 'showInvites':
	    return showInvites($request['uname']);
    case 'invite':
	    return inviteSend($request['id'],$request['uname']);	   
    case 'isOwner':
	    return isOwner($request['uname'],$request['leagueId']);
    case 'setLeagueDraftDone':
	    return leagueDraftDone($request['leagueId']);
    case 'checkUserDraft':
	    return checkUserDraft($request['uname'],$request['leagueId']);
    case 'getTeamData':
	    return getTeamData($request['leagueId']);
   case 'draft':
	   return draft($request['uaname'],$request['offenseId'],$request['defenseId'],$leagueId['leagueId']);
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


