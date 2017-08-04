<?php

// /var/www/run/../../resources/config.php
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'config.php');
require_once(OS_ROOT_DIR.DS.'MySQL.php');
require_once(OS_ROOT_DIR.DS.'SteamAPI.php');

// $serverInfo = SteamAPI::getServerInfo('192.69.97.58:27032');
// print_r($serverInfo);
// exit;

/*
Checking server 103.10.125.92:27030
MvM server found for Sydney
Array
(
    [Header] => 73
    [Protocol] => 17
    [Name] => Valve MvM Mann Up Server (Sydney srcds073 #16)
    [Map] => mvm_rottenburg_advanced1
    [Folder] => tf
    [Game] => Team Fortress
    [ID] => 440
    [Players] => 4
    [MaxPlayers] => 6
    [Bots] => 0
    [ServerType] => 100
    [Environment] => 108
    [Visibility] => 0
    [VAC] => 1
)
Array
(
    [0] => Array
        (
            [index] => 0
            [name] => -=ST=- Butt Stallion
            [score] => 50
            [time] => 00:40:04
        )

    [1] => Array
        (
            [index] => 0
            [name] => -=ST=- SniperBeast
            [score] => 62
            [time] => 00:40:03
        )

    [2] => Array
        (
            [index] => 0
            [name] => -=ST=- HappyFaceKillz
            [score] => 100
            [time] => 00:40:03
        )

    [3] => Array
        (
            [index] => 0
            [name] => The Farvio
            [score] => 4
            [time] => 00:05:45
        )

)

Server info:
Tour and Mission
Server City
Number of players
Players:
	Name
	Score
	Time
 */

// Get the mission list and server location list
$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
$mapNameResults = $sqlcon->query('select id, map_name from mission');
$mapNames = array();
foreach($mapNameResults as $mapNameResult) {
	$mapNames[$mapNameResult['map_name']] = $mapNameResult['id'];
}
$serverLocationResults = $sqlcon->query('select id, location from server_location');
$serverLocations = array();
foreach($serverLocationResults as $serverLocationResult) {
	$serverLocations[$serverLocationResult['location']] = $serverLocationResult['id'];
}
$sqlcon->CloseConnection();


while (true) {
	$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
	
	// Only check servers running the source engine
	$serverResults = $sqlcon->query('
		SELECT *
		FROM servers
		WHERE is_source_engine_game IS NOT NULL
		AND mission_id IS NOT NULL
	');	// id = 93044 and ipport="185.25.183.92:27031"
	
	foreach($serverResults as $serverResult) {
		$serverId = $serverResult['id'];
		$badReads = $serverResult['bad_read_count'];
		$isSourceEngineGame = 0;
		$missionId = null;
		$locationId = null;
		$playerCount = null;
		
		try {
			// echo 'Checking server ' . $serverResult['ipport'] . "\n";
			$serverInfo = SteamAPI::getServerInfo($serverResult['ipport']);
	
			if ($serverInfo) {
				$isSourceEngineGame = 1;		
				$serverName = $serverInfo['Name'];	// Valve MvM Mann Up Server (Sydney srcds071 #11)
				$badReads = 0;
				
				if (strpos($serverName, 'Valve MvM Mann Up Server') === 0) {
					$serverMap = $serverInfo['Map'];

					if (array_key_exists($serverMap, $mapNames)) {
						$missionId = $mapNames[$serverMap];
					}
					
					if ($missionId) {
						$cityStart = strpos($serverName, '(');
						$cityEnd = strpos($serverName, 'src', $cityStart);
						$serverCity = substr($serverName, $cityStart+1, $cityEnd-$cityStart-2);
						$playerCount = $serverInfo['Players'];
						
						if (array_key_exists($serverCity, $serverLocations)) {
							$locationId = $serverLocations[$serverCity];
						}
							
						// $players = SteamAPI::getPlayerList($serverResult['ipport']);
			
						// echo 'MvM server found for ' . $serverCity . "\n";
						// print_r($serverInfo);
						// print_r($players);
					}
				} else {
					// echo ' - not an MvM server' . "\n";
				}
			} else {
				++$badReads;
			}
		} catch (Exception $e) {
			echo ' - exception: ' . $e;
		}
		
		if ($missionId) {
			$sql = 'update servers set is_source_engine_game=true, last_known_used=now(), bad_read_count=0, mission_id='.$missionId.', location_id="'.$locationId.'", player_count='.$playerCount . ' where id='.$serverId;
		} elseif ($serverResult['mission_id'] && !$isSourceEngineGame && $badReads < 10) {
			// was previously an MvM game, but the server didn't return info this time
			// just update the bad reads for now
			// assume that the MvM data is still somewhat accurate
			$sql = 'update servers set bad_read_count='.$badReads . ' where id='.$serverId;
		} elseif ($badReads > 100) {
			$sql = 'delete from servers where id='.$serverId;
		} else {
			$sql = 'update servers set is_source_engine_game=' . $isSourceEngineGame . ', bad_read_count='.$badReads.', mission_id=null, location_id=null, player_count=null where id='.$serverId;
		}
		/*
		echo $serverResult['ipport'] . "\n";
		echo "isSourceEngineGame = $isSourceEngineGame\n";
		echo "missionId = $missionId\n";
		echo "locationId = $locationId\n";
		echo "playerCount = $playerCount\n";
		print_r($serverResult);
		print_r($serverInfo);
		echo $sql."\n\n\n";
		*/
		$sqlresult = $sqlcon->update($sql);

 		if (!$sqlresult) {
 			print_r($sql."\n");
 			print_r("sql result:".$sqlresult."\n");
 		}
	} // foreach $servers loop

	$sqlcon->CloseConnection();

	sleep(60);

} // while(true)

?>