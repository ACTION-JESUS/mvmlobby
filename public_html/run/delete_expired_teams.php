<?php

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'config.php');
require_once(OS_ROOT_DIR.DS.'MySQL.php');
require_once(OS_ROOT_DIR.DS.'Player.php');

$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);

while (true) {
	$teams = $sqlcon->query('select gl.id, gl.player_id, p.steamid, timestampdiff(second, created, now()) as team_age from group_lobby gl inner join player p on p.id = gl.player_id');

	foreach($teams as $team) {
		$delete = FALSE;
		$deleteReason = '';
		$player = new Player($team['steamid'],Player::REFRESH_FORCE_ON);	// REFRESH_AUTO will update the summary every minute anyway
		
		if ($team['team_age'] > 1200) {

			$delete = TRUE;
			$deleteReason = 'the team is older than 20 minutes';
						
		} else if ($team['team_age'] > 30 && ($player->is_playing_tf2==FALSE || !empty($player->current_game_ipport))) {
			
			// Give Steam at least 30 seconds to catch up on the current player summary.
			// (In case the player finishes a game and quickly invites the bot.)
 			$delete = TRUE;
			if ($player->is_playing_tf2 == FALSE) {
 				$deleteReason = 'the player stopped playing TF2';
			}
			if (!empty($player->current_game_ipport)) {
 				$deleteReason = 'the player is playing on a TF2 server';
			}
			
		}
		
		if ($delete) {
			$sqlcon->delete('delete from group_lobby where id='.$team['id']);
			error_log('Deleted team ID: ' . $team['id']);
			error_log('  time = ' . date("F j, Y, g:i a"));
			error_log('  player_id = ' . $team['player_id']);
			error_log('  team_age = ' . $team['team_age']);
			error_log('  steamid = ' . $team['steamid']);
			error_log('  player->is_playing_tf2 = ' . $player->is_playing_tf2);
			error_log('  player->current_game_ipport = ' . $player->current_game_ipport);
			error_log('  reason = ' . $deleteReason);
		}
		
	}
	
	sleep(30);
}
?>