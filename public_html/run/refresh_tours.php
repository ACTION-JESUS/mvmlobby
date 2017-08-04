<?php

if (isset($_SERVER['HTTP_USER_AGENT'])) {
	die('Invalid request');		// no direct access
}

// /var/www/run/../../resources/config.php
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'config.php');
require_once(OS_ROOT_DIR.DS.'MySQL.php');
require_once(OS_ROOT_DIR.DS.'Player.php');

$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);

while (true) {
	// Loop through players with at least 25 total tours.
	// Refresh the tour data if it is older than 24 hours.
	$playerResults = $sqlcon->query('
		SELECT steamid
		FROM player
		WHERE inventory_last_updated is not null
		and TIMESTAMPDIFF( HOUR , inventory_last_updated, NOW( ) ) >= 24
		and (total_tours >=25 OR (last_active_time is not null and TIMESTAMPDIFF( HOUR , last_active_time, NOW( ) ) < 48))
		and profile_visibility <> 1
		and inventory_status <> 15
	');
	
	foreach($playerResults as $playerResult) {
		new Player($playerResult['steamid']);		// refresh tour data
		usleep(100000);	// sleep in microseconds (1/10 second)
	}

	sleep(60*60);	// 1 hour
}

?>