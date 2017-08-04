var gPlayerManager;

$(document).ready(function(){
	//create a new WebSocket object.
	websocket = new WebSocket(gWebsocketURI); 
	
	websocket.onopen = function(ev) {
		// associates the socket to the player
		sendWebsocketMessage({
			'type':'player',
			'action':'add'
		});
	}

	gPlayerManager = new PlayerManager();


	// Data received from the socket
	websocket.onmessage = function(ev) {

		var msg = JSON.parse(ev.data); //PHP sends Json data
		console.log(msg);
		
		if ('error' in msg) {
			
			$('#createTeamDialog').hide();
			alert(msg['error']);
			
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
			} else if (type == 'team') {
				
				$('#createTeamDialog').hide(200);
				
			} else if (type == 'chat') {

// 					if (action == 'add') {
// 						var name = msg['name'];
// 						var message = msg['message'];
// 						gPlayerManager.showChatMessage(name, message);
// 					}
			}
		}
		
	};
	
	websocket.onerror	= function(ev){ console.log("Websocket error: " + ev); }; 
	//websocket.onclose 	= function(ev){$('#message_box').append("<div class=\"system_msg\">Connection Closed</div>");};

	window.onbeforeunload = function() {
	    websocket.onclose = function () {}; // disable onclose handler first
	    websocket.close();
	};

	$('#createTeamButton').on('click', function() {
		$('#createTeamDialog').show(200);
		//$('#createTeamDialogContent').html('stuff goes here');
	});

	$('#createTeamSubmitButton').on('click', function() {
		createTeam();
	});
	$('#createTeamCloseButton').on('click', function() {
		$('#createTeamDialog').hide(200);
	});

	$('#teamdialog_selectedtour').change(function() { updateMissionOptions(); });

	$('#sendChat').on('click', function() {
		gPlayerManager.sendChatMessage();
	});
	
});

function sendWebsocketMessage(msgObject) {
	var steamid = getCookieValue('user_steam_id');
	var session_key = getCookieValue('session_key');
	var PHPSESSID = getCookieValue('PHPSESSID');

	if (steamid && session_key) {
		msgObject['steamid'] = steamid;
		msgObject['session_key'] = session_key;
		msgObject['phpsessid'] = PHPSESSID; 

	 	//convert and send data to server
	 	websocket.send(JSON.stringify(msgObject));
 	}
}

function updateMissionOptions() {
	var tour = $('#teamdialog_selectedtour').val();
	var $mission = $('#teamdialog_mission');
	if (tour == -1) {
		$mission.hide();		// 2014-02-15 changed from remove... is this right???
	} else {
		var missionHTML = '';
		for(var m in gMissionOptions[tour]) {
			var mission = gMissionOptions[tour][m];
			missionHTML += '<option value="'+mission.bitmask+'">'+mission.name+'</option>'+"\n";
		}
		$mission.html(missionHTML);
		$mission.show();
	}
}

function createTeam() {
	var missionBitmask = 0;
	var tourId = $('#teamdialog_selectedtour').val();
	var minToursForTour = $('#teamdialog_pertourmintours').val();
	var minTotalTours = $('#teamdialog_mintotaltours').val();
	var region = $('#teamdialog_region').val();
	var notes = $('#teamdialog_notes').val();

	if (tourId == -1) {
		alert('Please select a tour.');
		return;
	}

	if (!isInteger(minToursForTour)) {
		alert('Please enter a valid number for the minimum number of completed tours for the selected tour.');
		return;
	}

	if (!isInteger(minTotalTours)) {
		alert('Please enter a valid number for the minimum total tours completed.');
		return;
	}

	if (region == -1) {
		alert('Please select a region.');
		return;
	}

	var missionValues = $('#teamdialog_mission').val();
	for (var m in missionValues) {
		missionBitmask += parseInt(missionValues[m]);
	}

	if (missionBitmask == 0) {
		alert('Please select a mission.');
		return;
	}


	sendWebsocketMessage({
		'type':'team',
		'action':'create',
		'tourId':tourId,
		'missionBitmask':missionBitmask,
		'minToursForTour':minToursForTour,
		'minTotalTours':minTotalTours,
		'region':region
	});
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
		var steamid = playerData['steamid'];
		var name = playerData['name'];
		var total_tours = playerData['total_tours'];
		var region = playerData['region'];
		var html = '<tr steamid="'+steamid+'"><td><a href="search.php?steamid='+steamid+'" target=_blank>'+name+'</a></td><td class="cellalignright">'+region+'</td><td class="intcell">'+total_tours+'</td></tr>';
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
		this.$playersTable.find('tr[steamid="'+steamid+'"]').remove();
		
		--this.playerCount;
		this.updateCountDisplay();
	};

	
	this.updateCountDisplay = function() {
		this.$playerCountElement.html(this.playerCount);
	}

// 	this.sendChatMessage() {
// 		var msg = $playerChatInput.val();
// 		if (msg) {
// 			sendWebsocketMessage({
// 				'type':'chat',
// 				'action':'add',
// 				'message':msg
// 			});
// 		}
// 	}

// 	this.showChatMessage(name, msg) {
// 		if (name && msg) {
// 			this.$playerChatContainer.after('<span>'+name+'</span><span>'+msg+'</span>');
// 		}
// 	}
}
