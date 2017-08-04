<?php

// /var/www/run/../../resources/config.php
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'config.php');
require_once(OS_ROOT_DIR.DS.'MySQL.php');

$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);

$hour = date("Y-m-d H:00:00");

// Get the grand totals first
$serverResult = $sqlcon->one('select sum(player_count) as player_count from servers where mission_id is not null');

// Check for an existing records... should be none
$existingRecords = $sqlcon->query('select * from server_time_summary where summary_date="' . $hour . '"');
if (sizeof($existingRecords)===0) {
	$sqlcon->insert('insert into server_time_summary (summary_date, player_count) values ("'.$hour.'", '.$serverResult['player_count'].')');
}


$serverResults = $sqlcon->query('
SELECT location_id, SUM(player_count) as player_count
FROM servers
WHERE mission_id IS NOT NULL 
GROUP BY location_id
having player_count > 0');

foreach($serverResults as $serverResult) {
	$locationId = $serverResult['location_id'];
	$playerCount = $serverResult['player_count'];
	$existingRecords = $sqlcon->query('select * from server_region_time_summary where summary_date="' . $hour . '" and location_id='.$locationId);
	if (sizeof($existingRecords)===0) {
		$sqlcon->insert('insert into server_region_time_summary (location_id, summary_date, player_count) values ('.$locationId.', "'.$hour.'", '.$serverResult['player_count'].')');
	}
}

$sqlcon->CloseConnection();

?>