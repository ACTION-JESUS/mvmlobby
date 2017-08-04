<?php
	require_once('..'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'config.php');
	require_once('header.php');
	require_once('MySQL.php');
	
	if ($user == NULL) {
		echo '&nbsp;<br/><span style="font-weight: bold; margin-left: 20px;">Please sign in through Steam first.</span>';
		return;
	}
	
	if (!isset($user) || sizeof($user->groupArray)==0) {
		echo '&nbsp;<br/><span style="font-weight: bold; margin-left: 20px;">Teams are only displayed for members of valid MvM groups.</span>';
		return;
	}
		
	
	$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
?>

<div class="well"><h5>To create a team, invite the <a href="http://steamcommunity.com/id/2cv_lobby_bot">2CV Party Bot</a> to your party.</h5></div>

<div id="myteam" class="well" style="background-color: #f0f0f0; margin: 0px 10px 4px; padding: 2px; display: none;">

	<form id="myteam_form">
	
		<h4>Create your team for lobby ID <span id="header_lobby_id"></span>:</h4>
	
		<input id="team_id" name="team_id" value="" type="hidden">
		
		<strong>Tour:</strong>
		<select id="selectedtour" class="form-inline shortDropdown">
			<?php
				$gMissionOptions = 'var gMissionOptions = [];'."\n";
				$tourArray = $sqlcon->query('
						select t.id, t.short_name, t.name, t.difficulty, pt.mission_bitmask
						from tour t
						left outer join player_tour pt on pt.tour_id = t.id and pt.player_id='.$user->id.'
						order by sortorder
				');
	
				echo '<option value="0">&lt;select tour&gt;</option>';
				
				foreach($tourArray as $tour) {
					$tourid = $tour['id'];
					echo '<option value="'.$tourid.'"'.($tourid==4?' selected':'').'>'.$tour['short_name'].'</option>';
					$gMissionOptions .= 'gMissionOptions['.$tourid.'] = [];'."\n";
				}
			?>
		</select>
		
		<strong>Mission:</strong>
		<select id="selectedmission" class="form-inline shortDropdown">
			<?php
				$missionArray = $sqlcon->query('select id, tour_id, name, map_bitmask, sortorder from mission order by tour_id, sortorder');
				
				echo '<option value="0">&lt;select mission&gt;</option>';
				
				// All options are created in Javascript
				foreach($missionArray as $mission) {
					$tour_id = $mission['tour_id'];
					$mission_id = $mission['id'];
					$mission_name = $mission['name'];
					$gMissionOptions .= 'gMissionOptions['.$tour_id.']['.$mission_id.']="'.$mission_name.'";'."\n";
				}
			?>
		</select>
		
		<strong>Region:</strong>
		<select id="selectedregion" class="form-inline shortDropdown">
			<?php
				$regionArray = $sqlcon->query('select r.id, r.key, r.name from region r');
				
				echo '<option value="0">&lt;select region&gt;</option>';
				
				// All options are created in Javascript
				foreach($regionArray as $region) {
					$region_id = $region['id'];
					$region_key = $region['key'];
					$region_name = $region['name'];
					echo '<option value="'.$region_id.'"s>'.$region_name.'</option>'."\n";
				}
			?>
		</select>
		
		<strong>MvM Group:</strong>
		<select id="selectedmvmgroup" class="form-inline shortDropdown">
		</select>

		<strong>Players needed:</strong>
		<input id="slots_available" value="5" type="number" min="1" max="5">
		
		<div style="margin-top: 10px;"></div>
		<button type="submit" class="btn btn-info">Create Team</button>
	
	</form>
	
	<div style="margin-top: 15px;"></div>

</div>

<div class="teams-container">


</div>	<!-- teams-container -->

<div id="templates" style="display: none;">

	<div id="team-template">
		<div class="team-outer-container" team_id="{team_id}">
			<div class="team-inner-container">
				<div class="container">
					<div class="row">
						<div class="col-md-4"><b><span>{mission_name} [{tour_name}]</span></b></div>
						<div class="col-md-8">Players: <span class="slots_available">{slots_available}</span><span>/6</span></div>
					</div>
					<div class="row">
						<div class="col-md-4">
							<a href="profile/{steamid}">{player_name}</a> ({total_tours} tours)
						</div>
						<div class="col-md-8">Lobby ID: <span>{lobby_id}</span></div>
					</div>
					<div class="row">
						<div class="col-md-4"><a href="steam://joinlobby/440/{lobby_id}">Join now!</a></div>
						<div class="col-md-8">Region: <span>{region_name}</span></div>
					</div>
					<div class="row team_leader_actions" style="display: none;">
						<div class="col-md-4"><strong>Update players needed: </strong><input class="slots_available_update" value="5" type="number" min="1" max="5"></div>
						<div class="col-md-8"><button class="btn btn-danger btn-xs delete_team_button">Delete Team</button></div>
					</div>
				</div>
			</div>
		</div>
	</div>	<!-- team-template -->
	
</div>	<!-- #templates hidden div -->

<script>

<?php
	echo $gMissionOptions;
?>

var gTeamTemplate;

$(document).ready(function() {
	getTeamList();
	gTeamTemplate = $("#team-template").html();
	updateMissionOptions();

	$('#selectedtour').on('change', function() {
		updateMissionOptions();
	});

	$("#myteam_form").submit(function(e){
		createTeam();
	    return false;
	});

	
	getTeamList();
	window.setInterval(getTeamList, 5000);
});

function getTeamList() {
	$.ajax({
		url: 'mvmlobbyAPI.php',
		type: 'GET',
		data: {
			'apitype': 'refreshTeams'
		}
	})
	.done(function(teamResults) {
		if ('userPlayerId' in teamResults && teamResults) {
			if ('teams' in teamResults) {
				displayTeamList(teamResults.userPlayerId, teamResults.teams);
			}
			if ('unitializedTeam' in teamResults && teamResults.unitializedTeam.length>0) {
				initializeTeam(teamResults.userPlayerId, teamResults.unitializedTeam[0], teamResults.validGroups);
			}
		}
	})
	.fail(function(error) {
	});
}

function displayTeamList(userPlayerId, teamList) {

	// Build an array of teams already on the page (existingTeamArray[team_id]=jquery object)
	var existingTeamArray = {};
	$('.team-outer-container').each(function() {
		var team_id = $(this).attr('team_id');
		if (isInteger(team_id)) {	// i.e. not the template html
			existingTeamArray[team_id] = $(this);
		}
	});

	for(var teamIdx in teamList) {

		var team = teamList[teamIdx];

		if (team.team_id in existingTeamArray) {
			// Only update the # of slots
			$(existingTeamArray[team.team_id]).find('.slots_available').html(team.slots_available);
			delete existingTeamArray[team.team_id];
		} else {
			// add new team to the list
		
			//	var invitations_sent = team.invitations_sent;
			//	var mission_id = team.mission_id;
			//	var player_id = team.player_id;
			//	var mvm_group_name = team.mvm_group_name;
			var teamHTML = gTeamTemplate;
			teamHTML = teamHTML.replace(/{team_id}/g,team.team_id);
			teamHTML = teamHTML.replace(/{age_in_seconds}/g,team.age_in_seconds);
			teamHTML = teamHTML.replace(/{lobby_id}/g,team.lobby_id);
			teamHTML = teamHTML.replace(/{slots_available}/g,team.slots_available);
			teamHTML = teamHTML.replace(/{mission_name}/g,team.mission_name);
			teamHTML = teamHTML.replace(/{tour_name}/g,team.tour_name);
			teamHTML = teamHTML.replace(/{steamid}/g,team.steamid);
			teamHTML = teamHTML.replace(/{player_name}/g,team.player_name);
			teamHTML = teamHTML.replace(/{total_tours}/g,team.total_tours);
			teamHTML = teamHTML.replace(/{region_name}/g,team.region_name);

			var $html = $(teamHTML);

			// Display controls for the team leader
			if (userPlayerId == team.player_id) {
				$html.find('.team_leader_actions').show();
				$html.find('.slots_available_update').val(team.slots_available);
				$html.find('.delete_team_button').on('click', function() {
					deleteTeam();
				});
				$html.find('.slots_available_update').on('change', function() {
					updateTeam(this);
				});
			}
			
			$(".teams-container").append($html);
		}
		
	}

	// Delete old teams
	for (var key in existingTeamArray) {
		if (existingTeamArray.hasOwnProperty(key)) {
			$(existingTeamArray[key]).remove();
		}
	}
	
}

function updateMissionOptions() {
	var tourId = $('#selectedtour').val();
	var $mission = $('#selectedmission');
	var missionOptionsHTML = '<option value="0">select a mission</option>';
	for(var mission_id in gMissionOptions[tourId]) {
		var mission_name = gMissionOptions[tourId][mission_id];
		missionOptionsHTML += '<option value="'+mission_id+'">'+mission_name+'</option>'+"\n";
	}
	$mission.html(missionOptionsHTML);
}

function initializeTeam(userPlayerId, team, mvmGroupArray) {
	$('#header_lobby_id').html(team.lobby_id);
	$('#team_id').html(team.team_id);

	var groupOptionsHTML = '';
	for (var groupKey in mvmGroupArray) {
		var group = mvmGroupArray[groupKey];
		groupOptionsHTML += '<option value="' + group.id + '">' + group.name + '</option>';
	}
	$('#selectedmvmgroup').html(groupOptionsHTML);

	$('#myteam').show();
}

function createTeam() {
	var selectedTour = $('#selectedtour').val();
	var selectedMission = $('#selectedmission').val();
	var selectedRegion = $('#selectedregion').val();
	var selectedGroup = $('#selectedmvmgroup').val();
	var slotsOpen = $('#slots_available').val();

	var error = '';

	if (selectedTour === 0) {
		error = 'Tour is missing.\n';
	}
	if (selectedMission === 0) {
		error += 'Mission is missing.\n';
	}
	if (selectedRegion === 0) {
		error += 'Region is missing.\n';
	}
	if (selectedGroup === 0) {
		error += 'Group is missing.\n';
	}
	if (!isInteger(slotsOpen) || slotsOpen < 1 || slotsOpen > 5) {
		error += 'Invalid number of players needed.';
	}

	if (error) {
		alert(error);
	} else {
		$.ajax({
			url: 'mvmlobbyAPI.php',
			type: 'POST',
			data: {
				'apitype': 'updateTeam',
				'mission_id': selectedMission,
				'region_id': selectedRegion,
				'group_id': selectedGroup,
				'slots_available': slotsOpen
			}
		})
		.done(function(teamResults) {
			// Clear values
			$('#myteam').slideUp(200);
			getTeamList();
		})
		.fail(function(error) {
			alert("An error occurred creating the team."+error);
		});
	}
}

function updateTeam(slotsInput) {

	var slotsOpen = $(slotsInput).val();
	var error = '';
	
	if (!isInteger(slotsOpen) || slotsOpen < 1 || slotsOpen > 5) {
		error += 'Invalid number of players needed.';
	}

	if (error) {
		alert(error);
	} else {
		$.ajax({
			url: 'mvmlobbyAPI.php',
			type: 'POST',
			data: {
				'apitype': 'updateTeam',
				'slots_available': slotsOpen
			}
		})
		.done(function(teamResults) {
			getTeamList();
		})
		.fail(function(error) {
		});
	}
}

function deleteTeam() {
	$.ajax({
		url: 'mvmlobbyAPI.php',
		type: 'POST',
		data: {
			'apitype': 'deleteTeam'
		}
	})
	.done(function(teamResults) {
		getTeamList();
	})
	.fail(function(error) {
	});
}

function isInteger(value) { 
    return !isNaN(parseInt(value,10)) && (parseFloat(value,10) == parseInt(value,10)); 
}

</script>

<?php include('footer.php'); ?>