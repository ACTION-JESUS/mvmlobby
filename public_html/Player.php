<?php

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'config.php');
include_once('common.php');
include_once('MySQL.php');
include_once('SteamAPI.php');

class Player {
	// Summary info
	public $steamid;
	public $id = null;
	public $name = '';
	public $region = '';
	public $site_status = 'Active';
	public $image_url_small = null;
	public $image_url_medium = null;
	public $image_url_large = null;
	public $favorite = null;
	public $inventory_unavailable = false;
	public $profile_visibility = null;
	public $inventory_status = null;
	public $steam3id = '';
	
	// Use these from the summary data and server query for a "game status" ("Playing TF2", "Playing Hamlet", "Playing Rust")
	public $is_playing_tf2 = FALSE;
	public $current_mvm_mission_id = NULL;
	public $current_mvm_mission_name = NULL;
	public $mission_wiki_url = NULL;
	public $current_game_name = NULL;
	public $current_game_ipport = NULL;

	// Inventory info
	public $inventory_last_updated = 0;
	public $ticketCount = 0;
	public $totalTours = 0;
	public $tourArray = array();
	
	// Status info
	public $last_known_status_code = NULL;
	public $summary_last_updated_in_minutes = NULL;
	public $inventory_last_updated_in_minutes = NULL;
	public $summary_display_age;
	public $inventory_display_age;
	public $status = NULL;
	public $status_color = null;
	
	public $robots_killed = 0;
	public $groupArray = NULL;
	public $is_moderator = false;
	
	// --- private variables which do not appear in JSON ------
	
	// Session
	private $session_salt = NULL;
	private $session_has_expired = NULL;

	private $sqlcon=null;
	
	const REFRESH_AUTO = 0;
	const REFRESH_FORCE_OFF = 1;
	const REFRESH_FORCE_ON = 2;
	
	function __construct($steamid=NULL,$forceFullRefresh = self::REFRESH_AUTO) {
		$this->sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
		
		if ($steamid!=NULL && steamIDFormatIsValid($steamid)) {
			$this->steamid = $steamid;
			$this->refreshData($forceFullRefresh);
		} else {
			// constructor for the bulk summary update
		}
	}
	
	public function __destruct() {
		if ($this->sqlcon!=null) {
			// $this->sqlcon->CloseConnection();
		}
	}
	
	public function getSessionSalt() { return $this->session_salt; }
	public function setSessionSalt($salt) { $this->session_salt = $salt; }
	public function sessionHasExpired() { return $this->session_has_expired; }
	
	
	public function createSessionKey() {
		return md5($this->id.$this->steamid.$this->getSessionSalt().AUTH_SALT);
	}
	
	
	public function cookieIsValid($cookieSessionKey) {
		return ($this->session_has_expired ? 0 : $cookieSessionKey === $this->createSessionKey());
	}


	private function getField($array, $fieldName) {
		if (isset($array[$fieldName])) {
			return $array[$fieldName];
		} else {
			return '';
		}
	}
	

	public function refreshData($forceRefresh=self::REFRESH_AUTO) {
		
		// Refresh the summary data
		$playerSummary = $this->sqlcon->one(
			'select
				p.id,
				p.steamid,
				p.name,
				p.region,
				p.site_status,
				p.image_url_small,
				p.image_url_medium,
				p.image_url_large,
				p.total_tours,
				p.tod_tickets,
				p.summary_last_updated,
				p.inventory_last_updated,
				p.last_known_status_code,
				convert(p.steamid,UNSIGNED INTEGER) - '.STEAM_ID_OFFSET.' as steam3id,
				TIMESTAMPDIFF(MINUTE,p.summary_last_updated,now()) as summary_last_updated_in_minutes,
				TIMESTAMPDIFF(MINUTE,p.inventory_last_updated,now()) as inventory_last_updated_in_minutes,
				p.last_known_status_code,
				p.is_playing_tf2,
				p.current_mvm_mission_id,
				mi.name as current_mvm_mission_name,
				p.current_game_name,
				p.current_game_ipport,
				p.inventory_unavailable,
				p.profile_visibility,
				p.inventory_status,
				p.session_salt,
				CASE WHEN p.session_expiration IS NULL THEN 1 ELSE (p.session_expiration < now()) END as session_has_expired,
				ss.description as status_description,
				ss.color as status_color,
				p.robots_killed
			from player p
			left outer join steam_status ss
				on ss.id=p.last_known_status_code
			left outer join mission mi
				on mi.id=p.current_mvm_mission_id
			where p.steamid = "' . $this->steamid . '"');
		
		if ($this->sqlcon->records > 0) {
			$this->id = $playerSummary['id'];
			$this->name = $playerSummary['name'];
			$this->region = $playerSummary['region'];
			$this->site_status = $playerSummary['site_status'];
			$this->image_url_small = $playerSummary['image_url_small'];
			$this->image_url_medium = $playerSummary['image_url_medium'];
			$this->image_url_large = $playerSummary['image_url_large'];
			$this->totalTours = $playerSummary['total_tours'];
			$this->ticketCount = $playerSummary['tod_tickets'];
			$this->inventory_last_updated = $playerSummary['inventory_last_updated'];
			$this->last_known_status_code = $playerSummary['last_known_status_code'];
			$this->summary_last_updated_in_minutes = $playerSummary['summary_last_updated_in_minutes'];		// empty string for NULL values
			$this->summary_display_age = getDateAge($this->summary_last_updated_in_minutes);
			$this->inventory_last_updated_in_minutes = $playerSummary['inventory_last_updated_in_minutes'];
			$this->inventory_display_age = getDateAge($this->inventory_last_updated_in_minutes);
			$this->status = $playerSummary['status_description'];
			$this->status_color = $playerSummary['status_color'];
			$this->is_playing_tf2 = $playerSummary['is_playing_tf2'];
			$this->current_mvm_mission_id = $playerSummary['current_mvm_mission_id'];
			$this->current_mvm_mission_name = $playerSummary['current_mvm_mission_name'];
			$this->current_game_name = $playerSummary['current_game_name'];
			$this->current_game_ipport = $playerSummary['current_game_ipport'];
			$this->inventory_unavailable = $playerSummary['inventory_unavailable'];
			$this->profile_visibility = $playerSummary['profile_visibility'];	// 5=Public, 1-4=various private settings
			$this->inventory_status = $playerSummary['inventory_status'];	// 1=success, 8=no steam id, 15=private, 18=invalid steamid
			$this->session_salt = $playerSummary['session_salt'];
			$this->session_has_expired = $playerSummary['session_has_expired'];
			$this->steam3id = $playerSummary['steam3id'];
			$this->robots_killed = $playerSummary['robots_killed'];
		}
		
		$this->groupArray = $this->sqlcon->query('select pg.mvm_group_id, pg.is_moderator, mg.name, mg.custom_url from player_group pg inner join mvm_group mg on mg.id = pg.mvm_group_id where pg.player_id = '.$this->id);
		foreach($this->groupArray as $group) {
			if ($group['is_moderator'] == 1) {
				$this->is_moderator = true;
			}
		}
		
		// Load Steam data for new players or if the summary is old
		$isNewPlayer = ($this->id == NULL ? TRUE : FALSE);
		
		if ($isNewPlayer
			|| ($forceRefresh != self::REFRESH_FORCE_OFF &&
					($forceRefresh==self::REFRESH_FORCE_ON
						|| $this->summary_last_updated_in_minutes == NULL
						|| $this->summary_last_updated_in_minutes == ''
						|| intval($this->summary_last_updated_in_minutes) > SUMMARY_REFRESH_MINUTES
					)
				)
			) {
			$playerResults = SteamAPI::getPlayerSummary($this->steamid);
			if (empty($playerResults['error'])) {
				$this->updateFromSteamSummary($playerResults['player']);
			}
		}

		// Change the status if the user is currently playing
		if (!empty($this->current_game_ipport)) {
			$serverInfo = SteamAPI::getServerMission($this->current_game_ipport);	// returns mission_id, tour_name, mission_name
			if ($serverInfo != NULL) {
				$this->current_mvm_mission_id = $this->getField($serverInfo, 'mission_id');
				$this->current_mvm_mission_name = $this->getField($serverInfo, 'mission_name');
				$this->mission_wiki_url = $this->getField($serverInfo, 'wiki_url');
				if ($this->id != null) {
					$this->sqlcon->update('update player set current_mvm_mission_id='.$this->current_mvm_mission_id.' where id='.$this->id);
					$this->sqlcon->insert('
						INSERT INTO servers (ipport, last_known_used)
							VALUES ("'.$this->current_game_ipport.'", now()) 
						ON DUPLICATE KEY
						    UPDATE last_known_used = now();
					');
				}
			}
		}
		
		if (!empty($this->current_game_name)) {
			if (!empty($this->current_mvm_mission_name)) {
				$this->status = 'Playing '.$this->current_mvm_mission_name;
		
			} else {
				$this->status = 'Playing '.$this->current_game_name;
			}
			$this->status_color = '#060';
		}
		
		// Something wrong, can't get data from the database or Steam
		if ($this->id == null) {
			die('Failed to retrieve player data.');
		}

		// Refresh inventory
		
		// Fetch tours from cache first
		$this->tourArray = $this->sqlcon->query('select t.id, t.defindex, t.sortorder, pt.mission_bitmask, pt.tours_completed, CASE WHEN tour_last_changed_date is null then 0 ELSE date_add(tour_last_changed_date, INTERVAL 1 MONTH) >= now() END as isActive from tour t left outer join player_tour pt on pt.tour_id = t.id and pt.player_id='.$this->id, 'id');
		
		$inventoryFetched = false;
		if ($this->profile_visibility == 1) {	// private profile
			
			// Set inventory_last_updated to now() to prevent the refresh script from attempting to get inventory 
			$this->inventory_unavailable = TRUE;
			$this->sqlcon->update('update player set inventory_unavailable=1, inventory_last_updated=now(), inventory_status=15 where id='.$this->id);
			
		} elseif ($isNewPlayer
					|| ($forceRefresh!=self::REFRESH_FORCE_OFF
							&& ($forceRefresh==self::REFRESH_FORCE_ON
								|| $this->inventory_last_updated_in_minutes==NULL
								|| $this->inventory_last_updated_in_minutes==''
								|| intval($this->inventory_last_updated_in_minutes) > INVENTORY_REFRESH_MINUTES
							)
						)
				) {
			$tf2InventoryResults = SteamAPI::getTF2Inventory($this->steamid);
			
			if (!isset($tf2InventoryResults['items']) || empty($tf2InventoryResults['items'])) {
				
 				$this->inventory_unavailable = TRUE;
				
 				$inventoryStatusSQL = '';
 				if (isset($tf2InventoryResults['status'])) {
 					$this->inventory_status = $tf2InventoryResults['status'];
 					if ($this->inventory_status == 15) {
 						// indicates that the inventory should NOT be refreshed on F5.  For Steam API issues it should refresh.
 						$inventoryStatusSQL = ' inventory_last_updated=now(),'; 
 					}
 				}
 				if (empty($this->inventoryStatus)) {
 					// 'status' not found or is empty, need to change to an integer
 					$this->inventory_status = 0;
 				}
 						
				$this->sqlcon->update('update player set inventory_unavailable=1, '.$inventoryStatusSQL.' inventory_status='.$this->inventory_status.' where id='.$this->id);
				
			} else {
				$tf2Items = $tf2InventoryResults['items'];
				$inventoryFetched = true;
				$oldTotalTours = $this->totalTours;
				$this->totalTours = 0;
				$this->ticketCount = 0;
				$this->inventory_last_updated_in_minutes = 0;
				$this->inventory_unavailable = FALSE;
				$this->inventory_display_age = getDateAge('0');
				$this->inventory_status = $tf2InventoryResults['status'];
				
				$tourDefindexToMysqlIDArray = array();
				$tourDefindexArray = array();
				foreach($this->tourArray as $tour) {
					$tourDefindexArray[] = $tour['defindex'];
					$tourDefindexToMysqlIDArray[$tour['defindex']] = $tour['id'];
				}

				// Search inventory for badges and TOD tickets
				foreach ($tf2Items as $item) {
					$defindex = $item['defindex'];
					$badgeToursCompleted = $item['level'];		// if attribute 2026 is found, use that value instead for Tours Completed (for tours > 255)
						
					if (in_array($defindex, $tourDefindexArray)) {
						$currentTourId = $tourDefindexToMysqlIDArray[$defindex];
						$this->tourArray[$currentTourId]['tourDataChanged'] = '0';


						// Determine which missions have been completed
						foreach ($item['attributes'] as $attr) {
							$attrDefindex = $attr['defindex'];
							if ($attrDefindex == '386') {		// defindex for "tours completed" bitmask
								$bitmask = $attr['value'];
								if ($bitmask != $this->tourArray[$currentTourId]['mission_bitmask']) {
									$this->tourArray[$currentTourId]['isActive'] = '1';
									$this->tourArray[$currentTourId]['tourDataChanged'] = '1';
								}
								$this->tourArray[$currentTourId]['mission_bitmask'] = $bitmask;
							} else if ($attrDefindex == '2026') {
								$badgeToursCompleted = $attr['value'];
								$oldToursCompleted = $this->tourArray[$currentTourId]['tours_completed'];
								if ($oldToursCompleted != $badgeToursCompleted) {
									$this->tourArray[$currentTourId]['isActive'] = '1';
									$this->tourArray[$currentTourId]['tourDataChanged'] = '1';
								}
								$this->tourArray[$currentTourId]['tours_completed'] = $badgeToursCompleted;
							}
						}
						
						$oldToursCompleted = $this->tourArray[$currentTourId]['tours_completed'];
						if ($oldToursCompleted != $badgeToursCompleted) {
							$this->tourArray[$currentTourId]['isActive'] = '1';
							$this->tourArray[$currentTourId]['tourDataChanged'] = '1';
						}
						$this->tourArray[$currentTourId]['tours_completed'] = $badgeToursCompleted;
						$this->totalTours += $badgeToursCompleted;
						
						
					} else if ($defindex == '725') { // defindex for Tour of Duty ticket
						++$this->ticketCount;
					}
				}
				
				// Add a history record for the total tours
				if ($oldTotalTours < $this->totalTours) {
					$this->sqlcon->insert('insert player_total_tours_history (player_id, tours_completed, date_changed) values ('.$this->id.', '.$this->totalTours.', now())');
				}

				// Update the player_tours table
				foreach($this->tourArray as $tour) {
					$toursCompleted = $tour['tours_completed'];
					$tourIsActive = (isset($tour['tourDataChanged'])?$tour['tourDataChanged']:0);			// not available for tours never played or badge does not exist
					$missionBitmask = (isset($tour['mission_bitmask']) ? $tour['mission_bitmask'] : 0);		// not available for tours never played or badge does not exist

					$results = $this->sqlcon->one('select id from player_tour where player_id='.$this->id.' and tour_id='.$tour['id']);
					if ($this->sqlcon->records == 0) {
						if ( $toursCompleted > 0 ) {
							$this->sqlcon->insert('insert player_tour (player_id, tour_id, tours_completed, mission_bitmask) values ('.$this->id.', '.$tour['id'].', '.$toursCompleted.', '.$missionBitmask.')');
						}	// else do not insert a record since they have zero
					} else {
						$id = $results['id'];
						$this->sqlcon->update('update player_tour set '.($tourIsActive?'tour_last_changed_date=now(), ':'').' tours_completed='.$toursCompleted.', mission_bitmask='.$missionBitmask.' where id='.$id);
					}
					if ($tourIsActive === '1') {
						//$this->executeSQL('insert into player_tour_history select null, '.$this->id.', '.$tour['id'].', '.$toursCompleted.', now() on duplicate key update tour');
						$this->sqlcon->insert('insert into player_tour_history (id, player_id, tour_id, tours_completed, date_changed) select null, '.$this->id.', '.$tour['id'].', '.$toursCompleted.', now() on duplicate key update tours_completed = '.$toursCompleted);
					}
				}
				
				// Get robots killed
				$apiRobotsKilled = SteamAPI::getRobotsKilled($this->steamid);
				if ($apiRobotsKilled >= 0) { $this->robots_killed = $apiRobotsKilled; }				
				
				$this->sqlcon->update('update player set robots_killed='.$this->robots_killed.', total_tours='.$this->totalTours.', tod_tickets='.$this->ticketCount.($inventoryFetched?', inventory_last_updated=now(), inventory_status='.$this->inventory_status.', inventory_unavailable=0 ':'').' where id='.$this->id);

			}
		}
	}
	
	
	/**
	 * Parse the Steam summary, populate Player fields, and update the database
	 * This may be called from "new Player(steamid)" or from update_all_friends_status.php to update using a single API call
	 * 
	 * @param $steamSummary - Array from Steam JSON player summary
	 */
	public function updateFromSteamSummary($steamSummary, $doNotInsert=FALSE) {
		$prevousStatusCode = $this->last_known_status_code;
		$this->steamid = $this->getField($steamSummary, 'steamid');
		$this->name = $this->getField($steamSummary, 'personaname');
		$this->region = $this->getField($steamSummary, 'loccountrycode');
		$this->image_url_small = $this->getField($steamSummary, 'avatar');
		$this->image_url_medium = $this->getField($steamSummary, 'avatarmedium');
		$this->image_url_large = $this->getField($steamSummary, 'avatarfull');
		$this->last_known_status_code = $this->getField($steamSummary, 'personastate');
		$this->profile_visibility = $this->getField($steamSummary, 'communityvisibilitystate');
		$this->summary_display_age = getDateAge('0');
		if (empty($this->steam3id)) {
			$results = $this->sqlcon->one('select '.$this->steamid.' - '.STEAM_ID_OFFSET.' as steam3id');
			$this->steam3id = $results['steam3id'];
		}
		$dbPlayerName = $this->sqlcon->SecureData($this->name);
		
		$this->is_playing_tf2 = FALSE;
		$this->current_mvm_mission_id = NULL;
		$this->current_mvm_mission_name = NULL;
		$this->current_game_name = $this->getField($steamSummary, 'gameextrainfo');
		$this->current_game_ipport = NULL;
		$gameid = $this->getField($steamSummary, 'gameid');
		if (!empty($gameid) && $gameid=='440') {
			$this->is_playing_tf2 = TRUE;
			if (isset($steamSummary['gameserversteamid'])) {
				$this->current_game_ipport = $this->getField($steamSummary, 'gameserverip');
			}
		}
		// 76561198042213743 (new player)
		
		// $player->status and $player->status_color might be off.  The values are based on the initial db query
		if ($prevousStatusCode == NULL || $prevousStatusCode != $this->last_known_status_code) {
			$result = $this->sqlcon->one('select description, color from steam_status where id=' . $this->last_known_status_code);
			if ($result) {
				$this->status = $result['description'];
				$this->status_color = $result['color'];
			} 
		}
		
		if ($this->id == null && $doNotInsert==FALSE) {

			$this->sqlcon->insert("
					INSERT INTO player (
						id,
						steamid,
						name,
						site_status,
						total_tours,
						tod_tickets,
						inventory_last_updated
					)
					VALUES (
						NULL,
						'$this->steamid',
						'$dbPlayerName',
						'Active',
						'0',
						'0',
						null
					)"
				);
			$this->id = $this->sqlcon->LastInsertID();
			$this->setSessionSalt(0);	// indicates that it needs to be set when the user first logs in
		}

		if ($this->id) {
			$this->sqlcon->update("
				update player
				set
					profile_visibility=$this->profile_visibility,
					name='$dbPlayerName',
					region='$this->region',
					image_url_small='$this->image_url_small',
					image_url_medium='$this->image_url_medium',
					image_url_large='$this->image_url_large',
					summary_last_updated=now(),
					last_known_status_code='$this->last_known_status_code',
					is_playing_tf2=".($this->is_playing_tf2 ? '1' : '0').",
					current_mvm_mission_id=NULL,
					current_game_name=".($this->current_game_name==NULL ? 'NULL' : "'".$this->sqlcon->SecureData($this->current_game_name)."'").",
					current_game_ipport=".(empty($this->current_game_ipport) ? 'NULL' : "'".$this->current_game_ipport."'")."
				where steamid='".$this->steamid."'");
			
		}
	}
	
	public function isAdmin() {
		return ($this->site_status == 'Admin' ? TRUE : FALSE);
	}
	
	// Run this 
	public function userLoggedIn() {
		$this->sqlcon->update('update player set last_active_time=now() where id='.$this->id);
	}

	
	/**
	 * Delete a player from the database
	 */
	public function delete() {
		$result = false;
		if ($this->id) {
			// `player_friend` (player_id or friend_id)
			$result = $this->sqlcon->delete('delete from player_friend where friend_id = '.$this->id.' or player_id = '.$this->id);
			
			// `player_notes` (player_id or target_player_id)
			if ($result) { $result = $this->sqlcon->delete('delete from player_notes where player_id = '.$this->id.' or target_player_id = '.$this->id); }
			
			// `player_tour` (player_id)
			if ($result) { $result = $this->sqlcon->delete('delete from player_tour where player_id = '.$this->id); }
			
			// `player_tour_history` (player_id)
			if ($result) { $result = $this->sqlcon->delete('delete from player_tour_history where player_id = '.$this->id); }
			
			// `player` (id)
			if ($result) { $result = $this->sqlcon->delete('delete from player where id = '.$this->id); }
		}
		return $result;
	}
	
	
	public function onLoginSuccess() {
		// Reset the salt when the user first logs in or when the session expires
		if ($this->getSessionSalt() == 0 || $this->sessionHasExpired()) {
			$this->setSessionSalt( mt_rand() );
		}
		
		setcookie('user_steam_id', $this->steamid, time()+86400*SESSION_DAYS);	// 86400=seconds per day
		setcookie('session_key', $this->createSessionKey(), time()+86400*SESSION_DAYS);	// 86400=seconds per day
			
		$_SESSION['steamid'] = $this->steamid;
		
		$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
		$sqlcon->update(
				'update player '.
				'set last_active_time=now(), '.
				'session_salt='.$this->getSessionSalt().', '.
				'session_expiration = date_add(now(), INTERVAL '.SESSION_DAYS.' DAY) '.
				'where id='.$this->id);
	}
}

?>