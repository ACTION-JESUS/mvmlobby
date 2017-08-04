<?php

	// Converts minutes into 14m/10h/5d
	function getDateAge($ageInMinutes) {
		$ageResult = '?';
		if (!empty($ageInMinutes) || $ageInMinutes=='0') {
			$intAge = intval($ageInMinutes);
			if ($intAge >= 1440) {		// 1 day
				$ageResult = floor($intAge/1440).'d';
			} else if ($intAge >= 60) {
				$ageResult = floor($intAge/60).'h';
			} else {
				$ageResult = $intAge.'m';
			}
		}
		return $ageResult;
	}
	
	
	function getURLContents($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5000);		// 5 seconds max
		$urlContents = curl_exec($ch);
		curl_close($ch);
		return $urlContents;
	}

	
	/* 
	 * Real Steam 64 ID's should always be:
	 * 	- 17 digits long
	 *  - all numeric
	 *  - in db all start with 7656119, not sure how reliable this is
	 */
	function steamIDFormatIsValid($steamid) {
		if (strlen($steamid) == 17 && is_numeric($steamid) && strpos($steamid, '7656') === 0) {
			return true;
		} else {
			return false;
		}
	}
	
	
	/*
	 This will convert an input to a Steam ID.
	 9/26/2014 - updated to return an array of Steam ID's
	
	Working so far:
	76561197973743060 U:1:13477332 STEAM_0:0:6738666 
	76561198043290448 U:1:83024720 STEAM_0:0:41512360
	76561198004117755 U:1:43852027 STEAM_0:1:21926013
	
	action_jesus
	http://steamcommunity.com/id/action_jesus
	http://steamcommunity.com/id/action_jesus/friends/
	http://steamcommunity.com/profiles/76561197965043615
	http://steamcommunity.com/profiles/76561197965043615/inventory/
	
	76561197973743060 76561198043290448
	*/
	function getSteamIDFromInput($steamInput) {
		require_once('SteamAPI.php');
		
		$steamInput = trim($steamInput);
		$steamIDArray = array();
		
		if (empty($steamInput)) {
			return null;
		}
		
		// Already in ID64 format?
		if (steamIDFormatIsValid($steamInput)) {
			$steamIDArray[] = $steamInput;
		}
		
		// Check for the steam ID anywhere in the input (URL or any form of garbage input)
		if (empty($steamIDArray)) {
			preg_match_all('/7656[0-9]{13}/', $steamInput, $idArray);
			if (!empty($idArray)) {
				foreach($idArray[0] as $steamid) {
					$steamIDArray[] = $steamid;
				}
			}
		}
		
		$con = null;
		// Check for SteamID32 (Steam2 format) (STEAM_0:0:673866)
		if (preg_match_all('/steam_[0-9]:[0-9]:[0-9]*/i', $steamInput, $idArray)) {
			if (!empty($idArray)) {
				foreach($idArray[0] as $steamID32) {
					$stringFrags = explode(':', $steamID32);
					if (count($stringFrags) == 3) {
						$authServer = $stringFrags[1];
						$userid = $stringFrags[2];
						if (is_numeric($authServer) && is_numeric($userid)) {
							if ($con == null) {
								require_once('MySQL.php');
								// PHP cannot reliably add 64 bit numbers but MySQL can
								// the magic number:  76561197960265728
								$con = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
							}
							$result = $con->one('select (('.$userid.'*2)+'.$authServer.')+'.STEAM_ID_OFFSET.' as steam64id');
							$steamIDArray[] = $result['steam64id'];
						}
					}
				}
			}
		}
		
		// Check for Steam3 format (U:1:13477332)
		if (preg_match_all('/u:[0-9]:[0-9]*/i', $steamInput, $idArray)) {
			if (!empty($idArray)) {
				foreach($idArray[0] as $steam3ID) {
					$stringFrags = explode(':', $steam3ID);
					if (count($stringFrags) == 3) {
						$steam3IDFrag3 = $stringFrags[2];
						if (is_numeric($steam3IDFrag3)) {
							if ($con == null) {
							require_once('MySQL.php');
								// PHP cannot reliably add 64 bit numbers but MySQL can
								// the magic number:  76561197960265728
								$con = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
							}
							$result = $con->one('select '.$steam3IDFrag3.'+'.STEAM_ID_OFFSET.' as steam64id');
							$steamIDArray[] = $result['steam64id'];
						}
					}
				}
			}
		}

		// If no valid ID found, look for a vanity URL and verify it
		if (empty($steamIDArray)) {
			$vanityURL = null;
			
			// Check for a valid steam ../id/<vanity_url> first
			if (strpos($steamInput, 'http') === 0) {
				$path = parse_url(urldecode($steamInput), PHP_URL_PATH);
				$pathFragments = explode('/', $path);
				foreach($pathFragments as $key=>$fragment) {
					if ($fragment=='id' && count($pathFragments)>($key+1)) {
						$vanityURL = $pathFragments[$key+1];
						break;
					}
				}
			}
			
			// Everything else failed, so try the raw input as a vanity url
			if ($vanityURL == null) {
				$vanityURL = $steamInput;
			}
			
			$steam64ID = SteamAPI::resolveVanityURL($vanityURL);
			if ($steam64ID) {
				$steamIDArray[] = $steam64ID;	// found it!
			}
		}
		
		return array_unique($steamIDArray);		// removes duplicate entries
	}
	
	
	/**
	 *	Convert an ID from the console to the Steam64 lobby ID
	 *	From:  L:1:1444365316 
	 *    To:  109775242361521156
	 *    
	 *  The magic formula:
	 *  (0x186000000000000 | accountId)
	*/
	function convertLobby32IDToLobby64ID($lobby32ID) {
		// 0x186000000000000 | $accountId
		// Check for SteamID32 (Steam2 format) (STEAM_0:0:673866)
		$steam32ID = '';
		if (preg_match_all('/L:1:[0-9]*/', $lobby32ID, $idArray)) {
			if (!empty($idArray)) {
				$con = null;
				foreach($idArray[0] as $steamID32) {
					$stringFrags = explode(':', $steamID32);
					if (count($stringFrags) == 3) {
						$accountId = $stringFrags[2];
						if (is_numeric($accountId)) {
							if ($con == null) {
								require_once('MySQL.php');
								// PHP cannot reliably add 64 bit numbers but MySQL can
								$con = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
							}
							$result = $con->one('select 0x186000000000000 | '.$accountId.' as lobby64id');
							$steam32ID = $result['lobby64id'];
						}
					}
				}
			}
		}
		return $steam32ID;
	}

?>
