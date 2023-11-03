#!/usr/bin/php
<?php

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function getData()
{
$apiKey = 'z58e5mh8q6rk8afnbczdj6xk';
$teamsID = [
    "ce92bd47-93d5-4fe9-ada4-0fc681e6caa0", "1f6dcffb-9823-43cd-9ff4-e7a8466749b5", "6680d28d-d4d2-49f6-aace-5292d3ec02c2",
    "7d4fcc64-9cb5-4d1b-8e75-8a906d1e1576", "768c92aa-75ff-4a43-bcc0-f2798c2e1724", "4809ecb0-abd3-451d-9c4a-92a90b83ca06",
    "5fee86ae-74ab-4bdd-8416-42a9dd9964f3", "97354895-8c77-4fd4-a860-32e62ea7382a", "82cf9565-6eb9-4f01-bdbd-5aa0d472fcd9",
    "f7ddd7fa-0bae-4f90-bc8e-669e4d6cf2de", "82d2d380-3834-4938-835f-aec541e5ece7", "d26a1ca5-722d-4274-8f97-c92e49c96315",
    "ad4ae08f-d808-42d5-a1e6-e9bc4e34d123", "d5a2eb42-8065-4174-ab79-0a6fa820e35e", "ebd87119-b331-4469-9ea6-d51fe3ce2f1c",
    "cb2f9f1f-ac67-424e-9e72-1475cb0ed398", "4254d319-1bc7-4f81-b4ab-b5e6f3402b69", "e6aa13a4-0055-48a9-bc41-be28dc106929",
    "f14bf5cc-9a82-4a38-bc15-d39f75ed5314", "0d855753-ea21-4953-89f9-0e20aff9eb73", "f0e724b0-4cbf-495a-be47-013907608da9",
    "de760528-1dc0-416a-a978-b510d20692ff", "2eff2a03-54d4-46ba-890e-2bc3925548f3", "3d08af9e-c767-4f88-a7dc-b920c6d2b4a8",
    "22052ff7-c065-42ee-bc8f-c4691c50e624", "e627eec7-bbae-4fa4-8e73-8e1d6bc5c060", "386bdbf9-9eea-4869-bb9a-274b0bc66e80",
    "04aa1c9d-66da-489d-b16a-1dee3f2eec4d", "7b112545-38e6-483c-a55c-96cf6ee49cb8", "c5a59daa-53a7-4de0-851f-fb12be893e9e",
    "a20471b4-a8d9-40c7-95ad-90cc30e46932", "33405046-04ee-4058-a950-d606f8c30852"
];


$teamsData = [];
for ($i = 0; $i < count($teamsID); $i++) {
	sleep(2);
    $teamID = $teamsID[$i];
    
        $apiURL = "https://api.sportradar.com/nfl/official/trial/v7/en/seasons/2023/REG/teams/$teamID/statistics.json?api_key=$apiKey";
        $ch = curl_init($apiURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        
        $apiResponse = curl_exec($ch);

        
        if ($apiResponse === false) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }

        
        curl_close($ch);

       
        $apiData = json_decode($apiResponse, true);

        $offensePositions = ["QB", "RB", "WR", "TE", "OL"];
        $defensePositions = ["DL", "LB", "CB", "S"];

        $teamOffense = [];
        $teamDefense = [];

        foreach ($apiData["players"] as $playerData) {
            $position = $playerData["position"];
            $jersey = isset($playerData["jersey"]) ? $playerData["jersey"] : '-';

            if (in_array($position, $offensePositions)) {
                $teamOffense[] = [$playerData["name"], $playerData["position"], "Offense", $jersey];
            } elseif (in_array($position, $defensePositions)) {
                $teamDefense[] = [$playerData["name"], $playerData["position"], "Defense", $jersey];
            }
        }

        if (!empty($teamOffense)) {
            $teamsData[] = [
                "name" => $apiData["name"],
		"alias" => $apiData["alias"],
		"offenseDefense"=>"Offense",
                "data" => $teamOffense,
            ];
        }

        if (!empty($teamDefense)) {
            $teamsData[] = [
                "name" => $apiData["name"],
		"alias" => $apiData["alias"],
		"offenseDefense"=>"Defense",
                "data" => $teamDefense,
            ];
        }
    }
	echo $teamsData;
    return json_encode($teamsData);
}

function requestProcessor($request)
{
    switch ($request['type']) {
        case "getData":
            return getData();
    }
}

$server = new rabbitMQServer("syntaxRabbitMQ.ini", "dmz");
echo "syntaxRabbitMQServer BEGIN" . PHP_EOL;
$server->process_requests('requestProcessor');
echo "syntaxRabbitMQServer END" . PHP_EOL;
exit();
?>
