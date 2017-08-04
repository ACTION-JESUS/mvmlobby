<?php
	require_once('header.php');
	require_once('common.php');
?>

<div class="content">


<?php
	$profileIDInput = '';
	if (isset($_REQUEST['profile'])) {
		$profileIDInput = $_REQUEST['profile'];
	}
	
	// in case something other than the steam64ID is passed in the URL, this will resolve it
	$steamid = null;
	$steamIDArray = getSteamIDFromInput($profileIDInput);
	if (!empty($steamIDArray)) {		// for profile requests only display the first match (should only be 1 match anyway)
		$steamid = $steamIDArray[0];
	}
?>

<?php  if (empty($steamid)) : ?>
	<form id="steamidinputform" role="form" action="search.php">
		<div class="form-inline">
			<label for="steaminput">Enter any text containing any steam ID's, URL, or vanity name:</label>
			<div class="form-horizontal input-append" style="height: 100%; vertical-align: top;">
				<div class="btn-group">
					<input id="steaminput" class="form-control" type="text" style="width: 380px;"></input>
					<span id="searchclear" class="glyphicon glyphicon-remove-circle"></span>
				</div> <!-- btn-group -->
				<button type="submit" class="btn btn-default">Search</button>
				<img src="images/ajax-loader.gif" id="loading_image" style="display: none;" />
			</div>
		</div>	<!-- form-group -->
	</form>
	<div style="height: 10px;"></div>
	To search by name:<br/>
		1) Open the <a href="http://steamcommunity.com/search/users/" target=_blank>Steam search page</a><br/>
		2) Copy the URL of the person you are looking for into the search box above.<br/>
	<div id="error_message" class="alert alert-danger" role="alert" style="margin-top: 4px; display: none;">Player not found</div>
	<div style="height: 12px;"></div>
<?php endif; ?>

<?php
	if (!empty($steamid)) {
		$_REQUEST['steamid'] = $steamid;	// change to the steam 64 id if necessary
		include('get_player_details.php');
	}
?>

</div> <!-- /content -->

<div id="searchResultsContainer" class="panel panel-default" style="padding: 20px; display: none;">
	<div class="panel-heading">Results</div>
	<div class="panel-body">
		<div id="searchResultsListGroup" class="list-group">
		</div> <!-- /searchResultsListGroup -->
	</div> <!-- /panel-body -->
</div> <!-- panel-heading -->

<form id="hiddenDataForm" style="display: none;">
	<input text="hidden" id="resultsStorage" value="" />
</form>

<script>
	function fetchData() {
		var $error = $('#error_message');
		$error.hide();
		var steaminput = $('#steaminput').val();
		$('#loading_image').show();
		$.ajax({
			type: 'POST',
			url: 'mvmlobbyAPI.php',
			data: {
				'apitype':'getSteam64IDArray',
				'steaminput': steaminput
			}
		})
		.done(function(jsonData) {
			if (jsonData) {
				if ('steam64IDArray' in jsonData) {
					var $searchResultsListGroup = $('#searchResultsListGroup');
					$searchResultsListGroup.empty();
					$('#searchResultsContainer').slideDown();
					var steamID64Array = jsonData.steam64IDArray;

					// First display the list of steam id's
					for (var key in steamID64Array) {
						var steamid = steamID64Array[key];
						$searchResultsListGroup.append('<div steamid="'+steamid+'"><img src="images/ajax-loader.gif" />&nbsp;<a href="http://steamcommunity.com/profiles/'+steamid+'" target=_blank>'+steamid+'</a><hr/></div>');
					}

					// Now refresh the data
					for (var key in steamID64Array) {
						var steamid = steamID64Array[key];
						getPlayerData(steamid, 0, function(playerArray) {
							// success
							
							var player = playerArray['player'];

							var html = '<a class="center-vertical" href="profile/'+player['steamid']+'"><img src="'+player['image_url_small']+'"/></a>';
							html += '<div class="center-vertical" style="padding-left: 8px;"><a href="profile/'+player['steamid']+'">'+player['name']+'</a><br/>';
							html += 'Tickets: <span id="headerTicketsOwned" style="font-weight: bold">'+player['ticketCount']+'</span>&nbsp;&nbsp;&nbsp;&nbsp;';
							html += 'Tours: <span id="headerTotalTours" style="font-weight: bold">'+player['totalTours']+'</span><br/></div><hr/></div>';
							
							$searchResultsListGroup.find('div[steamid="'+player['steamid']+'"]').html(html);

							$('#resultsStorage').val($searchResultsListGroup.html());	// due to async ajax doing this after every call
														
						}, function(error) {
							// failed
						});
					}
				} else {
					$error.show();
				}
			}
		})
		.complete(function() {
			$('#loading_image').hide();
		});
	}

	function updatePrivateNote() {
		// Copy the existing private note to the modal dialog
 		$('#editPrivateNoteButton').click(function() {
 			$('#updated_private_comment').val($('#private_comment').html());
 		});

		// Copy the updated private note back to the player profile
 		$('#privateNoteSaveButton').click(function() {
 	 		var note = $('#updated_private_comment').val();
 			$('#private_comment').html(note);
 			upsertNote(profileSteamID, note, 0, function(response) {
 	 			$('#private_comment').html(response.note);	// display the cleaned data from the server
 			}); 
 		});
	}

	function updatePublicNote() {
		// Copy the existing private note to the modal dialog
 		$('#editPublicNoteButton').click(function() {
 			$('#updated_public_note').val($('#myPublicComment').html());
 		});

 		$('#deletePublicNoteButton').click(function() {
	 		$('#myPublicComment').html("");
 			$('#myPublicCommentContainer').hide();
  			upsertNote(profileSteamID, '', 1, function(response) {
  	 			$('#myPublicCommentContainer').hide();
  	 			$('#newPublicCommentContainer').show();
  			});
		});

		// Copy the updated private note back to the player profile
  		$('#publicNoteSaveButton').click(function() {
  	 		var note = $('#updated_public_note').val();
  	 		if (note) {
	  			$('#myPublicComment').html(note);
	  			upsertNote(profileSteamID, note, 1, function(response) {
	  	 			$('#myPublicComment').html(response.note);	// display the cleaned data from the server
	  	 			if(response.note) {
	  	  	 			$('#myPublicCommentContainer').show();
	  	  	 			$('#newPublicCommentContainer').hide();
					} else {
						$('#myPublicCommentContainer').hide();
		 				$('#newPublicCommentContainer').show();
					}
	  			});
  	 		}
  		});

  		$('#newPublicCommentAddButton').click(function() {
  	  		var note = $('#newPublicComment').val();
	 		$('#myPublicComment').html(note);
	 		
  	  		upsertNote(profileSteamID, note, 1, function(response) {
  	 			$('#myPublicComment').html(response.note);	// display the cleaned data from the server
  	 			if(response.note) {
  	  	 			$('#myPublicCommentContainer').show();
  	  	 			$('#newPublicCommentContainer').hide();
				} else {
					$('#myPublicCommentContainer').hide();
	 				$('#newPublicCommentContainer').show();
				}
  			});
   		});
	}

	
	function removeFriendLinks() {
		$('.isfavorite').on('click', function() {
			updateFriendStatus();
		});
		$('#add_or_remove_friend_link').on('click', function() {
			updateFriendStatus();
			e.preventDefault();
		});
	}


	function addFriendLinks() {
		$('.isfavorite').on('click', function(e) {
			updateFriendStatus(false, true);
			e.preventDefault();
			e.stopPropagation();
		});
		var $friendLink = $('#add_or_remove_friend_link');
			
		$('#add_or_remove_friend_link').on('click', function(e) {
			updateFriendStatus(true, false);
			e.preventDefault();
			e.stopPropagation();
		});
	}


	
	function updateFriendStatus(toggleFriend, toggleFavorite) {
		var $player = $('.player_details_container');
		var steamid = $player.attr('steamid');
		var isFriend = ($player.attr('isfriend')=='1'?true:false);
		var isFavorite = ($player.attr('isfavorite')=='1'?true:false);
		var removeFriend = false;

		if (toggleFriend) {
			if (isFriend) {
				removeFriend = true;
				isFriend = false;
				isFavorite = false;
			} else {
				isFriend = true;
				isFavorite = false;
			}
		} else if (toggleFavorite) {
			isFavorite = !isFavorite;
			if (isFavorite) {
				isFriend = true;
			}
		}

		// Change the UI first
		$player.attr('isfriend', isFriend?'1':'0');
		$player.attr('isfavorite', isFavorite?'1':'0');
		$('.isfavorite').attr('src', (isFavorite?'images/favorite_on.png':'images/favorite_unchecked.png'));
		$('#add_or_remove_friend_link').html((isFriend?'Remove':'Add') + ' as <?php echo SITE_NAME; ?> friend');
		
		// Update the database
		upsertFriend(steamid, isFavorite, removeFriend); 
	}

	// global variables
	var profileSteamID = '<?php echo $steamid; ?>';

	$(document).ready(function() {
		$('#steamidinputform').submit(function(event) {
			fetchData();
			event.preventDefault();
		});

		$("#searchclear").click(function(){
		    $("#steaminput").val('');
		});

		addFriendLinks();
		updatePrivateNote();
		updatePublicNote();
	});

	$(window).load(function() {
		var html = $('#resultsStorage').val();
		if (html) {
			var $searchResultsListGroup = $('#searchResultsListGroup');
			$searchResultsListGroup.empty();
			$('#searchResultsContainer').show();
			$searchResultsListGroup.html( html );
			// $('#resultsStorage').val($searchResultsListGroup.html());	// due to async ajax doing this after every call
		}
	
	});
</script>

<?php include('footer.php'); ?>
