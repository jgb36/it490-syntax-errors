#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
 
require 'vendor/autoload.php';
function doLogin($username,$password)
{
	$logger = new rabbitMQClient("syntaxRabbitMQ.ini","logger");
	$logger2 = new rabbitMQClient("syntaxRabbitMQ.ini","logger2");

    $mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');

	if ($mydb->errno != 0)
	{
		echo "failed to connect to database: ". $mydb->error . PHP_EOL;
		$log = array();
        $log['where']="listener: doLogin";
        $log['error']="failed to connect to database: ". $mydb->error . PHP_EOL;
		$logger->publish($log);
		$logger2->publish($log);
		exit(0);
	}	

	echo "successfully connected to database(login)".PHP_EOL;

	// Hashes the Password
	$hashPassword = password_hash($password, PASSWORD_DEFAULT);

	$stmt = $mydb->prepare("SELECT id, name, password, email FROM syntaxUsers WHERE name = ?");
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
		$logger2->publish($log);


		exit(0);
	}
	if($stmt->num_rows>0){
		$stmt->bind_result($id, $name, $hashPassword, $email);
		$data = $stmt->fetch();
		if(password_verify($password, $hashPassword) && 
		(is_null($data['otp']) || (!is_null($data['otp']) && 
		!is_null($data['otp_expiration']) && 
		strtotime($data['otp_expiration']) < time()) ) ){
			$otp = sprintf("%'.05d",mt_rand(0,99999));
            $expiration = date("Y-m-d H:i" ,strtotime(date('Y-m-d H:i')." +4 mins"));
			$update_sql = "UPDATE syntaxUsers set otp_expiration = ?, otp = ? where id = ? ";

			$update_otp = $mydb->prepare($update_sql);
			$update_otp->bind_param("ssi", $expiration,$otp,$id);	
			$update_otp->execute();
			$request = array();
			$request['Validated'] = true;
			$request['id'] = $id;
			$request['uname'] = $username;
			$request['email'] = $email;
			print_r($request);
			
			$mail = new PHPMailer(true);
			
			try {
				$mail->SMTPDebug = 2;                                       
				$mail->isSMTP();                                            
				$mail->Host       = 'smtp.gmail.com;';                    
				$mail->SMTPAuth   = true;                             
				$mail->Username   = 'bruno.games.mota@gmail.com';                 
				$mail->Password   = 'cvhq jcqv snde xywd';                        
				$mail->SMTPSecure = 'tls';                              
				$mail->Port       = 587;  
			
				$mail->setFrom('bruno.games.mota@gmail.com', 'Fantasy');     
				
				$mail->addAddress($email);
				
				
				$mail->isHTML(true);                                  
				$mail->Subject = 'Pin';
				$mail->Body    = "Your pin is <b>$otp</b> ";
				$mail->AltBody = "Your pin is $otp";
				$mail->send();
				echo "Mail has been sent successfully!";
			} catch (Exception $e) {
				$log = array();
            	$log['where']="listener: doLogin";
        		$log['error']="failed to send email";
        		$logger->publish($log);
				$logger2->publish($log);

			}
            sessionAdd($username);
			return $request;		
		}

		else{
			$log = array();
			$log['Validated'] = false;
            $log['where']="listener: doLogin";
        	$log['error']="password failed to verify";
        	$logger->publish($log);
			$logger2->publish($log);

	        
		    return $log; 
		}
	 
	}
	else{
		$log = array();
        $log['Validated'] = false;
        $log['where']="listener: doLogin";
        $log['error']="account not found";
        $logger->publish($log);
		$logger2->publish($log);

        
		return $log;  

	}


}
function checkOTP($username, $otp){
	$logger = new rabbitMQClient("syntaxRabbitMQ.ini","logger");

    $mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');

	if ($mydb->errno != 0)
	{
		echo "failed to connect to database: ". $mydb->error . PHP_EOL;
		$log = array();
        $log['where']="listener: checkOTP";
        $log['error']="failed to connect to database: ". $mydb->error . PHP_EOL;
		$logger->publish($log);
		$logger2->publish($log);

		exit(0);
	}	
	$sql = "SELECT * FROM syntaxUsers where name = ? and otp = ?";
	$stmt = $mydb->prepare($sql);
	print_r($username);
	print_r($otp);

    $stmt->bind_param('ss',$username,$otp);
	$stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0){
        $mydb->query("UPDATE syntaxUsers set otp = NULL,
		 otp_expiration = NULL where name = '{$username}'");
		$request = array();
		$request['otpValidated'] = true;
		$request['uname'] = $username;
		print_r($request);
		return $request;
    }else{
		$log = array();
		$log['otpValidated'] = false;
		$log['uname'] = $username;
        $log['where']="listener: checkOTP";
        $log['error']="otp failed to verify";
        $logger->publish($log);
		$logger2->publish($log);

		return $log; 
    }


}
function userRegistration($username, $email, $password)
{
        $logger = new rabbitMQClient("syntaxRabbitMQ.ini","logger");

        $mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');

        if ($mydb->errno != 0)
        {
                echo "failed to connect to database: ". $mydb->error . PHP_EOL;
		$log = array();
                $log['where']="listener: userRegistration";
                $log['error']="failed to connect to databse: ". $mydb->error . PHP_EOL;
                $logger->publish($log);
				$logger2->publish($log);


		exit(0);
        }

	echo "successfully connected to database(regis)".PHP_EOL;

	$checkDups = $mydb->prepare("SELECT *  FROM syntaxUsers WHERE name = ? OR email = ?");
	$checkDups->bind_param("ss", $username, $email);
	$checkDups->execute();

	$dup_DB_Result = $checkDups->get_result();

	if($dup_DB_Result-> num_rows > 0) {	
		$log = array();
                $log['where']="listener: userRegistration";
                $log['error']="existing account with given name: ";
                $logger->publish($log);

	return array('created' => false, 'message'=>"Registration Failed, try again");
	
	

	} 

	else {
		$hashPassword = password_hash($password, PASSWORD_DEFAULT);
		$stmt = $mydb->prepare("INSERT INTO syntaxUsers(name, email, password) VALUES (?, ?, ?)");
		$stmt->bind_param("sss", $username, $email, $hashPassword);

		if($stmt->execute()) {
			$stmt->close();
			$mydb->close();
			sessionAdd($username);
	  		return array('created' => true,'uname' => $username, 'message'=>"Registration Successfull for $username");
		}	

		else {
			$stmt->close();
			
			
			$log = array();
        	$log['where']="listener: userRegistration";
        	$log['error']="Registration Failed When Inserting ". $mydb->error . PHP_EOL;
	        $logger->publish($log);
			$logger2->publish($log);


		  return array('created' => false, 'message'=>"Registration Failed, try again");
		}
	
	}
}


//Session add
function sessionAdd($username) {

	
    $logger = new rabbitMQClient("syntaxRabbitMQ.ini","logger");

    $mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');

    if ($mydb->errno != 0)
    {
        echo "failed to connect to database: ". $mydb->error . PHP_EOL;
		$log = array();
        $log['where']="listener: doLogin";
        $log['error']="failed to connect to database: ". $mydb->error . PHP_EOL;
        $logger->publish($log);
		$logger2->publish($log);

		exit(0);
        }

	echo "successfully connected to database(add)".PHP_EOL;

	$creationDate = time();
	$date = date('Y-m-d H:i:s', $creationDate);

	
        $stmt = $mydb->prepare("INSERT INTO sessions(userName, creationDate) VALUES (?, ?)");
	$stmt->bind_param("ss",$username, $date);
	if($stmt->execute()) {

	}
	else {
		$log = array();
                $log['where']="listener: sessionAdd";
                $log['error']="failed to insert session into db: ". $mydb->error . PHP_EOL;
                $logger->publish($log);

		echo "failed to create session for $username: ". $mydb->error . PHP_EOL;
	}

}




function sessionDelete($username) {

    $logger = new rabbitMQClient("syntaxRabbitMQ.ini","logger");

    $mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');

    if ($mydb->errno != 0)
        {
        echo "failed to connect to database: ". $mydb->error . PHP_EOL;
		$log = array();
        $log['where']="listener: sessionDelete";
        $log['error']="failed to connect to database: ". $mydb->error . PHP_EOL;
        $logger->publish($log);
		$logger2->publish($log);


		exit(0);
    }

    echo "successfully connected to database(del)".PHP_EOL;


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
		$logger2->publish($log);




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
				$logger2->publish($log);

                return array('createLeague' => true, 'message'=>"League Registration Successfull for $username");
                
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
				$logger2->publish($log);

                return array('Validated' => false, 'message'=>"No Sesssion Found");
        }
}

function checkForTeamPlayerData(){
        $logger = new rabbitMQClient("syntaxRabbitMQ.ini","logger");
        $mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');
        if ($mydb->errno != 0)
        {
                echo "failed to connect to database: ". $mydb->error . PHP_EOL;
                $log = array();
                $log['where']="listener: checkForTeamPlayerData";
                $log['error']="failed to connect to databse: ". $mydb->error . PHP_EOL;
                $logger->publish($log);
				$logger2->publish($log);

                exit(0);
        }
        echo "successfully connected to database(regis)".PHP_EOL;
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

         $stmt = $mydb->prepare("SELECT id,leagueId,invitedName,leagueName  from invites WHERE invitedName = ? ");
         $stmt->bind_param("s", $userName);
         //Check the database data
         $invites = array();
         if($stmt->execute()) {
                 $result = $stmt->get_result();
                 if ($result->num_rows > 0) {
                         while ($row = $result->fetch_assoc()) {
                                 $invites[] = array(
                                         "id"=>$row["id"],
                                         "leagueName" => $row["leagueName"],
                                         "leagueId" => $row["leagueId"],                               
                                 );
                         }
                         return $invites;
                 }
                 else {
                                 echo "No invites found for player: $userName";
                         }
         }
         else{
		 $log = array();
                 $log['where']="listener: listLeagues";
                 $log['error']="failed to connect to DB " ;
                 $logger->publish($log);
				 $logger2->publish($log);

                 return array('Validated' => false, 'message'=>"No Sesssion Found");
         }
}
function inviteSend($id,$invitedName,$leagueName){
		$mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');
		$teamQuery = "INSERT INTO invites(invitedName,leagueId,leagueName) values (?,?,?)";
		$stmt = $mydb->prepare($teamQuery);
		$stmt->bind_param('sis',$invitedName,$id,$leagueName);
                $stmt->execute();
}
function handleInvite($username,$leagueId, $choice){
	$mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');
	$deleteInvite = "DELETE FROM invites where invitedName = ? and leagueId = ?";
	$stmt = $mydb->prepare($deleteInvite);
	$stmt->bind_param('si',$username,$leagueId);
        $stmt->execute();

	if($choice){
 	    $teamQuery = "INSERT INTO participants(playerName,leagueId) values (?,?)";
            $stmt = $mydb->prepare($teamQuery);
	    $stmt->bind_param('si',$username,$leagueId);
            $stmt->execute();
	}
}
function leagueDraftDone($leagueId){
	$mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');
	$query = "SELECT COUNT(DISTINCT id) as participants_count
          FROM participants
	  WHERE leagueID = ?";
	$result = $mydb->prepare($query);
	$result->bind_param('i',$leagueId);
	$result->execute();
	$a = $result->get_result();
	$row = $a->fetch_assoc();
	$participants_count = $row['participants_count'];
	$query = "SELECT COUNT(*) as drafts_count
              FROM user_drafts ud
	      WHERE ud.leagueId = ?";
	
	$draftedCount = $mydb->prepare($query);
	$draftedCount->bind_param('i',$leagueId);
	$draftedCount->execute();
	$a= $draftedCount->get_result();

	$row = $a->fetch_assoc();
	$drafts_count = $row['drafts_count'];
	$expected_drafts = $participants_count;
	if ($drafts_count === $expected_drafts) {
		$query = "UPDATE league SET draftDone = 1 WHERE id = ?";
		$stmt = $mydb->prepare($query);
		$stmt->bind_param('i',$leagueId);
		
		

		if ($stmt->execute()) {
			$return = array();
			$return['done']=true;
			return $return;
		} else {
    			echo "Error updating draftDone: " . $mydb->error;
		}
		$return = array();
        $return['done']=false;
		return $return;

	}
}

function isOwner($username,$leagueId){
	$mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');

	$query = "SELECT * FROM league WHERE id = ? and ownerName = ?";
	
	$stmt = $mydb->prepare($query);
	$stmt->bind_param("is", $leagueId, $username);

	$stmt->execute();
	$ownerResult= $stmt->get_result();
	if ($ownerResult->num_rows > 0) {
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
	$query = "SELECT userName FROM user_drafts WHERE leagueId = ? and userName = ?";

	$stmt = $mydb->prepare($query);
	$stmt->bind_param("is", $leagueId, $username);

	$stmt->execute();
	$draftDoneResult= $stmt->get_result();

	if($draftDoneResult->num_rows>0){
		$result=array();
                $result['done']=true;
                return $result;
	}else{
		$result=array();
                $result['done']=false;
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
	$result = $stmt->get_result();
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
	$query = "INSERT into user_drafts(userName, offense_team_id, defense_team_id, leagueId) VALUES (?,?,?,?)";
	if( $mydb->connect_error){
		echo "failed to connect to database: ". $mydb->error . PHP_EOL;
                $log = array();
                $log['where']="listener: userRegistration";
                $log['error']="failed to connect to databse: ". $mydb->error . PHP_EOL;
                $logger->publish($log);
				$logger2->publish($log);

	}
	$stmt = $mydb->prepare($query);
	$stmt->bind_param("siii", $username, $offenseId,$defenseId,$leagueId);
	if($stmt->execute()){
		$result=array();
                $result['done']=true;
                return $result;		
	}else{
		$result=array();
		$result['done']=false;
                return $result;
	}
}

function getLeagueViewData($username, $leagueId){
	$mydb = new mysqli('localhost','jay','syn490-jay-errors','syntaxErrors490');
	if ($mydb->errno != 0)
	{
		echo "failed to connect to database: ". $mydb->error . PHP_EOL;
		$log = array();
        $log['where']="listener: doLogin";
        $log['error']="failed to connect to database: ". $mydb->error . PHP_EOL;
		$logger->publish($log);
		$logger2->publish($log);

		
	}	
	else{
		$query = "SELECT
			p.playerName,
			(
				SELECT JSON_ARRAYAGG(
					JSON_OBJECT(
						'playername', player.playerName,
						'position', player.position,
						'offenseDefense', player.offenseDefense,
						'JerseyNum', player.JerseyNum,
						'teamId', player.teamId
					)
				)
				FROM user_drafts ud
				JOIN player  ON ud.offense_team_id = player.teamId
				WHERE ud.leagueId = p.leagueID AND ud.userName = p.playerName
			) AS offenseTeam,
			(
				SELECT JSON_ARRAYAGG(
					JSON_OBJECT(
						'playername', player.playerName,
						'position', player.position,
						'offenseDefense', player.offenseDefense,
						'JerseyNum', player.JerseyNum,
						'teamId', player.teamId
					)
				)
				FROM user_drafts ud
				JOIN player  ON ud.defense_team_id = player.teamId
				WHERE ud.leagueId = p.leagueID AND ud.userName = p.playerName
			) AS defenseTeam
		FROM participants p
		WHERE p.leagueID = '$leagueID'
		ORDER BY p.playerName = '$username' DESC;
		";
		
		$stmt = $mydb->prepare($query);
		$stmt->execute();
		$result = $stmt->get_result();
		
		$results = [];
		while ($row = $result->fetch_assoc()){
			$results[]=$row;
		}
		print_r($results);
		return $results;

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
		  $logger2->publish($log);


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
	    return inviteSend($request['leagueId'],$request['uname'],$request['leagueName']);	   
    case 'handleInvite':
	    return handleInvite($request['uname'],$request['leagueId'],true);
    case 'isOwner':
	    return isOwner($request['uname'],$request['leagueId']);
    case 'setLeagueDraftDone':
	    return leagueDraftDone($request['leagueId']);
    case 'checkUserDraft':
	    return checkUserDraft($request['uname'],$request['leagueId']);
    case 'getTeamData':
	    return getTeamData($request['leagueId']);
   	case 'draft':
	   return draft($request['uname'],$request['offenseId'],$request['defenseId'],$request['leagueId']);
	case 'otp':
		return checkOTP($request['uname'],$request['otp']);
	case 'leagueView':
		return getLeagueViewData($request['uname'],$request['leagueId']);


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

