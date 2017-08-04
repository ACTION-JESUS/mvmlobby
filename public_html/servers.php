<?php
	require_once('..'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'config.php');
	require_once('header.php');
	require_once('MySQL.php');
	
	$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
?>

<div style="max-width: 900px; margin: 10px auto;">
	<div id="server-summary" style="margin-bottom: 20px">
		<h4>Mann Up Server Summary</h4>
		<table id="server-summary-table">
			<tr><td><strong>Active Servers</strong></td><td id="active-servers" class="cellalignright">0</td></tr>
			<tr><td><strong>Empty Servers</strong></td><td id="empty-servers" class="cellalignright">0</td></tr>
			<tr><td><strong>Total Servers</strong></td><td id="total-servers" class="cellalignright">0</td></tr>
			<tr><td><strong>Total Players</strong></td><td id="total-players" class="cellalignright">0</td></tr>
		</table>
	</div>
	
	<div class="server-list-container">
		<table id="server-list-table">
			<thead>
				<tr>
					<th>Region</th>
					<th>Location</th>
					<th>Tour</th>
					<th>Mission</th>
					<th>Players</th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>	<!-- server-list-container -->
</div>

<script>
var gTeamTemplate;

$(document).ready(function() {
	getServerList();
	gServerTemplate = $("#server-template").html();

	// window.setInterval(getServerList, 5000);
});

function getServerList() {
	$.ajax({
		url: 'mvmlobbyAPI.php',
		type: 'GET',
		data: {
			'apitype': 'getServerList'
		}
	})
	.done(function(serverResults) {
		if ('serverList' in serverResults) {
			displayServerList(serverResults.serverList);
		}
	})
	.fail(function(error) {
	});
}

function displayServerList(serverList) {

	/*
		serverList
			[0] {
				id: "84265"
				ipport: "208.78.164.165.27029"
				location: "Virginia"
				mission_name: "Metro Malice"
				player_count: "6"
				region_key: "NA"
				region_name: "North America"
				tour_name = "Two Cities"
			}
	*/

	var total_players = 0;
	var total_active_servers = 0;
	var total_empty_servers = 0;
	var current_region = '';
	var current_region_empty_servers = 0;
	for(var serverIdx in serverList) {

		var server = serverList[serverIdx];

		if (current_region != server.region_name) {
			// switch regions here
			current_region = server.region_name;
			current_region_empty_servers = 0;
		}

		if (server.player_count == 0) {
			++current_region_empty_servers;
			++total_empty_servers;
		} else {
			++total_active_servers;
			total_players += Number(server.player_count);
			var serverHTML =
				'<tr>'+
				'<td>'+server.region_name+'</td>'+
				'<td>'+server.location+'</td>'+
				'<td>'+server.tour_name+'</td>'+
				'<td><a href="#" onclick="displayServerInfo('+"'"+server.ipport+"','"+server.mission_name+"',''"+'); return false;">'+server.mission_name+'</a></td>'+
				'<td class="intcell">'+server.player_count+'</td>'+
				'</tr>'
			;
	
			$("#server-list-table tbody").append(serverHTML);
		}
		
	}

	$('#active-servers').html(total_active_servers);
	$('#empty-servers').html(total_empty_servers);
	$('#total-servers').html((Number(total_empty_servers) + Number(total_active_servers)));
	$('#total-players').html(total_players);

    $("#server-list-table").tablesorter();
}

function isInteger(value) { 
    return !isNaN(parseInt(value,10)) && (parseFloat(value,10) == parseInt(value,10)); 
}

</script>
<script type="text/javascript" src="js/jquery.tablesorter.js"></script>

<?php include('server_details.php'); ?>
<?php include('footer.php'); ?>