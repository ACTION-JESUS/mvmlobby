<?php
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'config.php');
include_once('common.php');
include_once('MySQL.php');

class SteamAPI {

	/**
	 * Get a summary for a single player
	 * 
	 * @param Array $steamIdArray - a simply #=>steamid array
	 * @return array:
	 *  player: player array
	 *  error: string
	 */
	public static function getPlayerSummary($steamid) {
		$steamIdArray = array();
		$steamIdArray[] = $steamid;
		$results = self::getMultiplePlayerSummaries($steamIdArray);
		$returnArray = array();
		$returnArray['error'] = $results['error'];
		if (isset($results['players']) && !empty($results['players'])) {
			$returnArray['player'] = $results['players'][0];
		}
		
		return $returnArray;
	}
	
	
	
	/**
	 * Get summaries for multiple players (100 players maximum)
	 * 
	 * @param Array $steamIdArray - a simply #=>steamid array
	 * @return array:
	 *  players: array
	 *  error: string
	 */
	public static function getMultiplePlayerSummaries($steamIdArray) {
		/*
		"response"
			"players" (empty list on error)
				[0] = first player
					steamid:  76561197973743060
					communityvisibilitystate:
						1 = Private
						2 = Friends only
						3 = Friends of Friends
						4 = Users Only
						5 = Public
					profilestate:
						1 = user has configured profile
					personaname: ACTION JESUS
					lastlogoff: 1390895278
					profileurl: url
					avatar: url
					avatarmedium: url
					avatarfull: url
					personastate
						0 = Offline (or for Private visibility)
						1 = Online
						2 = Busy
						3 = Away
						4 = Snooze
						5 = Looking to trade
						6 = Looking to play
					primaryclanid: 103582791434932048 (Two Cities Veterans)
					timecreated: 1107067739
					personastateflags: 0
					loccountrycode: US
					locstatecode: WA
					loccityid: 4030
					
					if in game:
					gameserverip: 192.69.96.168:27061
					gameextrainfo: Team Fortress 2
					gameid: 440
					gameserversteamid: 90089111116220416
		*/
		$playerList = NULL;
		$error = NULL;
		$url='http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key='.STEAM_API_KEY.'&steamids='.urlencode(implode(',',$steamIdArray));
		try {
			$summary = getURLContents($url);
			if ($summary!=null) {
				$jsonSummary = json_decode($summary, true);
				if ($jsonSummary!=null && isset($jsonSummary['response'])) {
					$response = $jsonSummary['response'];
					if (isset($response['players'])) {
						$playerList = $response['players'];
					}
				}
			}
		} catch (Exception $e) { $error = 'The Steam Summary API appears to be down.'; }
		
		$returnArray = array();
		if (empty($playerList)) {
			if (empty($error)) {
				$error = 'An unknown error occurred retrieving the Steam summary.';
			}
		} else {
			$returnArray['players'] = $playerList;
		}
		$returnArray['error'] = $error;
		return $returnArray;
	}
	
	
	/**
	 * Calls getMultiplePlayerSummaries multiple times to get combined results
	 * getMultiplePlayerSummariesUnlimited has a limit of 100 players
	 * 
	 * @param unknown $fullSteamIdArray
	 */
	public static function getMultiplePlayerSummariesUnlimited($fullSteamIdArray) {
		$returnArray = array();
		
		while ($fullSteamIdArray) {
			$partialSteamIdArray = array_splice($fullSteamIdArray, 0, 100);
			
			$partialSteamSummaryArray = SteamAPI::getMultiplePlayerSummaries($partialSteamIdArray);
			if ( isset($partialSteamSummaryArray['players']) ) {
				if (!isset($returnArray['players'])) { $returnArray['players'] = array(); }
				$returnArray['players'] = array_merge($returnArray['players'], $partialSteamSummaryArray['players']);
			} else if ( isset($partialSteamSummaryArray['error']) ) {
				$returnArray['error'] = $partialSteamSummaryArray['error'];
				break;
			}
					
		}
		
		return $returnArray;
	}
	
	/**
	 * Retrieve Team Fortress 2 inventory
	 * 
	 * @param $steamid
	 * @return array with inventory
	 */
	public static function getTF2Inventory($steamid) {
		$inventoryArray = array();
		$error = NULL;
		try {
			$url='http://api.steampowered.com/IEconItems_440/GetPlayerItems/v0001?key='.STEAM_API_KEY.'&SteamID='.$steamid;
			$steamResultsString = getURLContents($url);
			
			if (!empty($steamResultsString)) {
				$steamResultsJSON = json_decode($steamResultsString,true);
				if ($steamResultsJSON!=null && isset($steamResultsJSON['result'])) {
					$result = $steamResultsJSON['result'];
					if (isset($result['status'])) {
						$inventoryArray['status'] = $result['status'];
						if ($result['status']==1 && isset($result['items'])) {
							$inventoryArray['items'] = $result['items'];
						} else {
							$error = 'Results do not contain inventory';
						}
					} else {
						$error = 'Status array not found.';
					}
				} else {
					$error = "Error decoding JSON results";
				}
				// "result"
				// 	"status"=1	// good fetch, otherwise bad
				// 	"items"	// list of items
			} else {
				$error = 'No results returned from getURLContents()';
			}
		} catch (Exception $e) { $error = $e; }
		if (!empty($error)) {
			$inventoryArray['error'] = $error;
		}
		return $inventoryArray;
	}
	
	
	
	/**
	 * Retrieve the Steam list of friends 
	 * 
	 * @param String $steamid - SteamID64
	 * @return Array of SteamID64's
	 */
	public static function getFriendsArray($steamid) {
		$friendsArray = array();
		try {
			$url = 'http://api.steampowered.com/ISteamUser/GetFriendList/v1?key='.STEAM_API_KEY.'&steamid='.$steamid.'&relationship=all';
			$friendsResults = getURLContents($url);
			if ($friendsResults != null) {
				$friendsResultsJSON = json_decode($friendsResults,true);
				if ($friendsResultsJSON!=null && isset($friendsResultsJSON['friendslist']) && isset($friendsResultsJSON['friendslist']['friends'])) {
					$steamFriendsArray = $friendsResultsJSON['friendslist']['friends'];
					foreach($steamFriendsArray as $friend) {
						$friendsArray[] = $friend['steamid'];
					}
				}
			}		
		} catch (Exception $e) {}
		return $friendsArray;
	}
	

	/**
	 * Retrieve the Steam list of group ID's ("gid")
	 * 
	 * @param String $steamid - SteamID64
	 * @return Array of groups
	 */
	public static function getGroupsArray($steamid) {
		$groupArray = array();
		try {
			$url = 'http://api.steampowered.com/ISteamUser/GetUserGroupList/v1?key='.STEAM_API_KEY.'&steamid='.$steamid.'&relationship=all';
			$groupResults = getURLContents($url);
			if ($groupResults != null) {
				$groupResultsJSON = json_decode($groupResults,true);
				if ($groupResultsJSON!=null && isset($groupResultsJSON['response']) && isset($groupResultsJSON['response']['groups'])) {
					$steamGroupArray = $groupResultsJSON['response']['groups'];
					foreach($steamGroupArray as $friend) {
						$groupArray[] = $friend['gid'];
					}
				}
			}
		} catch (Exception $e) {}
		return $groupArray;
	}
	
	
	/**
	 * Retrieve the Steam list of group ID's ("gid")
	 * 
	 * @param String $steamid - SteamID64
	 * @return Array of groups
	 * Example:  http://steamcommunity.com/gid/103582791434932048/memberslistxml/?xml=1
	 */
	public static function getGroupMemberList($gid) {
		$steamIdArray = array();
		try {
			$url = 'http://steamcommunity.com/gid/'.$gid.'/memberslistxml?xml=1';
			
			$xml=simplexml_load_file($url);
			
			foreach ($xml->members->children() as $steamIdElement)
			{
				// echo $steamIdElement.'<br>';
				$steamIdArray[] = (string)$steamIdElement;
				// print_r($steamIdElement);
			}
		} catch (Exception $e) {}
		return $steamIdArray;
	}
	
	
	/**
	 * Determines if a player is a member of a Steam group
	 * 
	 * @param String $steamid - SteamID64
	 * @param String $gid - numeric value (the value should look like 5410640, not 103582791434932048, both of which are used for Two Cities Veterans)
	 * @return boolean - true=a member, false=not a member
	 */
	public static function isMemberOfGroup($steamid,$gid) {
		$isMember = FALSE;
		try {
			$url = 'http://api.steampowered.com/ISteamUser/GetUserGroupList/v1?key='.STEAM_API_KEY.'&steamid='.$steamid.'&relationship=all';
			$groupResults = getURLContents($url);
			if ($groupResults != null) {
				$groupResultsJSON = json_decode($groupResults,true);
				if ($groupResultsJSON!=null && isset($groupResultsJSON['response']) && isset($groupResultsJSON['response']['groups'])) {
					$steamGroupArray = $groupResultsJSON['response']['groups'];
					foreach($steamGroupArray as $friend) {
						if($friend['gid'] == $gid) {
							$isMember = TRUE;
							break;
						}
					}
				}
			}
		} catch (Exception $e) {}
		return $isMember;
	}
	
	
	
	/**
	 * Returns the A2S_INFO server info
	 * More info at https://developer.valvesoftware.com/wiki/Server_queries
	 *
	 * @param String $ipPort (ipaddress:port)
	 * @return an array containing all server fields
	 */
	public static function getServerInfo($ipport)
	{
		$serverResults = null;
		
		$ipParts = explode(":", $ipport);
		$serverIP = $ipParts[0];
		$serverPort = $ipParts[1];
	
		try {
			$queryCommand = "\xFF\xFF\xFF\xFFTSource Engine Query\0";
			$socket = fsockopen("udp://".$serverIP, $serverPort, $errno, $errstr,2); // 3 second timeout does not work, stream_set_timeout does
			stream_set_timeout($socket, 2);
			fwrite($socket, $queryCommand);
			$readResult = fread($socket,4);		// 4 x 0xff (this is the first point were it hangs on invalid servers)
			if ($readResult) {
				// byte:  ord(fread($socket, 1))
				// short: array_pop(unpack("S", fread($socket, 2)))
				// long:  array_pop(unpack("L", fread($socket, 4)))
				// float  array_pop(unpack("f", fread($socket, 4)))
				// string: static::getSocketString($socket)
				$CheckStatus = socket_get_status($socket);
				if($CheckStatus["unread_bytes"] >0) {				
					$serverResults = array();
					$serverResults['Header'] = 	ord(fread($socket, 1));	// byte, s/b 0x49  / 'D'
					$serverResults['Protocol'] = ord(fread($socket, 1));
					$serverResults['Name'] = static::getSocketString($socket);
					$serverResults['Map'] = static::getSocketString($socket);
					$serverResults['Folder'] = static::getSocketString($socket);
					$serverResults['Game'] = static::getSocketString($socket);
		 			$serverResults['ID'] = array_pop(unpack("S", fread($socket, 2)));
					$serverResults['Players'] = ord(fread($socket, 1));
					$serverResults['MaxPlayers'] = ord(fread($socket, 1));
					$serverResults['Bots'] = ord(fread($socket, 1));
					$serverResults['ServerType'] = ord(fread($socket, 1));	// d=dedicated, i=non-dedicated, p=sourcetv
					$serverResults['Environment'] = ord(fread($socket, 1));	// l=linux, w=windows, m or o=Mac
					$serverResults['Visibility'] = ord(fread($socket, 1));	// 0=public, 1=private
					$serverResults['VAC'] = ord(fread($socket, 1));			// 0=unsecured, 1=secured
				}
			}	
		} catch (Exception $e) {
			error_log("getServerInfo error: port=".$ipport . $e);
		}

		if (isset($socket)) {
			fclose($socket);
		}
		
		return $serverResults;

	}

	
	/**
	 * Retrieves Steam info from getServerInfo(), but only returns data if it is an MvM Mann Up map
	 * 
	 * Inputs:
	 * 		ipport:  the IP and port number (192.69.97.231:27065)
	 * 
	 * Output:
	 * 		an array of MvM mission info
	 * 
	 * Output example:
	 * 		$missionResults['mission_id'] = 19
	 * 		$missionResults['tour_name'] = Two Cities
	 * 		$missionResults['mission_name'] = Bavarian Botbash
	 * 		$missionResults['wiki_url'] = https://wiki.teamfortress.com/wiki/Bavarian_Botbash_(mission)
	 */
	public static function getServerMission($ipport) {
		$missionResults = null;
		
		$serverResults = static::getServerInfo($ipport);
		$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
		$results = $sqlcon->one('
				select
					m.id as mission_id,
					t.short_name as tour_name,
					m.name as mission_name,
					m.wiki_url
				from mission m
				inner join tour t on t.id=m.tour_id
				where m.map_name="'.$serverResults['Map'].'"'
		);
		if ($sqlcon->records > 0) {
			$missionResults = $results;
		}
		
		return $missionResults;
	}
	
	
	/**
	 * Resolves a vanity URL 'action_jesus' to SteamID64
	 * 
	 * @param unknown $vanityURL
	 * @return NULL
	 */
	public static function resolveVanityURL($vanityURL) {
		$steam64ID = NULL;
		try {
			$url = 'http://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001?key='.STEAM_API_KEY.'&vanityurl='.urlencode($vanityURL);
			$response = getURLContents($url);
			if ($response!=null) {
				$jsonSummary = json_decode($response, true);
				if ($jsonSummary!=null && isset($jsonSummary['response'])) {
					if (isset($jsonSummary['response']['steamid'])) {
						$steam64ID = $jsonSummary['response']['steamid'];
					}
				}
		
			}
		} catch (Exception $e) { $error = "Steam API appears to be down."; }
		return $steam64ID;
	}
	
	
	/**
	 * Returns the list of players on a server
	 * Thanks to this post:  https://developer.valvesoftware.com/wiki/Server_queries
	 * This is the A2S_PLAYER format
	 *
	 * @param String $ipPort (ipaddress:port)
	 * @return an array with mission_id, tour_name, and mission_name, or null if not playing Mann Up MVM
	 */
	public static function getPlayerList($ipport)
	{
		$ipParts = explode(":", $ipport);
		$serverIP = $ipParts[0];
		$serverPort = $ipParts[1];
	
		try {
			$socket = fsockopen("udp://".$serverIP, $serverPort, $errno, $errstr,3);		// 3 second timeout does not work, stream_set_timeout does
			stream_set_timeout($socket, 3);
	
			// First get the "challenge " code from the server
			fwrite($socket, "\xFF\xFF\xFF\xFF\x55\xFF\xFF\xFF\xFF");
			$challengeResponse = fread($socket, 16);
			$challengeNumber = substr($challengeResponse, 5, 9);
			
			// Now send the command to get the player list using the challenge number
			$queryCommand = "\xFF\xFF\xFF\xFFU".$challengeNumber;	// = FF FF FF FF 55 ?? ?? ?? ??
			fwrite($socket, $queryCommand);
			fread($socket,4);		// 4 x 0xff
			
			$playerArray = array();
			$header = ord(fread($socket, 1));	// byte, s/b 0x44  / 'D'
			$playerCount = ord(fread($socket, 1));
			for ($i=0; $i<$playerCount; $i++) {
				$playerIndex = ord(fread($socket, 1));
				$playerName = SteamAPI::getSocketString($socket);
				$playerScore = array_pop(unpack("L", fread($socket, 4)));	// (long) should result in an array and the score is element 1
				$playerDuration = array_pop(unpack("f", fread($socket, 4))); // (float) should result in an array and the score is element 1
				
				$playerArray[$i] = array(
					'index' => $playerIndex,
					'name'	=> $playerName,
					'score'	=> $playerScore,
					'time'	=> sprintf("%02d%s%02d%s%02d", floor($playerDuration/3600), ':', ($playerDuration/60)%60, ':', $playerDuration%60)
				);
			}
		} catch (Exception $e) {
			error_log("getPlayerList error: port=".$ipport . $e);
		}
		if (isset($socket)) {
			fclose($socket);
		}

		return $playerArray;
	
	}

    public static function getSocketString($socket) {
        $string = '';
        $loop   = TRUE;
       
        while($loop) {
            $_fp = fread($socket, 1);

            if( ord($_fp) != 0 ) {
                $string .= $_fp;
            }
            else { $loop = FALSE; }
        }
       
        return $string;
    }
	
	public static function strToHex($string){
		$hex = '';
		for ($i=0; $i<strlen($string); $i++){
			$ord = ord($string[$i]);
			$hexCode = dechex($ord);
			$hex .= substr('0'.$hexCode, -2).' ';
		}
		return strToUpper($hex);
	}
	
	
	/*  Get the number of robots killed
	 *  Returns -1 for a Steam API error or the actual number on success
	http://api.steampowered.com/ISteamUserStats/GetUserStatsForGame/v0002/?appid=440&key=XXXXXX&steamid=76561197972495328
	 *  {
	"playerstats": {
		"steamID": "76561197973743060",
		"gameName": "Team Fortress 2",
		"stats": [
			 {
							"name": "TF_MVM_KILL_ROBOT_MEGA_GRIND_STAT",
							"value": 358223
			 },
	 */
	public static function getRobotsKilled($steamid) {
		$robotsKilled = -1;
		
		try {
			$url = 'http://api.steampowered.com/ISteamUserStats/GetUserStatsForGame/v0002/?appid=440&key='.STEAM_API_KEY.'&steamid='.$steamid;
			$achievementResults = getURLContents($url);
			if ($achievementResults != null) {
				$resultsJSON = json_decode($achievementResults,true);
				if ($resultsJSON!=null && isset($resultsJSON['playerstats']) && isset($resultsJSON['playerstats']['stats'])) {
					$achievementArray = $resultsJSON['playerstats']['stats'];
					foreach($achievementArray as $achievement) {
						if($achievement['name'] == "TF_MVM_KILL_ROBOT_MEGA_GRIND_STAT") {
							$robotsKilled = $achievement['value']; 
							break;
						}
					}
				}
			}
		} catch (Exception $e) {}

		return $robotsKilled;
	}
	
	
}
?>