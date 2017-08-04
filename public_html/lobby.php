<?php
	require_once('..'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'config.php');
	require_once('header.php');
	require_once('MySQL.php');
	
	if ($user == NULL) {
		echo '&nbsp;<br/><span style="font-weight: bold; margin-left: 20px;">Please sign in through Steam first.</span>';
		return;
	}
	
	if (!$user->isAdmin()) {
		echo '&nbsp;<br/><span style="font-weight: bold; margin-left: 20px;">The lobby is not yet available.</span>';
		return;
	}
	
	$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
	$tourArray = $sqlcon->query('
			select t.id, t.short_name, t.name, t.difficulty, pt.mission_bitmask
			from tour t
			left outer join player_tour pt on pt.tour_id = t.id and pt.player_id='.$user->id.'
			order by sortorder
		');
	$missionArray = $sqlcon->query('select sortorder, tour_id, name, map_bitmask from mission order by sortorder');
	$gRegionOptions = 'var gRegionOptions = new Array;'."\n";
	$gMissionOptions = 'var gMissionOptions = new Array;'."\n";
	$gTourOptions = 'var gTourOptions = new Array;'."\n";
	
	$regionArray = array(
		'-1' => '&lt;Select a Region&gt;',
		'NA' => 'North America',
		'SA' => 'South America',
		'EU' => 'Europe',
		'AU' => 'Australia',
		'RU' => 'Russia',
		'AS' => 'Asia',
		'AF' => 'Africa'
	);
?>

	<div class="well lobby-sidebar-nav-fixed">
	
		<strong>I want to:</strong><br/>
		<input type="radio" name="jointype" value="inqueue" checked> join a team<br/>
		<input type="radio" name="jointype" value="hasteam"> find players for my team
		<div class="divider8"></div>
		
		<div class="roundedBorderLight">
		<strong>Tour:</strong> <select id="selectedtour" class="form-control shortDropdown" multiple size="5">
			<?php
				$tourMissionFilter = 'var gMissionOptions = new Array;'."\n";
				$missionHTML = '';
				foreach($tourArray as $tour) {
					$tourid = $tour['id'];
					echo '<option value="'.$tourid.'">'.$tour['short_name'].'</option>';
					$missionsPlayedBitmask = 0;
					if (isset($tour['mission_bitmask'])) {
						$missionsPlayedBitmask = intval($tour['mission_bitmask']);
					}

					// also fill out an array for JavaScript Tour/Mission options
					$tourMissionFilter .= 'gMissionOptions['.$tourid.'] = new Array;'."\n";
					foreach($missionArray as $mission) {
						if ($mission['tour_id'] == $tourid) {
							$missionBitmask = intval($mission['map_bitmask']);
							$missionCompleted = (($missionsPlayedBitmask & $missionBitmask) > 0 ? 1 : 0);
							$tourMissionFilter .= 'gMissionOptions['.$tourid.']['.$mission['sortorder'].']={"name":"'.$mission['name'].'", "bitmask":'.$missionBitmask.', "missionCompleted":'.$missionCompleted.'};'."\n";
							
							$missionHTML .= '<option tourid="'.$tourid.'" value="'.$missionBitmask.'" mission_played="'.$missionCompleted.'">'.$mission['name'].'</option>';
						}
					}

				}
			?>
		</select>
		<div class="divider8"></div>
		
		<div id="needs_mission_container" style="">
			<strong>Needs Mission</strong><br />
			<input id="missionsNotCompletedCheckbox" type="checkbox" onclick="updateMissionOptions();"></input>&nbsp;Not completed<div class="divider8"></div>
			<select id="mission" multiple class="form-control" style="font-size: 12px;" size="6">
				<?php echo $missionHTML; ?>
			</select>
			<div class="divider4"></div>
			<button type="button" class="btn bth-default btn-xs" onclick='$("#missionsNotCompletedCheckbox").prop("checked",false); $("#mission").find("option:selected").removeAttr("selected");'>Clear</button>
			<!--  <button type="button" class="btn bth-default btn-xs" onclick='$("#missionsNotCompletedCheckbox").prop("checked",false); $("#mission").find("option:selected").removeAttr("selected"); updateConditions();'>Clear</button>-->
			<?php unset($missionHTML); ?>
			
		</div>	<!-- /needs_mission_container -->
		</div>	<!-- /Tour border -->
		<div class="divider8"></div>
		
		<strong>Min. Total Tours:</strong><br/>
		<input id="mintotaltours" class="form-control shortInput" type="text" maxlength="4" value="0"></input>
		<div class="divider8"></div>
		
		<strong>Region</strong>
		<select id="regionselect">
			<?php
				foreach($regionArray as $regionKey=>$regionName) {
					echo '<option value="'.$regionKey.'">'.$regionName.'</option>';
					$gRegionOptions .= 'gRegionOptions["'.$regionKey.'"] = "'.$regionName.'";'."\n";
				}
			?>
		</select>
		<div class="divider8"></div>
		
		<button type="button" class="btn btn-success btn-sm" onclick="refreshInventories(true);" data-toggle="tooltip" data-placement="right">Join</button>
		<div class="divider8"></div>

	</div>	<!-- /well sidebar-nav-fixed -->
	

	<div class="lobby_outer_container">
		<div class="lobby_inner_container">
			<div class="well well-sm">Connection status: <span id="connection_status">Not connected</span></div>

			<div id="player_chat_container">

				<div id="player_list_container">

					<ul id="player_list">

						<li><span class="player_name">ACTION JESUS</span><span class="player_tours">305</span></li>
						<li><span class="player_name">socksweasel</span><span class="player_tours">5</span></li>

					</ul>

				</div>


			</div>
			
			<!-- chat
				http://chatjs.net/
			 -->
			
		</div> <!-- /lobby_inner_container -->
	</div> <!-- /lobby_outer_container -->

<script>

<?php
	echo $gRegionOptions;
	echo $gTourOptions;
	echo $gMissionOptions;
	echo 'var gSteamId="'.$user->steamid.'";';
	echo 'var gTotalTours='.$user->totalTours.';';
	
	echo 'var gToursCompleted = new Array;'."\n";
	foreach($user->tourArray as $tour) {
		$toursCompleted = $tour['tours_completed'];
		echo 'gToursCompleted['.$tour['id'].']='.($toursCompleted==''?0:$toursCompleted).';'."\n";
	}
?>

var gWebsocketURI = "<?php echo WEBSOCKET_URI; ?>"; 	
var gPlayerManager;
var gWebsocketManager;


function updateMissionOptions() {
	var tourIdArray = $('#selectedtour').val() || [];	// multiple tours allowed ([], [4], [1,3,5])
	var selectMissionsNotCompleted = $('#missionsNotCompletedCheckbox').is(':checked');
	var $missionContainer = $('#needs_mission_container');
	var $missionSelect = $('#mission');

	$missionSelect.children().hide();	// hide everything by default
	if (selectMissionsNotCompleted) {
		// clear 'selected' prop by default
		$missionSelect.children().prop('selected',false);
	}
	
	for (var i=0; i<tourIdArray.length; i++) {
		var tourId = tourIdArray[i];
		$missionSelect.children('[tourid='+tourId+']').show();

		if (selectMissionsNotCompleted) {
			$missionSelect.children('[tourid='+tourId+'][mission_played=0]').prop('selected',true);
		}
	}
}


function isInteger(value) { 
// 	var intRegex = /^\d+$/;
// 	if(intRegex.test(someNumber)) {}
    return !isNaN(parseInt(value,10)) && (parseFloat(value,10) == parseInt(value,10)); 
}

function getCookieValue(cookieName) {
	var nameEQ = cookieName + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') { c = c.substring(1,c.length); }
		if (c.indexOf(nameEQ) == 0) { return c.substring(nameEQ.length,c.length); }
	}
	return null;
}


function PlayerManager() {
	this.$playersTable = $('#playersTable');
	this.$playerCountElement = $('#playercount');
	this.playerCount = 0;

	this.$playerChatContainer = $('#playerChatContainer');
	this.$playerChatInput = $('#playerChatInput');
	
	this.add = function(playerData) {
		var socketid = playerData['socketid'];
		var steamid = playerData['steamid'];
		var name = playerData['name'];
		var total_tours = playerData['total_tours'];
		var region = playerData['region'];
		var html = '<tr steamid="'+steamid+'" socketid="'+socketid+'"><td><a href="search.php?steamid='+steamid+'" target=_blank>'+name+'</a></td><td class="cellalignright">'+region+'</td><td class="intcell">'+total_tours+'</td></tr>';
		this.$playersTable.find('tr:first').after(html);
		
		++this.playerCount;
		this.updateCountDisplay();
	};

	
	this.showPlayerList = function (playerList) {
		for (var key in playerList) {
			this.add(playerList[key]);
		}
	}

	
	this.remove = function(playerData) {
		var steamid = playerData['steamid'];
		var socketid = playerData['socketid'];
		this.$playersTable.find('tr[steamid="'+steamid+'"][socketid="'+socketid+'"]').remove();
		
		--this.playerCount;
		this.updateCountDisplay();
	};

	
	this.updateCountDisplay = function() {
		this.$playerCountElement.html(this.playerCount);
	}

	this.sendChatMessage = function() {
		var chatMessage = this.$playerChatInput.val();
		if (chatMessage) {
			gWebsocketManager.sendWebsocketMessage({
				'type':'chat',
				'action':'add',
				'message':chatMessage
			});
			this.$playerChatInput.val('');
		}
	}

	this.showChatMessage = function(name, msg) {
		if (name && msg) {
			this.$playerChatContainer.append('<span class="playerChatName">'+name+': </span><span class="playerChatMessage">'+msg+'</span><br/>');
			this.$playerChatContainer[0].scrollTop = this.$playerChatContainer[0].scrollHeight; // scroll to the bottom
		}
	}
}



function WebsocketManager() {
	
	//create a new WebSocket object.
	this.websocket = new WebSocket(gWebsocketURI);
	this.steamid = getCookieValue('user_steam_id');
	this.session_key = getCookieValue('session_key');
	this.PHPSESSID = getCookieValue('PHPSESSID');
	
	this.websocket.onopen = function(ev) {
		$('#connectionError').hide();
		// associates the socket to the player
		gWebsocketManager.sendWebsocketMessage({
			'type':'player',
			'action':'add'
		});
		$('#connection_status').css('color','#060').html('Connected');
	}

		
	// Data received from the socket
	this.websocket.onmessage = function(ev) {
	
		var msg = JSON.parse(ev.data); //PHP sends Json data
		console.log(msg);
		
		if ('error' in msg) {
			
			dialogAlert(msg['error']);
			
		} else if ('type' in msg && 'action' in msg) {
			
			var type = msg['type'];
			var action = msg['action'];
			
			if (type=='player') {
				
				if (action == 'connect') {
					
					gPlayerManager.add(msg['playerdata']);
					
				} else if (action == 'disconnect') {
					
					gPlayerManager.remove(msg['playerdata']);
					
				} else if (action == 'showlist') {
					
					gPlayerManager.showPlayerList(msg['playerlist']);
					
				}
				
			} else if (type == 'chat') {
	
					if (action == 'add') {
						var name = msg['name'];
						var message = msg['message'];
						gPlayerManager.showChatMessage(name, message);
					}
			}
		}
		
	};

	this.sendWebsocketMessage = function(msgObject) {

		if (this.steamid && this.session_key) {
			msgObject['steamid'] = this.steamid;
			msgObject['session_key'] = this.session_key;
			msgObject['phpsessid'] = this.PHPSESSID; 

		 	//convert and send data to server
		 	this.websocket.send(JSON.stringify(msgObject));
	 	}
	}
	
	this.websocket.onerror	= function(eventObject){
		$('#connection_status').css('color','#f00').html('Cannot connect to the lobby coordinator');
	};
	 
	this.websocket.onclose	= function(ev){
		$('#connection_status').css('color','#f00').html('Not connected');
	}; 
}


/**
 * Convert the mission bitmask into a list
 *	tourId = 0-5
 *	missiontBitmask = combined mission bitmasks
 *	delimiter = the separator between mission names
 */
function getMissionNamesFromBitmask(tourId, missionBitmask, delimiter) {
	var missionList = '';
	var currentDelimiter = '';
	for(var m in gMissionOptions[tourId]) {
		var mission = gMissionOptions[tourId][m];
		if (missionBitmask & mission['bitmask']) {
			missionList += currentDelimiter + mission.name;
			currentDelimiter = delimiter;
		}
	}
	return missionList;
}

function dialogAlert(msg) {
	alert(msg);	// TODO:  change to a dialog
}



$(document).ready(function() {
	gPlayerManager = new PlayerManager();
	gWebsocketManager = new WebsocketManager();

	window.onbeforeunload = function() {
	    this.gWebsocketManager.websocket.onclose = function () {}; // disable onclose handler first
	    this.gWebsocketManager.websocket.close();
	};

	$('#sendChat').on('click', function(e) {
		gPlayerManager.sendChatMessage();
		e.preventDefault;
		return false;
	});

	$("#playerChatInput").keypress(function (e) {
		if(e.which == 13) {
			$('#sendChat').click();
			$(this).val("");
			e.preventDefault();
		}
	});

	$('#selectedtour').on('click', function() {
		updateMissionOptions();
	});

	$('#mission').on('click', function() {
		$('#missionsNotCompletedCheckbox').prop('checked',false);
	});

	$('input[name=jointype]').change(function() {
		alert('shit changed');
	});
});

</script>

<?php include('footer.php'); ?>