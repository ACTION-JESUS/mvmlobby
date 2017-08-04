<?php
	require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'config.php');
	require_once('header.php');
	require_once('MySQL.php');

	$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
	if (isset($user) && isset($user->id)) {
		$tourArray = $sqlcon->query('
			select t.id, t.short_name, t.name, t.difficulty, pt.mission_bitmask
			from tour t
			left outer join player_tour pt on pt.tour_id = t.id and pt.player_id='.$user->id.'
			order by sortorder
		');
	} else {
		$tourArray = $sqlcon->query('
			select id, short_name, name, difficulty
			from tour
			order by sortorder
		');
	}
	$missionArray = $sqlcon->query('select sortorder, tour_id, name, map_bitmask from mission order by sortorder');
	$tourMissionFilter = '';
?>


<div id="importFriendsModal" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
				<h4 class="modal-title">Manage Friends</h4>
			</div>	<!-- /modal-header -->
			<div class="modal-body">
				<div id="importFriendsTableScroll">
					<div id="importFriendsLoadingDialog" style="height: 240px; width: 100%;">
						<img src="images/ajax-loader.gif" />
					</div>
					<table id="importFriendsTable"></table> <!-- /importFriendsTable -->
				</div>	<!-- /importFriendsTableScroll -->
			</div>	<!-- /modal-body -->
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>	<!-- /modal-footer -->
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /importFriendsModal -->

	<div class="well sidebar-nav-fixed">
		<input id="hastickets" type="checkbox" checked="true"></input> <strong>Has TOD Tickets</strong><div class="divider8"></div>
		<input id="favoritesonly" type="checkbox"></input> <strong>Favorites</strong><div class="divider8"></div>
		<input id="filter_onlineonly" type="checkbox" checked="true"></input> <strong>Online Only</strong><div class="divider8"></div>
		<input id="filter_not_playing_mvm" type="checkbox" checked="false"></input> <strong>Not playing MvM now</strong><div class="divider8"></div>
		
		<div class="roundedBorderLight">
		<strong>Tour:</strong> <select id="selectedtour" class="form-control shortDropdown">
			<?php
				echo '<option value="0" selected>&lt;select tour&gt;</option>';
				$tourMissionFilter = 'var gMissionOptions = new Array;'."\n";
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
						}
					}

				}
			?>
		</select>
		<div class="divider4"></div>
		Minimum # of tours:<br/>
		<input id="mintours" class="form-control shortInput" type="text" maxlength="4" value="1"></input>
		
		<div class="divider8"></div>
		<div id="needs_mission_container" style="display: none;">
			<strong>Needs Mission</strong><br />
			<input id="missionsNotCompletedCheckbox" type="checkbox"></input>&nbsp;Not completed<div class="divider8"></div>
			<select id="mission" multiple class="form-control" style="font-size: 12px;">
				<!-- options populated when a tour is selected -->
			</select>
			<div class="divider4"></div>
			<button type="button" class="btn bth-default btn-xs" onclick='$("#missionsNotCompletedCheckbox").prop("checked",false); $("#mission").find("option:selected").removeAttr("selected"); updateConditions();'>Clear</button>
			
		</div>	<!-- /needs_mission_container -->
		</div>	<!-- /Tour border -->
		<div class="divider8"></div>
		
		<strong>Min. Total Tours:</strong><br/>
		<input id="mintotaltours" class="form-control shortInput" type="text" maxlength="4" value="0"></input>
		<div class="divider8"></div>
		
		<button type="button" class="btn btn-success btn-sm" onclick="refreshInventories(false);" data-toggle="tooltip" data-placement="right" title="Refreshes the Steam status for everyone.  Backpack data (tour info) is only updated for players where the data age is over 15 minutes.">Refresh Status</button>
		<div class="divider8"></div>
		
		<button type="button" class="btn btn-success btn-sm" onclick="refreshInventories(true);" data-toggle="tooltip" data-placement="right" title="Refreshes the Steam status for everyone.  Backpack data (tour info) is fetched for all friends.  This is usually considerably slower than the Refresh Status button.">Refresh All</button>
		<div class="divider8"></div>

		<button id="importFriendsButton" type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#importFriendsModal">Manage Friends</button>
	</div>	<!-- /well sidebar-nav-fixed -->

	
	<div class="friendsSectionContainer">
	<div class="friendsSection">
<?php
	if ($user == null) {
?>
You must sign in first!<br/><p><p/>
Instead of spamming Mann Up invites, this screen allows you to find friends who:<br/>
<ul style="margin-left: 40px;">
	<li>are not already playing MvM</li>
	<li>are online</li>
	<li>have TOD tickets</li>
	<li>need exact missions</li>
	<li>have a minimum # of tours for the tour you want to play</li>
	<li>have a minimum total # of tours</li>
	<li>are tagged as a favorite</li>
</ul>
<p></p>
This works best using the Steam browser while creating a Mann Up team in TF2<br/>
<p></p>
To use it:<br/>
<ul style="margin-left: 40px;">
	<li>Sign in!</li>
	<li>Add friends using the "Manage Friends" button on the left (or add them while viewing their profile)</li>
	<li>Click the "Refresh Status" or "Refresh Tours" buttons on the left</li>
	<li>Use the filters on the left as needed</li>
</ul>
<p></p>

<?php
	} else {
		echo '<div id="refresh_data_message" style="display: none;" class="alert alert-info" role="alert">Refreshing data</div>';

		$results = $sqlcon->query('
			select
				f.id,
				f.name,
				f.steamid,
				f.image_url_small,
				f.total_tours,
				f.tod_tickets,
				f.current_mvm_mission_id,
				pf.favorite,
				TIMESTAMPDIFF(MINUTE,f.inventory_last_updated,now()) as inventory_last_updated_in_minutes,
				pt1.tours_completed as tour1,
				pt1.mission_bitmask as bitmask1,
				pt2.tours_completed as tour2,
				pt2.mission_bitmask as bitmask2,
				pt3.tours_completed as tour3,
				pt3.mission_bitmask as bitmask3,
				pt4.tours_completed as tour4,
				pt4.mission_bitmask as bitmask4,
				pt5.tours_completed as tour5,
				pt5.mission_bitmask as bitmask5
			from player_friend pf
				inner join player f on pf.friend_id=f.id
				left outer join player_tour pt1 on pt1.tour_id=1 and pt1.player_id=pf.friend_id
				left outer join player_tour pt2 on pt2.tour_id=2 and pt2.player_id=pf.friend_id
				left outer join player_tour pt3 on pt3.tour_id=3 and pt3.player_id=pf.friend_id
				left outer join player_tour pt4 on pt4.tour_id=4 and pt4.player_id=pf.friend_id
				left outer join player_tour pt5 on pt5.tour_id=5 and pt5.player_id=pf.friend_id
			where pf.player_id='.$user->id.'
			order by f.name');
		
		if ($sqlcon->records > 0) {
			echo '<table id="friendstable">';
			echo '<tr class="headers"><td></td><td>Friend <span id="friend_filter_count"></span></td><td>Contact</td><td>Status</td><td>Tours</td><td class="cellalignright tour1">Oil<br/>Spill</td><td class="cellalignright tour2">Steel<br/>Trap</td><td class="cellalignright tour3">Mecha<br/>Engine</td><td class="cellalignright tour4">Two<br/>Cities</td><td class="cellalignright tour5">Gear<br/>Grinder</td><td class="cellalignright">Age</td></tr>';
			foreach($results as $result) {
				$id = $result['id'];
				$steamid = $result['steamid'];
				$name = $result['name'];
				$image_url_small = $result['image_url_small'];
				$total_tours = $result['total_tours'];
				$favorite = $result['favorite'];
				$tod_tickets = $result['tod_tickets'];
				$tour1 = $result['tour1'];
				$tour2 = $result['tour2'];
				$tour3 = $result['tour3'];
				$tour4 = $result['tour4'];
				$tour5 = $result['tour5'];
				$tour1_mission_bitmask = $result['bitmask1'];
				$tour2_mission_bitmask = $result['bitmask2'];
				$tour3_mission_bitmask = $result['bitmask3'];
				$tour4_mission_bitmask = $result['bitmask4'];
				$tour5_mission_bitmask = $result['bitmask5'];
	
				if (!$total_tours) { $total_tours=0; }
				if (!$favorite) { $favorite=0; }
				if (!$tod_tickets) { $tod_tickets=0; }
				if (!$tour1) { $tour1=0; $tour1_mission_bitmask=0; }
				if (!$tour2) { $tour2=0; $tour2_mission_bitmask=0; }
				if (!$tour3) { $tour3=0; $tour3_mission_bitmask=0; }
				if (!$tour4) { $tour4=0; $tour4_mission_bitmask=0; }
				if (!$tour5) { $tour5=0; $tour5_mission_bitmask=0; }
				
				$current_mvm_mission_id = $result['current_mvm_mission_id'];
				$mvm_mission_attr = (empty($current_mvm_mission_id) ? ' current_mvm_mission_id="-1"' : ' current_mvm_mission_id="'.$current_mvm_mission_id.'"');
				
				$ageString = getDateAge($result['inventory_last_updated_in_minutes']);
	
				echo '<tr player_id="'.$id.'" steamid="'.$steamid.'" total_tours="'.$total_tours.'" favorite="'.$favorite.'" statuscode="-1" tod_tickets="'.$tod_tickets.'"'.$mvm_mission_attr.' tour1="'.$tour1.'" tour2="'.$tour2.'" tour3="'.$tour3.'" tour4="'.$tour4.'" tour5="'.$tour5.'" mission_bitmask1="'.$tour1_mission_bitmask.'" mission_bitmask2="'.$tour2_mission_bitmask.'" mission_bitmask3="'.$tour3_mission_bitmask.'" mission_bitmask4="'.$tour4_mission_bitmask.'" mission_bitmask5="'.$tour5_mission_bitmask.'">';
				echo '<td class="favoritetd"><img src="'.($favorite?'images/favorite_on.png':'images/favorite_unchecked.png').'" onclick="toggleFavorite('."'".$steamid."'".');"></img></td><td><a href="profile/'.$steamid.'"><img src="'.$image_url_small.'"></img></a>&nbsp;&nbsp;<a class="name" href="profile/'.$steamid.'">'.$name.'</a><span class="noticketmessage" style="color: #f00; display: '.($tod_tickets>0?'none':'inline-block').'">&nbsp;[no tickets]</span></td><td><a href="steam://friends/message/'.$steamid.'">Chat</a></td><td class="onlinestatus"></td><td class="cellalignright total_tours">'.$total_tours.'</td><td class="cellalignright tour1">'.$tour1.'</td><td class="cellalignright tour2">'.$tour2.'</td><td class="cellalignright tour3">'.$tour3.'</td><td class="cellalignright tour4">'.$tour4.'</td><td class="cellalignright tour5">'.$tour5.'</td><td class="cellalignright inventory_last_updated_in_minutes">'.$ageString.'</td></tr>';
			}
			echo '</table>';
		} else {
			echo '<span style="font-weight: 500; font-size: 14px;"><p>You have not added any MvM friends yet. To add friends, you can:<br/>1) Select "Manage Friends" on the left<br/>2) View player details and select "Add MvM Lobby Friend" or click the favorite button</span>';
		}
	}
?>
</div>	<!-- /friendsSection -->
</div>	<!-- /friendsSectionContainer -->

<?php include('server_details.php'); ?>

<script>

	<?php echo $tourMissionFilter; ?>

	/**
		forceRefresh=0 - retrieves summaries in 1 Steam API call, updates the database, loops through each player,
							and only refreshes inventories older than INVENTORY_REFRESH_MINUTES
		forceRefresh=1 - loops through each player and always refreshes the summary and inventory regardless of cache age  
	*/
	function refreshInventories(forceRefresh) {
		var $refreshMsg = $('#refresh_data_message');
		var $content = $('#refreshDialogContent');
		var $refreshButton = $('#refreshButton');
		var $forceRefreshButton = $('#forceRefreshButton');

		$refreshButton.hide(150);
		$forceRefreshButton.hide(150);
		$refreshMsg.slideDown();
		$('.onlinestatus').each(function() {
			$(this).html('pending...');
			$(this).css('color', '#000');
		});
		var $trlist = $('#friendstable').find('tr');
		var playerCount = $trlist.length-1; // skip the header row
		var playersRemaining = $trlist.length-1; // skip the header row
		var counter=1;

		// First refresh all friends status from steam in bulk
		$.ajax({
			type: 'POST',
			url: 'mvmlobbyAPI.php',
			data: {
				'apitype':'updateAllFriendsStatus'
			}
		})
		.done(function(refreshStatusResponse) {

			// Now refresh backpacks if necessary
			$trlist.each(function() {
				var $tr = $(this);
				var steamid = $tr.attr('steamid');	// again, header row has no steamid
				if (steamid) {
					var rowIndex = counter;
					++counter;
					$.ajax({
						type: 'GET',
						url: 'mvmlobbyAPI.php',
						data: {
							'apitype':'getPlayerData',
							'steamid':steamid,
							'forcerefresh': forceRefresh
						}
					})
					.fail(function(error) {
						--playersRemaining;
						console.log("------ FAILED");
					})
					.done(function(response) {

						var player = response['player'];
						var total_tours = player['totalTours'];
						var tod_tickets = player['ticketCount'];
						$tr.attr('total_tours', total_tours);
						$tr.attr('tod_tickets', tod_tickets);
						$tr.find('.total_tours').html(total_tours);
						if (tod_tickets==0) {
							$tr.find('.noticketmessage').show();
						} else {
							$tr.find('.noticketmessage').hide();
						}
						$tr.find('.name').html(player['name']);

						var missionId = player['current_mvm_mission_id'];
						$tr.attr('current_mvm_mission_id', (missionId ? missionId : '-1'));

						var $statustd = $tr.find('.onlinestatus');
						var statusText = player['status'];
						$statustd.css('color', player['status_color']);
 						if (missionId > 0) {
 	 						$statustd.html('<a style="color: '+player['status_color']+'" href="#" onclick="displayServerInfo('+"'"+player['current_game_ipport']+"','"+player['current_mvm_mission_name']+"','"+player['mission_wiki_url']+"'"+'); return false;">'+statusText+'</a>');
						} else {
 							$statustd.html(statusText);
						}
						
						$tr.attr('statuscode', player['last_known_status_code']);


						$tr.find('.inventory_last_updated_in_minutes').html(player['inventory_display_age']);
						
						if ('tourArray' in player) {
							var tours = player['tourArray'];
							var tc1 = tours[1]['tours_completed'];
							var tc2 = tours[2]['tours_completed'];
							var tc3 = tours[3]['tours_completed'];
							var tc4 = tours[4]['tours_completed'];
							var tc5 = tours[5]['tours_completed'];
							tc1 = (tc1==null ? 0 : tc1);
							tc2 = (tc2==null ? 0 : tc2);
							tc3 = (tc3==null ? 0 : tc3);
							tc4 = (tc4==null ? 0 : tc4);
							tc5 = (tc5==null ? 0 : tc5);
							$tr.attr('tour1', tc1);
							$tr.attr('tour2', tc2);
							$tr.attr('tour3', tc3);
							$tr.attr('tour4', tc4);
							$tr.attr('tour5', tc5);
							$tr.find('.tour1').html(tc1);
							$tr.find('.tour2').html(tc2);
							$tr.find('.tour3').html(tc3);
							$tr.find('.tour4').html(tc4);
							$tr.find('.tour5').html(tc5);
	
							var mb1 = tours[1]['mission_bitmask'];
							var mb2 = tours[2]['mission_bitmask'];
							var mb3 = tours[3]['mission_bitmask'];
							var mb4 = tours[4]['mission_bitmask'];
							var mb5 = tours[5]['mission_bitmask'];
							$tr.attr('mission_bitmask1', mb1);
							$tr.attr('mission_bitmask2', mb2);
							$tr.attr('mission_bitmask3', mb3);
							$tr.attr('mission_bitmask4', mb4);
							$tr.attr('mission_bitmask5', mb5);
						}

						updateConditions($tr);
						
						$refreshMsg.html('Refreshing player ' + (playerCount-playersRemaining) + ' of ' + playerCount);
						--playersRemaining;
						if (playersRemaining == 0) {
							$refreshMsg.slideUp();
							$refreshButton.show(150);
							$forceRefreshButton.show(150);
							updateConditions(undefined);
						}
					});
				}
			});
		});
		}


	function toggleFavorite(steamid) {
		var $tr = $('#friendstable').find('tr[steamid='+steamid.toString()+']');
		var favorite = parseInt($tr.attr('favorite'));
		var updatedFavoriteState = (favorite?0:1);
		$tr.attr('favorite', updatedFavoriteState);
		var $img = $tr.find('.favoritetd').find('img');
		$img.off('click');
		$img.attr('src', (updatedFavoriteState ? 'images/favorite_on.png' : 'images/favorite_unchecked.png'));
		$img.on('click', function() {
			toggleFavorite(steamid);
		});

		upsertFriend(steamid, updatedFavoriteState, false);
	}
	

	function updateMissionOptions() {
		var tourId = $('#selectedtour').val();
		var selectMissionsNotCompleted = $('#missionsNotCompletedCheckbox').is(':checked');
		var $missionContainer = $('#needs_mission_container');
		var $mission = $('#mission');
		if (tourId == 0) {
			$missionContainer.slideUp();
		} else {
			var missionHTML = '';
			for(var m in gMissionOptions[tourId]) {
				var mission = gMissionOptions[tourId][m];
				var missionPlayed = mission.mission_played;
				var selectedText = '';

 				if (selectMissionsNotCompleted && !mission.missionCompleted) {
 					selectedText = ' selected';
 				} 
				missionHTML += '<option value="'+mission.bitmask+'" mission_played="'+mission.missionCompleted+'"'+selectedText+'>'+mission.name+'</option>'+"\n";
			}
			$mission.html(missionHTML);
			$mission.attr('size', gMissionOptions[tourId].length -1);
			$missionContainer.slideDown();
			updateConditions();	// refresh the friends filters
		}
	}


	/**
		Shows/hides players based on the selected filters

		$onlyThisTR = <friends table TR> - only changes conditions for this TR

		if $onlyThisTR is undefined, update conditions for all friends table TR's
	*/
	function updateConditions($onlyThisTR) {
		var onlineonly = ($('#filter_onlineonly').is(':checked'))?1:0;
		var hastickets = ($('#hastickets').is(':checked'))?1:0;
		var favoritesonly = ($('#favoritesonly').is(':checked'))?1:0;
		var selectedtour = $('#selectedtour').val();
		var mintours = parseInt($('#mintours').val());
		var notPlayingMVM = ($('#filter_not_playing_mvm').is(':checked'))?1:0;
		var mintotaltours = $('#mintotaltours').val();
		
		var bitmask = 0;
		if (selectedtour > 0) {
			var mission = $('#mission').val();
			for (var m in mission) {
				bitmask += parseInt(mission[m]);
			}
		}

		var $friendsTable = $('#friendstable');
		for (var idx=1; idx<=<?php echo count($tourArray); ?>; idx++) {
			var hideClass = "hideTour"+idx;
			if (selectedtour == 0 || selectedtour == idx) {
				$friendsTable.removeClass(hideClass);
			} else {
				$friendsTable.addClass(hideClass);
			}
		}

		// Save the settings in a cookie
		var cookieValue = new Object();
		cookieValue.onlineonly = onlineonly;
		cookieValue.hastickets = hastickets;
		cookieValue.favoritesonly = favoritesonly;
		cookieValue.selectedtour = selectedtour;
		cookieValue.mintours = mintours;
		cookieValue.notPlayingMVM = notPlayingMVM;
		cookieValue.bitmask = bitmask;
		cookieValue.mintotaltours = mintotaltours;
		var date = new Date();
 		date.setTime(date.getTime()+(15552000000));	// 6 months = 6m*30d*24h*60m*60s*1000ms
 		var expires = "; expires="+date.toGMTString();
		document.cookie = "FriendsSettings="+JSON.stringify(cookieValue)+expires+"; path=/";
		

		var updateConditionsForTR = function($tr) {
			if ($tr.attr('steamid')) {
				var show = true;
				var total_tours = parseInt($tr.attr('total_tours'));
				var tod_tickets = parseInt($tr.attr('tod_tickets'));
				var favorite = parseInt($tr.attr('favorite'));

				if (onlineonly) {
					var onlinestatus = $tr.attr('statuscode');
					if (onlinestatus==0) {
						show = false;
					}
				}

				if (notPlayingMVM) {
					if ($tr.attr('current_mvm_mission_id') > 0) {
						show = false;
					}
				}

				if (favoritesonly==1 && favorite==0) { show = false; }
				if (hastickets==1 && tod_tickets==0) { show = false; }

				if (total_tours < mintotaltours) { show = false; }
				
				if (selectedtour > 0) {
					var tours_completed = parseInt($tr.attr('tour'+selectedtour.toString()));
					if (tours_completed < mintours) { show = false; }
				}

				if (bitmask) {
					var missionBitmask = parseInt($tr.attr('mission_bitmask'+selectedtour.toString()));
					if ((missionBitmask & bitmask) > 0) {
						show = false;
					}
				}

				if (show) {
					$tr.show();
				} else {
					$tr.hide();
				}
			}
		}

		if ($onlyThisTR) {
			updateConditionsForTR($onlyThisTR);
		} else {
			var $friendsTable = $('#friendstable');
			$('#friendstable').find('tr').each(function() {
				updateConditionsForTR($(this));
			});
		}

		var friendsCount = $('#friendstable tr').length - 1;	// - the header TR
		var displayedRowCount = friendsCount - $('#friendstable tr:hidden').length;
		$('#friend_filter_count').html("(" + displayedRowCount + " / " + friendsCount + ")");
	}

	
	function startSteamFriendsImport() {
		var $importFriendsLoadingDialog = $('#importFriendsLoadingDialog');
		var $importFriendsTable = $('#importFriendsTable');
		$importFriendsLoadingDialog.show();
		$importFriendsTable.hide();

		$.ajax({
			type: 'POST',
			url: 'mvmlobbyAPI.php',
			data: {
				'apitype':'getSteamFriendsImportList'
			}
		})
		.done(function(newFriendsArray) {

			var $importFriendsTable = $('#importFriendsTable');

			$importFriendsTable.find('a').off('click');
			$importFriendsTable.empty();
			
			var html = '<tr><th>Name</th><th>Steam Friend</th><th>Tours</th><th>Last Played (days)</th><th>MvM Lobby</th></tr>';
			for (var i=0; i<newFriendsArray.length; i++) {
				var friend = newFriendsArray[i];
				var steamid = friend['steamid'];
				var name = friend['name'];
				var image_url = decodeURIComponent(friend['image_url']);
				var is_steam_friend = friend['is_steam_friend'];
				var is_mvmlobby_friend = friend['is_mvmlobby_friend'];

				var isInDatabase = false;
				var total_tours = '';
				var last_played = '';
				if ('total_tours' in friend) {
					isInDatabase = true;
					total_tours = friend['total_tours'];
					last_played = (friend['last_played'] == null ? '' : friend['last_played']);
				}
				
				html += '<tr>';
				
				html += '<td><a class=".profileLink" href="http://steamcommunity.com/profiles/'+steamid+'" target=_blank><img src="'+image_url+'"/></a>';
				if (isInDatabase) {
					html += '<a href="<?php echo HTTP_ROOT; ?>/profile/'+steamid+'" target=_blank>'+name+'</a>';
				} else {
					html += name;
				}
				html += '</td>';

				html += '<td>'+is_steam_friend+'</td>';
				html += '<td>'+total_tours+'</td>';
				html += '<td>'+last_played+'</td>';
				var buttonActionClass = '';
				var buttonStyleClass = '';
				var buttonText = '';
				if (is_mvmlobby_friend == "Y") {
					buttonActionClass = 'removeFriend';
					buttonStyleClass = 'btn-danger';
					buttonText = 'Remove';
				} else {
					buttonActionClass = 'addFriend';
					buttonStyleClass = 'btn-info';
					buttonText = 'Add Friend';
								}
				html += '<td style="vertical-align: middle; text-align: center;"><button type="button" class="'+buttonActionClass+' btn '+buttonStyleClass+' btn-xs" steamid="'+steamid+'">'+buttonText+'</button><img style="display:none;" src="images/ajax-loader.gif"></td>';
				html += '</tr>';
							}
			$importFriendsTable.html(html);

			$importFriendsTable.find('button[steamid]').on('click', function(e) {
				manageFriendsAction($(this));
				return false;
			});

			$importFriendsLoadingDialog.hide();
			$importFriendsTable.show();
				

		});
	}

	function manageFriendsAction($button) {
		var $parentTD = $button.parent();
		var $loading = $parentTD.find('img');
		var isAddFriend = $button.hasClass('addFriend');
		var friendSteamId = $button.attr('steamid');
		
		$button.hide();
		$loading.show();

		var actionInfo;
		if (isAddFriend) {
			actionInfo = {
				"removeParam" : false,
				"actionClassCurrent" : "addFriend",
				"actionClassNew" : "removeFriend",
				"buttonStyleCurrent" : "btn-info",
				"buttonStyleNew" : "btn-danger",
				"newText" : "Remove"
			}
		} else {
			actionInfo = {
				"removeParam" : true,
				"actionClassCurrent" : "removeFriend",
				"actionClassNew" : "addFriend",
				"buttonStyleCurrent" : "btn-danger",
				"buttonStyleNew" : "btn-info",
				"newText" : "Add Friend"
			}
		}

		upsertFriend(friendSteamId, false, actionInfo.removeParam, function(data) {
			$button.removeClass(actionInfo.actionClassCurrent);
			$button.removeClass(actionInfo.buttonStyleCurrent);
			$button.addClass(actionInfo.buttonStyleNew);
			$button.addClass(actionInfo.actionClassNew);
			$button.html(actionInfo.newText);
			$button.off('click');
			$button.on('click', function() {
				manageFriendsAction($(this));
				return false;
			});

			$loading.hide();
			$button.show();
		}, function(error) {});

	}

	function restoreSettings() {
		var value = "; " + document.cookie;
		var parts = value.split("; FriendsSettings=");
		if (parts.length == 2) {
			var jsonSettings = parts.pop().split(";").shift();
			var s = JSON.parse(jsonSettings);
			$('#filter_onlineonly').prop('checked', s.onlineonly);
			$('#hastickets').prop('checked', s.hastickets);
			$('#favoritesonly').prop('checked', s.favoritesonly);
			$('#filter_not_playing_mvm').prop('checked', s.notPlayingMVM);
			$('#selectedtour').val(s.selectedtour);
			$('#mintours').val(s.mintours);
			$('#mintotaltours').val(s.mintotaltours);
			// 			also s.bitmask, maybe add later
		}

		updateMissionOptions();
	}


	$(document).ready(function() {
		restoreSettings();
		updateConditions(undefined);
		
		$('#importFriendsButton').on('click', function() {
			startSteamFriendsImport();
		});
		
		$('#importFriendsModal').on('hidden.bs.modal', function(e) {
 			location.reload(true);
 			return false;
		});

		$('#missionsNotCompletedCheckbox').on('click', function() {
			if ($(this).is(':checked')) {
				updateMissionOptions();
			}
		});

		$('#isonline').change(function() {updateConditions(); });
		$('#hastickets').change(function() {updateConditions(); });
		$('#favoritesonly').change(function() {updateConditions(); });
		$('#selectedtour').change(function() {updateMissionOptions(); updateConditions(); });
		$('#mintours').change(function() {updateConditions(); });
		$('#mission').change(function() {updateConditions(); });
		$('#filter_onlineonly').change(function() {updateConditions(); });
		$('#filter_not_playing_mvm').change(function() {updateConditions(); });
		$('#mintotaltours').change(function() {updateConditions(); });

		$('#mintours').on('input', function() {updateConditions(); });		// event triggered for each keystroke
		$('#mintotaltours').on('input', function() {updateConditions(); });
		// refreshInventories();	/* do this automatically??? */
	});
	
</script>
<?php include('footer.php'); ?>
