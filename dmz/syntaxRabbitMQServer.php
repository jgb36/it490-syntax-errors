#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

// Replace these with your actual Sportradar API credentials
$apiKey = 'z58e5mh8q6rk8afnbczdj6xk';
// $accessLevel = 'your_access_level'; E.g., 't', 't1', 't2', etc.

// Specify the NFL team's ID or other relevant information
$teamID = '97354895-8c77-4fd4-a860-32e62ea7382a';

// Sportradar API URL
$apiURL = "https://api.sportradar.com/nfl/official/trial/v7/en/seasons/2023/REG/teams/$teamID/statistics.json?api_key=z58e5mh8q6rk8afnbczdj6xk";
try
{
    // Initialize the cURL session to make a request to the Sportradar API
	$ch = curl_init($apiURL);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute the API request
    $apiResponse = curl_exec($ch);

    // Check for errors in the API response
    if ($apiResponse === false) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }

    // Close the cURL session
    curl_close($ch);

    // Assuming the API response is JSON data, you can decode it
    $teamData = json_decode($apiResponse, true);

    echo "Team: " . $teamData['market'] . " " . $teamData['name'] . "<br>";
    echo "Abbreviation: " . $teamData['alias'] . "<br>";


    // Handle and display the team data
    //echo "Team Name: " . $teamData['name'] . "<br>";
    //$name = $teamData{'name'};
    //echo $name;
    //echo "Abbreviation: " . $teamData['abbreviation'] . "<br>";
    // Add more data fields as needed
}
catch (Exception $e)
{
    echo "Error: " . $e->getMessage();
}
?>

