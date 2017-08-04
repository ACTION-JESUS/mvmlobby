
function upsertFriend(friendSteamID, isFavorite, removeFriend, completedCallback, errorCallback) {
	$.ajax({
		url: 'mvmlobbyAPI.php',
		type: 'POST',
		data: {
			'apitype': 'upsert',
			'action':	'upsertFriend',
			'friend_steamid':	friendSteamID,
			'favorite': (isFavorite ? '1' : '0'),
			'subaction':  (removeFriend ? 'remove' : 'update_friend_status')
		}
	})
	.done(function(data) {
		if (completedCallback) {
			completedCallback(data);
		}
	})
	.fail(function(error) {
		if (errorCallback) {
			errorCallback(error);
		}
	});
}

function upsertNote(targetSteamID, noteText, is_public, completedCallback, errorCallback) {
	$.ajax({
		url: 'mvmlobbyAPI.php',
		type: 'POST',
		data: {
			'apitype': 'upsert',
			'action':	'upsertNote',
			'targetSteamID':	targetSteamID,
			'note': noteText,
			'is_public': is_public
		}
	})
	.done(function(data) {
		if (completedCallback) {
			completedCallback(data);
		}
	})
	.fail(function(error) {
		if (errorCallback) {
			errorCallback(error);
		}
	});
}

function getPlayerData(steamId, forceRefresh, onSuccessFunction, onFailFunction, onCompleteFunction) {
	$.ajax({
		type: 'GET',
		url: 'mvmlobbyAPI.php',
		data: {
			'apitype':'getPlayerData',
			'steamid':steamId,
			'forcerefresh': forceRefresh
		}
	})
	.fail(function(error) {
		if (onFailFunction) {
			onFailFunction(error);
		}
	})
	.done(function(response) {
		if (onSuccessFunction) {
			onSuccessFunction(response);
		}
	});
}

function Player(playerArray) {
	this.steamid = playerArray['steamid'];
	this.id = playerArray['id'];
	this.name = playerArray['name'];
	this.region = playerArray['region'];
	this.site_status = playerArray['site_status'];
	this.image_url_small = playerArray['image_url_small'];
	this.image_url_medium = playerArray['image_url_medium'];
	this.image_url_large = playerArray['image_url_large'];
	this.favorite = playerArray['favorite'];
	this.inventory_unavailable = playerArray['inventory_unavailable'];
	this.profile_visibility = playerArray['profile_visibility'];
	this.inventory_status = playerArray['inventory_status'];
	this.steam3id = playerArray['steam3id'];
	this.robots_killed = playerArray['robots_killed'];
	
	this.is_playing_tf2 = playerArray['is_playing_tf2'];
	this.current_mvm_mission_id = playerArray['current_mvm_mission_id'];
	this.current_mvm_mission_name = playerArray['current_mvm_mission_name'];
	this.mission_wiki_url = playerArray['mission_wiki_url'];
	this.current_game_name = playerArray['current_game_name'];
	this.current_game_ipport = playerArray['current_game_ipport'];

	// Inventory info
	this.inventory_last_updated = playerArray['inventory_last_updated'];
	this.ticketCount = playerArray['ticketCount'];
	this.totalTours = playerArray['totalTours'];
	this.tourArray = playerArray['tourArray'];
	
	// Status info
	this.last_known_status_code = playerArray['last_known_status_code'];
	this.summary_last_updated_in_minutes = playerArray['summary_last_updated_in_minutes'];
	this.inventory_last_updated_in_minutes = playerArray['inventory_last_updated_in_minutes'];
	this.summary_display_age = playerArray['summary_display_age'];
	this.inventory_display_age = playerArray['inventory_display_age'];
	this.status = playerArray['status'];
	this.status_color = playerArray['status_color'];
}

function changeDisplayValue($element, newValue, newColor) {
	var millis = 800;
	var oldValue = $element.html();
	if (oldValue != newValue) {
		$element.fadeOut(millis, 'swing', function() {
			$element.html(newValue);
			if (newColor) {
				$element.css('color', newColor);
			}
			$element.fadeIn(millis);
		});
		// $element.html(newValue);
	}
}

function HeaderView() {
	this.update = function(user) {
		changeDisplayValue($('#headerTicketsOwned'), user.ticketCount);
		changeDisplayValue($('#headerTotalTours'), user.totalTours);
	}
}

function PlayerHeaderController(steamid) {
	this.user = null;
	this.headerView = null;
	
	this.refreshData = function() {
		getPlayerData(steamid, 0, this.onFetchSuccess);
	}
	
	this.onFetchSuccess = function(jsonData) {
		if (jsonData && jsonData['player']) {
			this.user = new Player(jsonData['player']);
			this.headerView = new HeaderView();
			this.headerView.update(this.user);
		}
	}
}

function DetailView() {
	this.update = function(player) {

		var $player_attrs = $('#playerdetails [player_attr]');
		var $ticketcount = $player_attrs.find('[player_attr=ticketcount]');
		
		var robotsKilledDisplay = '?';
		if (player.robots_killed >= 0 ) {
			if (player.robots_killed == 1000000) {
				robotsKilledDisplay = '>1,000,000';
			} else {
				robotsKilledDisplay = player.robots_killed.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
			}
		}
		
		changeDisplayValue($player_attrs.filter('[player_attr=name]'), player.name);
		changeDisplayValue($player_attrs.filter('[player_attr=ticketcount]'), player.ticketCount);
		changeDisplayValue($player_attrs.filter('[player_attr=totaltours]'), player.totalTours);
		changeDisplayValue($player_attrs.filter('[player_attr=robotskilled]'), robotsKilledDisplay);

 		var $statusContainer = $player_attrs.filter('[player_attr=steamstatus]');
 		$statusContainer.off('click');
 		$statusContainer.css('color', player.status_color);
 		
 		if (player.current_mvm_mission_name) {
 			// statusHTML = '<a style="color: '+player.status_color+'" href="#" onclick="displayServerInfo(\''+player.current_game_ipport+'\',\''+player.current_mvm_mission_name+'\',\''+player.mission_wiki_url+'\'); return false;">'+player.status+'</a>';
 			changeDisplayValue($statusContainer, '<a style="color: '+player.status_color+'" href="#" onclick="return false;">'+player.status+'</a>');
 	 		$statusContainer.on('click', function() {
				displayServerInfo(player.current_game_ipport, player.current_mvm_mission_name, player.player_wiki_url);
 				return false;
 			});
 		} else {
 			changeDisplayValue($statusContainer, player.status);
 		}

 		for (var tourId in player.tourArray) {
 			var tourInfo = player.tourArray[tourId];
 			var missionsCompleted = 0;
 			var missionsCompletedBitmask = (tourInfo['mission_bitmask'] ? tourInfo['mission_bitmask'] : 0);
 			
 			// Update missions first to get the count
 			$missionList = $player_attrs.filter('[player_attr=mission][tour_id='+tourId+']');
 			$missionList.each(function() {
 				var mission_bitmask = $(this).attr('bitmask');
 				if ( (missionsCompletedBitmask & mission_bitmask) == mission_bitmask) {
 					changeDisplayValue($(this), 'Complete', '#00aa00');
 					++missionsCompleted;
 				} else {
 					changeDisplayValue($(this), 'Incomplete', '#aa0000');
 				}
 			});
 			
 			// Update the header
 			var tours_completed = (tourInfo['tours_completed'] ? tourInfo['tours_completed'] : 0);
 			var html = '<span style="font-weight: bold;">' + tours_completed + '</span> tour' + (tours_completed==1?'':'s') + ' (' + missionsCompleted + '/' + $missionList.length + ')';
 			changeDisplayValue($player_attrs.filter('[player_attr=tours_completed][tour_id='+tourId+']'), html);
 		}

 		// Update the status
 		changeDisplayValue($player_attrs.filter('[player_attr=inventory_display_age]'), player.inventory_display_age);
 		$('#mvmDataStatus').html('MvM data is current within the past 15 minutes.').css('color','#070');

	}
}

function PlayerDetailController(steamid) {
	var player = null;
	var detailView = new DetailView();
	
	this.refreshData = function() {
		getPlayerData(steamid, 0, this.onFetchSuccess);
	}
	
	this.onFetchSuccess = function(jsonData) {
		if (jsonData && jsonData['player']) {
			player = new Player(jsonData['player']);
			detailView.update(player);
		}
	}
}