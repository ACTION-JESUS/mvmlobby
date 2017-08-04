<?php

// /var/www/run/../../resources/config.php
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'config.php');
require_once(OS_ROOT_DIR.DS.'MySQL.php');
require_once(OS_ROOT_DIR.DS.'SteamAPI.php');


$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);

$serverResults = $sqlcon->query('
select ipport
from servers
');

$results = Array();
foreach($serverResults as $serverResult) {
	try {
		echo 'Checking server ' . $serverResult['ipport'] . "\n";
		$serverInfo = SteamAPI::getServerInfo($serverResult['ipport']);
		$serverName = $serverInfo['Name'];	// Valve MvM Mann Up Server (Sydney srcds071 #11)
		if (strpos($serverName, 'Valve MvM Mann Up Server') === 0) {
			$cityStart = strpos($serverName, '(');
			$cityEnd = strpos($serverName, 'src', $cityStart);
			$city = substr($serverName, $cityStart+1, $cityEnd-$cityStart-2);
				
			echo '- MvM server found for ' . $city . "\n";
			
			if (array_key_exists($city, $results)) {
				$results[$city] += 1; 
			} else {
				$results[$city] = 1;
				echo "    - in_array=" . in_array($city, $results) . "\n";;
			}
			
		} else {
			echo ' - not an MvM server' . "\n";
		}
	} catch (Exception $e) {
		
	}
	
}

print_r($results);

?>