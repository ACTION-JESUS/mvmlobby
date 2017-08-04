<?php

// /var/www/run/../../resources/config.php
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'config.php');
require_once(OS_ROOT_DIR.DS.'MySQL.php');
require_once(OS_ROOT_DIR.DS.'Player.php');
require_once(OS_ROOT_DIR.DS.'SteamAPI.php');

?>

<html>
	<head>
		<title>Two Cities Veterans Activity Report</title>
		<script type="text/javascript" src="js/jquery-2.1.0.min.js"></script> 
		<script type="text/javascript" src="js/jquery.tablesorter.js"></script>
		<style>
			table {
				border: 1px solid #666;
				border-collapse: collapse;
			}
			
			th,td {
				border: 1px solid #666;
				vertical-align: middle;
				padding-left: 3px;
				padding-right: 4px;
			
			}
		
		
		</style>
	</head>
	<body style="background-color: #fff;">

		<h2>Two Cities Veterans Activity Report</h2>
	
		<table id="myTable" class="tablesorter">
			<thead>
				<tr>
					<th>#</th>
					<th>SteamID</th>
					<th>Name</th>
					<th>Total Tours</th>
					<th>Tours Played This Month</th>
					<th>Last Date Played</th>
				</tr>
			</thead>
			<tbody>
<?php

$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
// $steamIdArray = SteamAPI::getGroupMemberList('103582791434932048');

// $delimiter = '';
// $sqlMemberList = '';
// foreach ($steamIdArray as $steamId) {
// 	$sqlMemberList .= $delimiter."'".$steamId."'";
// 	$delimiter = ',';
// }

// profile visibility:  5=Public, 1-4=various private settings
// inventory_status:  1=success, 8=no steam id, 15=private, 18=invalid steamid

$firstOfMonth = date("Y-m-01");
echo $firstOfMonth;

$playerResults = $sqlcon->query('
select
	p.steamid,
	p.name,
	p.total_tours,
	previous_month_total.tours_completed as previous_total_tours,
	p.total_tours - previous_month_total.tours_completed as tours_this_month,
	p.last_known_status_code,
	p.profile_visibility,
	p.inventory_status,
	p.inventory_unavailable,
	(select max(date_changed) from player_tour_history where player_id = p.id) as last_date_played
from mvm_group mg
inner join player_group pg on pg.mvm_group_id = mg.id
inner join player p on p.id = pg.player_id
left outer join (
	select h.player_id, max(h.tours_completed) as tours_completed
	from player_total_tours_history h
	inner join player_group pg2 on pg2.player_id = h.player_id
	inner join mvm_group mg2 on mg2.id = pg2.mvm_group_id and mg2.custom_url="twocitiesveterans"
	where h.date_changed < "'.$firstOfMonth.'"'.' 
	group by h.player_id
) as previous_month_total on previous_month_total.player_id = p.id
WHERE mg.custom_url =  "twocitiesveterans"
order by tours_this_month desc, last_date_played desc, p.total_tours desc;
');

$counter = 1;
foreach($playerResults as $playerResult) {
	// new Player($playerResult['steamid']);
	$visibility = '';
	if ($playerResults['profile_visibility'] ==1 || $playerResults['inventory_status'] == 15) {
		$visibility = 'private';
	}
	echo '<tr>';
	echo '<td>'.$counter.'</td>';
	echo '<td>'.$playerResult['steamid'].'</td>';
	echo '<td><a href="profile/'.$playerResult['steamid'].'">'.$playerResult['name'].'</a></td>';
	echo '<td>'.$playerResult['total_tours'].'</td>';
	echo '<td>'.$playerResult['tours_this_month'].'</td>';
	echo '<td>'.$playerResult['last_date_played'].'</td>';
	// echo '<td>'.$visibility.'</td>';
	echo '</tr>';
	++$counter;
}

?>
			</tbody>
		</table>
		<script>
			$(document).ready(function() 
			    { 
			        $("#myTable").tablesorter(); 
			    } 
			); 
		</script>
	</body>
</html>