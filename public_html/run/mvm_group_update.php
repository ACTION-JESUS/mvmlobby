<?php

// /var/www/run/../../resources/config.php
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'config.php');
require_once(OS_ROOT_DIR.DS.'MySQL.php');
require_once(OS_ROOT_DIR.DS.'Player.php');
require_once(OS_ROOT_DIR.DS.'SteamAPI.php');

while (true) {
	$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
	
	// Fetch a list of all MvM groups
	$groupResults = $sqlcon->query('select id, gid, custom_url, name from mvm_group');
	
	// Loop through the MvM groups
	echo "Begin looping through mvm_group items\n";
	foreach($groupResults as $groupResult) {
	
		$gid = $groupResult['gid'];
		echo " - retrieving the group member list for " . $groupResult['name'] ."\n"; 
		$steamIdArray = SteamAPI::getGroupMemberList($gid);
		// file_put_contents("group_members.json",json_encode($steamIdArray));		// DEV ONLY: save steam results to file
		// $steamIdArray = json_decode(file_get_contents('group_members.json'), true);	// DEV ONLY: retrieve from file instead of steam API
	
		// Truncate temp_group_members
		echo "   - truncating table temp_group_members\n";
		$sqlcon->delete('delete from temp_group_members');
		
		// Populate temp_group_members
		echo "   - begin inserting into temp_group_members\n";
		foreach ($steamIdArray as $steamId) {
			// echo "     - adding member $steamId\n";
			$sqlcon->insert('insert into temp_group_members select "'.$steamId.'"');
		}
	
		// Delete members that are no longer in the list
		$sql = '
			delete pg.*
			from player_group pg
			inner join player p on p.id = pg.player_id
			inner join mvm_group mg on mg.gid = "' . $gid . '"
				where not exists (
			    select *
			    from temp_group_members tgm
			    where tgm.steamid = p.steamid
			)';
		$sqlcon->delete($sql);
	
		
		// Add players that are not in the database yet
		$sql = 'select tgm.steamid from temp_group_members tgm where not exists (select * from player p where p.steamid = tgm.steamid)';
		$results = $sqlcon->query($sql);
		foreach($results as $playerResult) {
			$missingPlayer = $playerResult['steamid'];
			new Player($missingPlayer);		// adds the players to the database
		}
		
	
		// Insert new players into player_group
		$sql = '
			insert into player_group (player_id, mvm_group_id)
			select p.id, mg.id
			from temp_group_members tgm
			inner join player p on p.steamid = tgm.steamid
			inner join mvm_group mg on mg.gid = "' . $gid . '"
			where not exists (
				select *
				from player_group pg
				where pg.player_id = p.id
				and pg.mvm_group_id = mg.id
			)';
		$sqlcon->insert($sql);
		
		echo " - finished with group\n";	
	}
	
	sleep (60*60);
}	// while "true"
?>