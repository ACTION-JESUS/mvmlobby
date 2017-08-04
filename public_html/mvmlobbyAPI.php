<?php
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'config.php');

if (isset($_REQUEST['apitype'])) {
	$apitype = $_REQUEST['apitype'];
	
	switch ($apitype) {
		case 'getPlayerData': getPlayerData(); break;
		case 'serverPlayerList': serverPlayerList(); break;
		case 'getSteam64IDArray': getSteam64IDArray(); break;
		case 'getRawSummary': getRawSummary(); break;
		case 'getRawInventory': getRawInventory(); break;
		case 'getRawFriendsList': getRawFriendsList(); break;
		case 'updateAllFriendsStatus': updateAllFriendsStatus(); break;
		case 'getSteamFriendsImportList': getSteamFriendsImportList(); break;
		case 'upsert': upsert(); break;
		case 'convertLobbyID': convertLobbyID(); break;
		case 'deletePlayer': deletePlayer(); break;
		case 'refreshTeams': refreshTeams(); break;
		case 'updateTeam': updateTeam(); break;
		case 'deleteTeam': deleteTeam(); break;
		case 'getServerList': getServerList(); break;
	}
	
	exit;
}

	function getSessionSteamID() {
		if (session_id()=='' || !isset($_SESSION)) {
			@session_start();
		}
		if (isset($_SESSION['steamid'])) {
			return $_SESSION['steamid'];
		} else {
			return NULL;
		}
	}
	

	/**
	 * Retrieve player from the Player class
	 * 
	 * Inputs:
	 * 		steamid
	 * 
	 * Outputs:
	 * 		array['error'] = error message
	 * 		array['player'] = Player class data
	 */
	function getPlayerData() {
		require_once('Player.php');
		
		$returnArray = array();
		$errorFound = null;
		
		if (!isset($_REQUEST['steamid'])) {
			$errorFound = 'steamid input is missing';
		} else {
			$steamid = trim($_REQUEST['steamid']);
		
			$forceRefresh = FALSE;
			if (isset($_REQUEST['forcerefresh']) && $_REQUEST['forcerefresh']=='true') {
				$forceRefresh = Player::REFRESH_FORCE_ON;
			}
		
			$player = new Player($steamid, $forceRefresh);
		
			if ($player == null) {
				$errorFound = 'invalid steamid';
			} else {
				$returnArray['player'] = $player;
			}
		}
		
		if ($errorFound != NULL) {
			$returnArray['error'] = $errorFound;
		}
		
		header('Content-type: application/json');
		echo json_encode($returnArray);
	}
	
	
	/**
	 * Retrieve the list of players from an MVM server with the steam id's when possible
	 * 
	 * Inputs:
	 * 		ipport:  string (ex: "192.168.1.1:27015")
	 * 
	 * Outputs:
	 * 	array of players (ex: array[0]['name'])
	 * 		
	 */
	function serverPlayerList() {
		include_once('common.php');
		include_once('MySQL.php');
		include_once('SteamAPI.php');
		if (isset($_REQUEST['ipport']) && !empty($_REQUEST['ipport'])) {
			
			$playerArray = SteamAPI::getPlayerList($_REQUEST['ipport']);
			
			if ($playerArray && count($playerArray)>0) {
				$con = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
			
				// get the player name from the database, if available
				foreach($playerArray as $key => $playerInfo) {
					$steamid = '';
					$sqlResults = $con->query('select steamid from player where name="'.$playerInfo['name'].'"');
					if ($con->records == 1) {
						$steamid = $sqlResults[0]['steamid'];
					}
					$playerArray[$key]['steamid'] = $steamid;
				}
					
				// sort by score
				usort($playerArray, function($a, $b) {
					return $b['score'] - $a['score'];
				});
			
				$con->CloseConnection();
			}
			
			header('Content-type: application/json');
			echo json_encode($playerArray);}
	}
	
	
	
	/**
	 * Convert any text into an array of steamid's
	 * 
	 * Input:
	 * 		steaminput:  any blob of text (must use 'PUT' for this, otherwise a URI too long error will be thrown)
	 * 
	 * Ouput:
	 * 		steamidarray (ex: $results[0]['steamid']
	 */
	function getSteam64IDArray() {
		include_once('common.php');
		$error = null;
		$steam64IDArray = null;
		$steamInput = null;
		
		if (!isset($_REQUEST['steaminput'])) {
			$error = 'No input found';
		} else {
			$steamInput = $_REQUEST['steaminput'];
		}
		
		if ($error==null && empty($steamInput)) {
			$error = 'Input too short';
		}
		
		if ($error == null) {
			$steam64IDArray = getSteamIDFromInput($steamInput);
		}
		
		$returnArray = array();
		if ($steam64IDArray == null) {
			$error = 'Player not found';
		} else {
			$returnArray['steam64IDArray'] = $steam64IDArray;
		}
		
		if ($error != null) {
			$returnArray['error'] = $error;
		}
		
		header('Content-type: application/json');
		echo json_encode($returnArray);
	}

	
	function convertLobbyID() {
		include_once('common.php');
		$error = null;
		$steam64ID = null;
		$steamInput = null;

		if (!isset($_REQUEST['lobbyidinput'])) {
			$error = 'No input found';
		} else {
			$lobbyIDInput = $_REQUEST['lobbyidinput'];
		}
		
		if ($error==null && empty($lobbyIDInput)) {
			$error = 'Input too short';
		}
		if ($error == null) {
			$steam64ID = convertLobby32IDToLobby64ID($lobbyIDInput);
		}
		
		$returnArray = array();
		if (empty($steam64ID)) {
			$error = 'Cannot find the lobby ID in the input text.  It should be in the format L:1:1444365316';
		} else {
			$returnArray['lobby64id'] = $steam64ID;
		}
		
		if ($error != null) {
			$returnArray['error'] = $error;
		}
		
		header('Content-type: application/json');
		echo json_encode($returnArray);
	}

	
	/**
	 * Admin functions to test Steam API calls
	 */
	function getRawSummary() {
		if (isAdmin()) {
			include_once('SteamAPI.php');
			if (isset($_REQUEST['steamid'])) {
				$returnArray = SteamAPI::getPlayerSummary($_REQUEST['steamid']);
				header('Content-type: application/json');
				echo json_encode($returnArray);
			}
		}
	}
	function getRawInventory() {
		if (isAdmin()) {
			include_once('SteamAPI.php');
			if (isset($_REQUEST['steamid'])) {
				$returnArray = SteamAPI::getTF2Inventory($_REQUEST['steamid']);
				header('Content-type: application/json');
				echo json_encode($returnArray);
			}
		}
	}
	function getRawFriendsList() {
		if (isAdmin()) {
			include_once('SteamAPI.php');
			if (isset($_REQUEST['steamid'])) {
				$returnArray = SteamAPI::getFriendsArray($_REQUEST['steamid']);
				header('Content-type: application/json');
				echo json_encode($returnArray);
			}
		}
	}
	
	function isAdmin() {
		include_once('MySQL.php');
		$isAdmin = FALSE;
		$steamid = getSessionSteamID();
		if ($steamid) {
			$steamid = $_SESSION['steamid'];
			$con = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
			$con->one('select 1 from player where steamid="'.$steamid.'" and site_status="Admin" limit 1');
			if ($con->records > 0) {
				$isAdmin = TRUE;
			}
			$con->CloseConnection();
		}
		return $isAdmin;
	}
	
	
	/**
	 * Retrieve all Steam summary data for friends at once and update the database
	 * 
	 * Inputs:
	 * 	none (uses the session steam id)
	 * 
	 * Outputs:
	 * 	returns error=? or success=1
	 * */
	 function updateAllFriendsStatus() {
		require_once('common.php');
		require_once('Player.php');
		require_once('MySQL.php');
		require_once('SteamAPI.php');
		
		$returnArray = array();
		$error = null;
		$steamid = getSessionSteamID();
		if (!$steamid) {
			$error = 'Must be logged in to update friend status.';
		}
		
		if ($error == NULL) {
			try {
				// Create an array of MVM Lobby friend steam ID's
				$con = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
				$results = $con->query('
						select
							f.steamid
						from player p
						inner join player_friend pf
							on pf.player_id=p.id
						inner join player f
							on f.id=pf.friend_id
							and (f.summary_last_updated is NULL OR date_add(f.summary_last_updated, INTERVAL '.SUMMARY_REFRESH_MINUTES.' MINUTE) <= now())
						where p.steamid="'.$steamid.'"');
				
				if ($con->records > 0) {
					$steamIdList = array();
					foreach($results as $result) {
						$steamIdList[] = $result['steamid'];
					}
		
					// Retrieve Steam summaries for all MVM lobby friends in a single API call
					$summaryResults = SteamAPI::getMultiplePlayerSummariesUnlimited($steamIdList);
					if (empty($summaryResults['error'])) {
						$playerList = $summaryResults['players'];
						foreach($playerList as $steamPlayerSummary) {
							$player = new Player();		// just creates the player template, does not load the player from cache
							$player->updateFromSteamSummary($steamPlayerSummary, TRUE);
						}
					}
				}
				$con->CloseConnection();
			} catch (Exception $e) { $error = "Steam API appears to be down."; }
		} else {
			$error = 'Invalid steam ID';
		}
		
		if ($error != null) {
			$returnArray['error'] = $error;
		} else {
			$returnArray['success'] = TRUE;
		}
		header('Content-type: application/json');
		echo json_encode($returnArray);
	 }
	 
	 
	 
	 /**
	  * Get a list of Steam friends who are NOT mvmlobby friends
	  * 
	  * Inputs:
	  * 	none
	  * 
	  * Outputs:
	  * 	
	  *
	  */
	function getSteamFriendsImportList() {
		require_once('common.php');
		require_once('Player.php');
		require_once('SteamAPI.php');
		 
		$userSteamID = getSessionSteamID();
		$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
		
		// Retrieve the Steam friends list ([0]="7656...", etc.).  may or may not be an mvmlobby friend
		$steamFriendsArray = SteamAPI::getFriendsArray($userSteamID);
		
		// Get the user steam id
		$sqlResults = $sqlcon->one('select id from player where steamid="'.$userSteamID.'";');
		$userSQLId = $sqlResults['id'];
		unset($sqlResults);
		
		// Top SQL:  Steam friends who are NOT mvmlobby friends but ARE in the database
		// Bottom SQL: mvmlobby friends
		// Result:
		// 		[steamid1] = { "steamid" => "7656", "name":"steve", etc. }
		// 		[steamid2] = { "steamid" => "7656", "name":"joe", etc. }
		$steamFriendsSQL = implode('", "', $steamFriendsArray);
		$friendsInDatabase = $sqlcon->query('
			select
				f.steamid,
				f.name,
				f.image_url_small as image_url,
				f.total_tours,
				TIMESTAMPDIFF(DAY,(select max(date_changed) from player_tour_history pth where pth.player_id=f.id), now()) as last_played,
				"N" as is_mvmlobby_friend,
				"Y" as is_steam_friend
			from player f
			left outer join player_friend pf on pf.friend_id = f.id and pf.player_id='.$userSQLId.'
			where f.steamid in ("'.$steamFriendsSQL.'")
			and pf.id is null
			UNION
			select
				f.steamid,
				f.name,
				f.image_url_small as image_url,
				f.total_tours,
				TIMESTAMPDIFF(DAY,(select max(date_changed) from player_tour_history pth where pth.player_id=f.id), now()) as last_played,
				"Y" as is_mvmlobby_friend,
				"N" as is_steam_friend
			from player p
			inner join player_friend pf on pf.player_id=p.id
			inner join player f on f.id=pf.friend_id
			where p.id='.$userSQLId.'
		', 'steamid');
		$sqlcon->CloseConnection();

		// Get Steam summary info for all Steam friends
		$steamSummaryArray = SteamAPI::getMultiplePlayerSummariesUnlimited($steamFriendsArray);
		
		// Create a results array from the Steam summary
		$steamFriendsResults = array();
		if ($steamSummaryArray && isset($steamSummaryArray['players'])) {
			foreach($steamSummaryArray['players'] as $steamSummary) {
				$tempArray = array();
				$steamid = $steamSummary['steamid'];
				$tempArray['steamid'] = $steamid;
				$tempArray['name'] = $steamSummary['personaname'];
				$tempArray['image_url'] = $steamSummary['avatar'];
				$tempArray['is_steam_friend'] = 'Y';
		 		
				if (array_key_exists($steamid, $friendsInDatabase)) {
					$dbFriend = $friendsInDatabase[$steamid];
					$tempArray['total_tours'] = $dbFriend['total_tours'];
					$tempArray['last_played'] = $dbFriend['last_played'];
					$tempArray['is_mvmlobby_friend'] = $dbFriend['is_mvmlobby_friend'];
					unset($friendsInDatabase[$steamid]);
				} else {
					$tempArray['is_mvmlobby_friend'] = 'N';
				}
				$steamFriendsResults[$steamid] = $tempArray;
			}
		}
		 
		// Merge in the remains of the $friendsInDatabase array, which should only contain mvmlobby friends who are NOT Steam friends at this point
		// (see unset($friendsInDatabase) above)
		$mergedArray = array_merge($steamFriendsResults, $friendsInDatabase);
		unset($steamFriendsArray);
		unset($friendsInDatabase);

		// sort results by name
		function sortByName($a, $b) {
			return strtolower($a['name']) > strtolower($b['name']) ? 1 : -1;
		}
		usort($mergedArray, 'sortByName');
		 
		header('Content-type: application/json');
		echo json_encode($mergedArray);
	 }
	 
	 
	 /**
	  * 
	  */
	 function upsert() {
		 require_once('common.php');
		 require_once('MySQL.php');
		 require_once('Player.php');
		 
		 $returnArray = array();
		 
		 $sessionSteamID = getSessionSteamID();
		 
		 if ($sessionSteamID && isset($_REQUEST['action'])) {
		 	$action = $_REQUEST['action'];
		 	$sessionSteamID = $_SESSION['steamid'];
		 
		 	// ---- Upsert Friend status ------------------------------------------------------
		 	if ($action == 'upsertFriend' && isset($_REQUEST['friend_steamid'])) {
		 		$friend_steamid = $_REQUEST['friend_steamid'];
		 			
		 		$favorite = 0;
		 		if (isset($_REQUEST['favorite'])) {
		 			$favorite = $_REQUEST['favorite'];
		 		}
		 
		 		$removeFriend = FALSE;
		 		if (isset($_REQUEST['subaction'])) {
		 			$action = $_REQUEST['subaction'];
		 			if ($action==='remove') {
		 				$removeFriend = TRUE;
		 			}
		 		}
		 
		 		$con = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
		 		$userResults = $con->one('select id from player where steamid="'.$sessionSteamID.'"');
		 		if ($con->records > 0) {
		 			$player_id = $userResults['id'];
		 			$friend_id = NULL;
		 			
		 			$friendResults = $con->one('select id from player where steamid="'.$friend_steamid.'"');
		 			
		 			if ($con->records == 1) {
		 				$friend_id = $friendResults['id'];
		 			} else {
		 				// Adding a new player as a friend.  Need to create an instance of Player to fetch Steam data
		 				$friend = new Player($friend_steamid, Player::REFRESH_AUTO);
		 				if ($friend != NULL && !empty($friend->id)) {
		 					$friend_id = $friend->id;
		 				}
		 			}
		 			
		 			if ($friend_id != NULL) {
		 				$con->one('select id from player_friend where player_id='.$player_id.' and friend_id='.$friend_id);
		 				if ($removeFriend == TRUE) {
		 					if ($con->records > 0) {
		 						$con->update('delete from player_friend where player_id='.$player_id.' and friend_id='.$friend_id);
		 						$returnArray['action_performed'] = 'delete';
		 					}
		 				} else {
		 					if ($con->records > 0) {
		 						$con->update('update player_friend set favorite='.$favorite.' where player_id='.$player_id.' and friend_id='.$friend_id);
		 						$returnArray['action_performed'] = 'update';
		 					} else {
		 						$con->update('insert into player_friend (player_id, friend_id, favorite) values ('.$player_id.', '.$friend_id.', '.$favorite.')');
		 						$returnArray['action_performed'] = 'insert';
		 					}
		 					$returnArray['isFavorite'] = $favorite;
		 					$returnArray['image_url'] = ($favorite ? 'images/favorite_on.png' : 'images/favorite_unchecked.png');
		 				}
		 			} else {
		 				$returnArray['error'] = 'Friend not found in database';
		 			}
		 		} else {
		 			$returnArray['error'] = 'Player not found in database';
		 		}
		 		$con->CloseConnection();
		 	} // end friend update
		 
		 
		 	// ---- Update note ---------------------------------------------------------
		 	if ($action == 'upsertNote' && isset($_REQUEST['targetSteamID']) && isset($_REQUEST['note']) && isset($_REQUEST['is_public'])) {
		 		$targetSteamID = $_REQUEST['targetSteamID'];
		 		$noteText = substr(strip_tags($_REQUEST['note']), 0, 512);
		 		$isPublic = ($_REQUEST['is_public'] == 0 ? 0 : 1);		// 0=private, 1=public
		 			
		 		$sessionMYSQLID = null;
		 		$targetMYSQLID = null;
		 		$con = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
		 		$results = $con->one('select id from player where steamid="'.$sessionSteamID.'"');
		 		if ($con->records > 0) {
		 			$sessionMYSQLID = $results['id'];
		 		}
		 		$results = $con->one('select id from player where steamid="'.$targetSteamID.'"');
		 		if ($con->records > 0) {
		 			$targetMYSQLID = $results['id'];
		 		}
		 			
		 		$noteText = $con->SecureData($noteText);
		 
		 		if (!empty($sessionMYSQLID) && !empty($targetMYSQLID)) {
		 			$returnArray['note'] = stripslashes($noteText);
		 			$existingRecordID = null;
		 
		 			// Look for an existing record
		 			$results = $con->one('select id from player_notes where player_id='.$sessionMYSQLID.' and target_player_id='.$targetMYSQLID . ' and is_public='.$isPublic);
		 			if ($con->records > 0) {
		 				$existingRecordID = $results['id'];
		 			}
		 
		 			// Delete the comment if it's empty
		 			if (empty($existingRecordID)) {
		 				// insert new record
		 				$con->insert('insert into player_notes (player_id, target_player_id, post_date, is_public, note) values ('.$sessionMYSQLID.', '.$targetMYSQLID.', now(), '.$isPublic.', "'.$noteText.'")');
		 				$returnArray['action_performed'] = 'insert';
		 
		 			} else {
		 					
		 				if (empty($noteText)) {
		 					$con->delete('delete from player_notes where id='.$existingRecordID);
		 					$returnArray['action_performed'] = 'delete';
		 				} else {
		 					// update existing record
		 					$con->update('update player_notes set note="'.$noteText.'" where id='.$existingRecordID);
		 					$returnArray['action_performed'] = 'update';
		 				}
		 					
		 			}
		 
		 		}
		 		$con->CloseConnection();
		 	}	// end update private note
		 
		 } else {
		 	$returnArray['error'] = 'Invalid inputs';
		 }
		 
		 header('Content-type: application/json');
		 echo json_encode($returnArray);
	}
	
	
	function deletePlayer() {
		$result = FALSE;
		if (isAdmin()) {
			require_once('Player.php');

			if (isset($_REQUEST['steamid'])) {
				$steamid = $_REQUEST['steamid'];
				$player = new Player($steamid, Player::REFRESH_FORCE_OFF);
				$result = $player->delete();
			}
			header('Content-type: application/json');
			echo json_encode(array('Result' => $result));
		}
		
	}

	
	// Bot lobby functions
	function getTeamLeaderPlayerId() {
		require_once('common.php');
		require_once('MySQL.php');
		$id = null;
		$teamLeader = getSessionSteamID();
	
		if (!empty($teamLeader)) {
			$con = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
			$result = $con->one('select p.id from player p where p.steamid = "'.$teamLeader.'" and exists (select * from player_group pg where pg.player_id = p.id)');
			$id = $result['id'];
		}
	
		return $id;
	}
	
	function refreshTeams() {
		require_once('common.php');
		require_once('MySQL.php');
		$returnArray = array();
	
		$playerId = getTeamLeaderPlayerId();
	
		if (!empty($playerId)) {
			$con = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
			$teamResults = $con->query(
					'select gl.id as team_id, timestampdiff(second, gl.created, now()) as age_in_seconds, gl.lobby_id, gl.invitations_sent, gl.slots_available, gl.mission_id, '.
					'		p.id as player_id, p.steamid, p.name as "player_name", p.total_tours, '.
					'		m.name as "mission_name", t.short_name as "tour_name", '.
					'		r.name as "region_name", mg.id as "mvm_group_id", mg.name as "mvm_group_name" '.
					'from group_lobby gl '.
					'inner join mission m on gl.mission_id = m.id '.
					'inner join tour t on m.tour_id = t.id '.
					'inner join player p on gl.player_id = p.id '.
					'inner join region r on gl.region_id = r.id '.
					'inner join mvm_group mg on gl.mvm_group_id = mg.id '.
					'inner join player_group pg on pg.mvm_group_id = mg.id and pg.player_id = '.$playerId.' '.
					'where gl.player_initialized = 1'
			);
			
			$uninitializedTeam = $con->query(
					'select '.
					'	gl.id as team_id, '.
					'	gl.lobby_id, '.
					'	p.steamid, '.
					'	p.name as "player_name" '.
					'from group_lobby gl '.
					'inner join player p on gl.player_id = p.id '.
					'where gl.player_id = '.$playerId.' '.
					'and gl.player_initialized = 0'
			);
			
			$validGroups = $con->query(
					'select mg.id, mg.name '.
					'from player_group pg '.
					'inner join mvm_group mg on mg.id = pg.mvm_group_id '.
					'where player_id = '.$playerId
			);
			
			$returnArray['userPlayerId'] = $playerId; 
			$returnArray['validGroups'] = $validGroups; 
			$returnArray['unitializedTeam'] = $uninitializedTeam;
			$returnArray['teams'] = $teamResults;
		} else {
			$returnArray['error'] = 'Your Steam ID is not associated with a valid MvM Group';
		}

		header('Content-type: application/json');
		echo json_encode($returnArray);
	}
	
	
	function updateTeam() {
		require_once('common.php');
		require_once('MySQL.php');
		$returnArray = array();
		$error = null;
		$mission_id = null;
		$slots_available = null;
		$region_id = null;
	
		$teamLeaderID = getTeamLeaderPlayerId();
	
		if (empty($teamLeaderID)) {
			$error = 'Not logged in through Steam or not associated with a valid MvM group';
		}
	
		if (isset($_REQUEST['slots_available'])) {
			$slots_available = $_REQUEST['slots_available'];
		} else {
			$error = 'slots_available is a required field';
		}
	
		if (empty($error)) {
			$con = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
			$results = $con->query('select id, lobby_id, created, player_initialized, invitations_sent, mission_id, region_id, slots_available from group_lobby where player_id = ' . $teamLeaderID);
				
			if ($con->records == 1) {
				// Valid team found
				$team = $results[0];
	
				if ($team['player_initialized'] == 0) {
					// Team not initialized - set the mission_id, region, and slots available
						
					if (isset($_REQUEST['mission_id'])) {
						$mission_id = $_REQUEST['mission_id'];
					} else {
						$error = 'Mission ID is a required field';
					}
						
					if (isset($_REQUEST['region_id'])) {
						$region_id = $_REQUEST['region_id'];
					} else {
						$error = 'Region ID is a required field';
					}
					
					if (isset($_REQUEST['group_id'])) {
						$group_id = $_REQUEST['group_id'];
					} else {
						$error = 'Group ID is a required field';
					}
					
					if (empty($error)) {
						$con->update(
								'update group_lobby '.
								'set player_initialized=1, mission_id='.$mission_id.', region_id='.$region_id.', mvm_group_id='.$group_id.', slots_available='.$slots_available.' '.
								'where player_id='.$teamLeaderID
						);
					}
						
				} else {
					// Team already initialized - only slot updates allowed
					$con->update(
							'update group_lobby '.
							'set slots_available='.$slots_available.' '.
							'where player_id='.$teamLeaderID
					);
				}
	
			} else {
				$error = 'No valid team found.';
			}
				
		}
	
		if (empty($error)) {
			$returnArray['Status'] = 'Success';
		} else {
			$returnArray['error'] = $error;
		}
	
		header('Content-type: application/json');
		echo json_encode($returnArray);
	}
	
	
	function deleteTeam() {
		require_once('common.php');
		require_once('MySQL.php');
		$returnArray = array();
		$error = null;
		$teamLeaderID = getTeamLeaderPlayerId();
	
		if (empty($teamLeaderID)) {
			$error = 'Not logged in through Steam or not associated with a valid MvM group';
		} else {
			$con = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
			$con->delete('delete from group_lobby where player_id = ' . $teamLeaderID);
		}
	
		if (empty($error)) {
			$returnArray['Status'] = 'Success';
		} else {
			$returnArray['error'] = $error;
		}
	
		header('Content-type: application/json');
		echo json_encode($returnArray);
	}
	
	
	function getServerList() {
		require_once('common.php');
		require_once('MySQL.php');
		$returnArray = array();
	
		$con = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);

		$returnArray['serverList'] = $con->query(
			'select '.
			'	s.id, '.
			'	s.ipport, '.
			'	sl.location, '.
			'	rg.key as "region_key", '.
			'	rg.name as "region_name", '.
			'	s.player_count, '.
			'	m.name as "mission_name", '.
			'	t.short_name as "tour_name" '.
			'from servers s '.
			'inner join mission m on m.id = s.mission_id '.
			'inner join tour t on t.id = m.tour_id '.
			'inner join server_location sl on sl.id = s.location_id '.
			'inner join region rg on rg.id = sl.region_id '.
			'where s.is_source_engine_game = 1 '.
			'and s.mission_id is not null '.
			'order by rg.id, sl.location, s.ipport '
		);
		error_log(print_r($con,true));
		$con->CloseConnection();
		error_log(print_r($returnArray,true));
	
		header('Content-type: application/json');
		echo json_encode($returnArray);
	}
	
?>