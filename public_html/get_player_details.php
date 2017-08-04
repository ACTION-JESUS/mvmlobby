<?php
	if (session_id()=='' || !isset($_SESSION)) {
		@session_start();
	}

	require_once('common.php');
	require_once('Player.php');

	if (!isset($_REQUEST['steamid'])) {
		die("steamid is a required field");
	}
	
	$forceRefresh = Player::REFRESH_FORCE_OFF;
	if (isset($_REQUEST['refresh']) && $_REQUEST['refresh'] == '1') {
		$forceRefresh = Player::REFRESH_FORCE_ON;
	}

	$steamid = trim($_REQUEST['steamid']);
	$player = new Player($steamid,$forceRefresh);
	$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
	$isFavorite = null;
	$isFriend = FALSE;
	$isLoggedInUser = FALSE;

	if ($player == null || $player->id == null) {
		echo '<div id="error_message" class="alert alert-danger" role="alert" style="margin-top: 4px;">Player not found</div>';
		exit();
	}
	
	
	$privateComment = '';
	
	// get the friend/favorite status
	if (isset($_SESSION) && isset($_SESSION['steamid'])) {
		$sessionSteamId = $_SESSION['steamid'];
		if ($sessionSteamId == $steamid) {
			$isLoggedInUser = TRUE;
		}
		
		$result = $sqlcon->one('select pf.favorite from player p inner join player_friend pf on pf.player_id=p.id and pf.friend_id='.$player->id.' where p.steamid="'.$_SESSION['steamid'].'"');
		if ($sqlcon->records==1) {
			$isFavorite = $result['favorite'];
			$isFriend = TRUE;
		}

		if (!$isLoggedInUser) {
			$result = $sqlcon->one('SELECT pn.note from player_notes pn inner join player p on p.steamid="'.$_SESSION['steamid'].'" and p.id = pn.player_id where pn.target_player_id = '.$player->id.' and is_public = 0');
			if ($sqlcon->records==1) {
				$privateComment = $result['note'];
			}
		}
	}	// if session exist

	echo '<div id="playerdetails">';
	echo '<h5 style="color: red;">mvmlobby.com will be permantently deactivated on August 1st, 2017</h5>';
	echo '<div class="player_details_container" steamid="'.$steamid.'" isfriend="'.($isFriend?'1':'0').'" isfavorite="'.($isFavorite?'1':'0').'">';
	echo '<div class="playersummary">';
	echo '<a href="'.HTTP_ROOT.'profile/'.$player->steamid.'"><div class="playersummaryimage"><img src="'.$player->image_url_medium.'"/></div> <!-- playersummaryimage --></a>';
	echo '<div class="playersummarydetails">';
	if (!$isLoggedInUser) {
		echo '<img class="isfavorite" src="images/'.($isFavorite?'favorite_on.png':'favorite_unchecked.png').'" style="padding-right: 3px;"/>';
	}
	echo '<span player_attr="name">'.$player->name.'</span><br/>';
	// echo 'Steam: <a href="http://steamcommunity.com/profiles/'.$steamid.'" target=_blank>'.$steamid.'</a><br/>';
	echo 'Tours: <span player_attr="totaltours" style="font-weight: bold">'.$player->totalTours.'</span><br/>';
	$robotsKilledDisplay = '?';
	if ($player->robots_killed > 0) {
		if ($player->robots_killed == 1000000) {
			$robotsKilledDisplay = '>1,000,000';
		} else {
			$robotsKilledDisplay = number_format($player->robots_killed);
		}
	}
	echo 'Robots killed: <span player_attr="robotskilled" style="font-weight: bold">'.$robotsKilledDisplay.'</span><br/>';
	echo '</div>';		// playersummarydetails
	
// 	echo '<div style="float: left; margin-left: 8px;">';
// 	echo 'Tours: <span player_attr="totaltours" style="font-weight: bold">'.$player->totalTours.'</span><br/>';
// 	$robotsKilledDisplay = '?';
// 	if ($player->robots_killed > 0) {
// 		if ($player->robots_killed == 1000000) {
// 			$robotsKilledDisplay = '>1,000,000';
// 		} else {
// 			$robotsKilledDisplay = number_format($player->robots_killed);
// 		}
// 	}
// 	echo 'Robots killed: <span player_attr="robotskilled" style="font-weight: bold">'.$robotsKilledDisplay.'</span><br/>';
// 	echo 'Tickets: <span player_attr="ticketcount" style="font-weight: bold">'.$player->ticketCount.'</span>&nbsp;&nbsp;&nbsp;&nbsp;';
// 	echo '</div>';		// playersummarydata
	
	// Friend actions
	echo '<div class="playersummaryactions">';

 		if (empty($player->current_mvm_mission_name)) {
 			$html = $player->status;
 		} else {
 			// <a style="color: #060" href="#" onclick="displayServerInfo('208.78.166.169:27056','Empire Escalation','https://wiki.teamfortress.com/wiki/Empire_Escalation_(mission)'); return false;">Playing Empire Escalation</a>
 			$html='<a style="color: '.$player->status_color.';" href="#" onclick="displayServerInfo('."'".$player->current_game_ipport."','".$player->current_mvm_mission_name."','".$player->mission_wiki_url."'".'); return false;">'.$player->status.'</a>';
 		}

		echo 'Status: <span player_attr="steamstatus" style="color: '.$player->status_color.';">'.$html.'</span><br/>';
	
		if ($isLoggedInUser) {
			echo '<a href="logout.php">Logout</a><br/>';
		} else {
			echo '<a id="add_or_remove_friend_link" href="#">'.($isFriend?'Remove':'Add').' as '.SITE_NAME.' friend</a><br/>';
			echo '<a href="steam://friends/add/'.$steamid.'">Add as Steam friend</a><br/>';
			echo '<a href="steam://friends/message/'.$steamid.'">Open Steam chat</a><br/>';
		}

	echo '</div> <!-- playersummaryactions -->';
	
	if (!$isLoggedInUser && isset($sessionSteamId)) {
		// Private notes
		echo '<div id="private_comment_container">';
		echo '<button id="editPrivateNoteButton" type="button" class="btn btn-xs btn-info" data-toggle="modal" data-target="#editPrivateNote">Edit private comment</button><br/>';
		echo '<div id="private_comment" style="font-style: italic">' . $privateComment . '</div>';
		echo '</div><!-- /private_comment -->';
	}
	echo '</div> <!-- playersummary -->';
	echo '</div> <!-- player_details_container -->';
	
	echo '<span style="font-size: 10px;">MvM data last updated <span player_attr="inventory_display_age">'.$player->inventory_display_age.'</span> ago. &nbsp;&nbsp;';
	echo 'Status: <span id="mvmDataStatus"><span style="color: #aaa;">Checking for updated MvM data</span></span>&nbsp&nbsp';
	echo '<a href="profile/'.$player->steamid.'&refresh=1">(force tour data refresh now)</a></span><br/><div style="height: 10px;"></div>';
	
	// Display a status message here, if needed
	$msg = '';
	if ($player->site_status == 'Banned') {
		$msg .= 'Player is banned from '.SITE_NAME.'<br/>';
	}
	if ($player->inventory_unavailable) {
		if ($player->profile_visibility == 1) {
			$msg .= 'Profile set to private, displaying cached data.<br/>';
		} elseif ($player->inventory_status == 15) {
			$msg .= 'Inventory is set to private, displaying cached data.<br/>';
		} else {
			$msg .= 'Steam API error, displaying cached data.<br/>';
		}
	}
	if (!empty($msg)) {
		// echo '<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>'.$msg.'</div>';
		echo '<div class="alert alert-danger" role="alert">'.$msg.'</div> <!-- alert -->';
	}

	// Get comments
	$commentCount = 0;
	$myCommentsCount = 0;
	$myComments = null;
	if (empty($sessionSteamId)) {
		$comments = $sqlcon->query ( '
			select p.steamid, p.name, p.image_url_medium, pn.post_date, pn.note
			from player_notes pn
			inner join player p on p.id = pn.player_id
			where pn.target_player_id = '.$player->id.'
			and pn.is_public = 1
			order by pn.post_date desc' );
		$commentCount = $sqlcon->records;
	} else {
		$comments = $sqlcon->query ( '
			select p.steamid, p.name, p.image_url_medium, pn.post_date, pn.note
			from player_notes pn
			inner join player p on p.id = pn.player_id
			where pn.target_player_id = '.$player->id.'
			and pn.is_public = 1
			and p.steamid <> "'.$sessionSteamId.'"
			order by pn.post_date desc' );
		$commentCount = $sqlcon->records;
	
		$myComments = $sqlcon->one ( '
			select p.steamid, p.name, p.image_url_medium, pn.post_date, pn.note
			from player p
			left outer join player_notes pn on pn.player_id = p.id and pn.target_player_id='.$player->id.' and pn.is_public=1
			where p.steamid = "'.$sessionSteamId.'"');
		$myCommentsCount = (empty($myComments['note']) ? 0 : 1);
		$commentCount += $myCommentsCount;
		
		$myCommentsAllPlayers = $sqlcon->query ( '
				select p.steamid, p.name, p.image_url_small, pn.post_date, pn.note, pn.is_public
				from player_notes pn
				inner join player p on p.id = pn.target_player_id
				where pn.player_id = '.$player->id.'
				order by pn.post_date desc' );
		$myCommentsAllPlayersCount = $sqlcon->records;
		
	}
	?>

	<!-- Nav tabs -->
	<ul class="nav nav-tabs" role="tablist">
	  <li class="active"><a href="#tour_details" role="tab" data-toggle="tab">Tours</a></li>
	  <li><a href="#player_links" role="tab" data-toggle="tab">Info</a></li>
	  <li><a href="#player_activity" role="tab" data-toggle="tab">Activity</a></li>
	  <li><a href="#player_comments" role="tab" data-toggle="tab">Comments (<?php echo $commentCount; ?>)</a></li>
		<?php if ($isLoggedInUser) : ?>
	  <li><a href="#my_comments" role="tab" data-toggle="tab">My Comments (<?php echo $myCommentsAllPlayersCount; ?>)</a></li>
	  	<?php endif; ?>
	</ul>

	<div class="tab-content">
	
				
	<div id="tour_details" class="tab-pane active tab_content">
		<div class="panel-group" id="tour_details_accordion">
<?php
	$tourInfo = $sqlcon->query ( 'select t.id, t.name, t.difficulty, pt.mission_bitmask from tour t left outer join player_tour pt on pt.tour_id=t.id and pt.player_id=' . $player->id . ' order by t.sortorder' );
	$missionInfo = $sqlcon->query ( 'select mi.tour_id, map.name as map_name, mi.name as mission_name, mi.map_bitmask from mission mi inner join map on map.id=mi.map_id order by mi.sortorder');

		$javascriptTourInfo = '';
		foreach($tourInfo as $tour) {
			$tourId = $tour['id'];
			$tourName = $tour['name'];
			$tourDifficulty = $tour['difficulty'];
			$playerMissionBitmask = intval($tour['mission_bitmask']);

			$toursCompleted = 0;
			$playerActivelyPlays = 0;
			foreach($player->tourArray as $playerTour) {
				if ($playerTour['id'] == $tourId) {
					$tempToursCompleted = $playerTour['tours_completed'];
					if ($tempToursCompleted != null) {
						$toursCompleted = $tempToursCompleted;
					}
					$playerActivelyPlays = $playerTour['isActive'];
					break;
				}
			}
			// $sectionHiddenState = ($playerActivelyPlays? false : true);	// true=hidden, false=display
			$javascriptTourInfo .= 'currentTourData['.$tourId.']= new Array'.";\n";
			$javascriptTourInfo .= 'currentTourData['.$tourId.']["id"] = '.$tourId.";\n";
			$javascriptTourInfo .= 'currentTourData['.$tourId.']["tours_completed"] = '.$toursCompleted.";\n";
			$javascriptTourInfo .= 'currentTourData['.$tourId.']["mission_bitmask"] = '.$playerMissionBitmask.";\n";

			$sectionHiddenState = ($tourId!=4 && $tourId!=5);

			// Display tour and mission data
			$missionHTML = '<div id="tour_details_tour'.$tourId.'" class="panel-collapse collapse'.($sectionHiddenState?'':' in').'"><div class="panel-body">';
			$missionHTML .= '<table style="padding-left: 50px;">';
			$missionCount = 0;
			$missionsPlayed = 0;
			foreach($missionInfo as $mission) {
				if ($mission['tour_id'] == $tourId) {
					++$missionCount;
					$mapName = $mission['map_name'];
					$missionName = $mission['mission_name'];
					$missionBitmask = intval($mission['map_bitmask']);
					$completed = (($playerMissionBitmask & $missionBitmask) == $missionBitmask);
					$missionsPlayed += ($completed?1:0);
					$missionHTML .= '<tr><td width="200px">'.$mapName.'</td>';
					$missionHTML .= '<td width="160px">'.$missionName.'</td>';
					$missionHTML .= '<td player_attr="mission" tour_id="'.$tourId.'" bitmask='.$missionBitmask.' width="40px" style="color: #'.($completed?'00aa00':'aa0000').'">'.($completed?'Complete':'Incomplete').'</td>';
					$missionHTML .= '</tr>';
				}
			}
			$missionHTML .= '</table>';
			$missionHTML .= '</div><!-- /panel-body --> </div><!-- /tour_details_tour -->'."\n";

			echo '<div class="panel panel-default"><div class="panel-heading"><h4 class="panel-title">';
			echo '<a data-toggle="collapse" data-parent="#tour_details_accordion" href="#tour_details_tour'.$tourId.'" style="outline: 0;">';
			echo '<span style="display: inline-block; width: 200px; font-weight: bold;">'.$tourName.'</span>';
			echo '<span style="display: inline-block; width: 160px;">'.$tourDifficulty.'</span>';
			echo '<span player_attr="tours_completed" tour_id="'.$tourId.'" style="display: inline-block; width: 120px;"><span style="font-weight: bold;">'.$toursCompleted.'</span> tour'.($toursCompleted==1?'':'s').' ('.$missionsPlayed.'/'.$missionCount.')</span>';
			echo '</a></h4></div><!-- /.panel-heading --> </div><!-- /.panel panel-default --><div style="height: 4px;"></div>';
			echo $missionHTML;
		}
		
?>
		</div>	<!-- /.panel-group -->
	</div> <!-- /.tour_details -->
	
	<div id="player_links" class="tab-pane tab_content">
<?php 
	
	function createLinkHTML($label, $content, $isLink, $isBlankLink) {
		if ($isLink) {
			$content = '<a href="'.$content.'"'.($isBlankLink?' target=_blank':'').'>'.$content.'</a>';
		}
		return '<tr><td style="text-align: right;"><b>'.$label.'</b>' . '&nbsp; </td><td>'.$content.'</tr>';
	}

	$steam3idNumber = $player->steam3id;
	$steam2idParm2 = $steam3idNumber % 2;
	$steam2idParm3 = floor($steam3idNumber / 2);
	$steam2id = 'STEAM_0:'.$steam2idParm2.':'.$steam2idParm3;
	$steam3id = 'U:1:'.$steam3idNumber;

	echo '<b>'.$player->name . " ID's:</b><br/><p></p>";
	echo '<table class="summary-link-table">';
	echo createLinkHTML('Steam ID &nbsp;', $player->steamid, FALSE, FALSE);
	echo createLinkHTML('Steam 2 ID &nbsp;', $steam2id, FALSE, FALSE);
	echo createLinkHTML('Steam 3 ID &nbsp;', $steam3id, FALSE, FALSE);
	echo '</table>';
	echo '<br/>';
		
	echo '<b>'.$player->name . ' links:</b><br/><p></p>';
	echo '<table class="summary_link_table">';
	echo createLinkHTML('MvM Lobby', HTTP_ROOT.'profile/'.$player->steamid, TRUE, FALSE);
	echo createLinkHTML('Steam profile', 'http://steamcommunity.com/profiles/'.$steamid, TRUE, TRUE);
	echo createLinkHTML('SteamRep', 'http://steamrep.com/profiles/'.$steamid, TRUE, TRUE);
	echo createLinkHTML('Steam inventory', 'http://steamcommunity.com/profiles/'.$steamid.'/inventory', TRUE, TRUE);
	echo createLinkHTML('backpack.tf', 'http://backpack.tf/profiles/'.$steamid, TRUE, TRUE);
	echo '</table>';
	
	if (isset($user) && $user->isAdmin()) {
		echo '&nbsp;<br/><strong>Admin info:</strong><br/>';
		echo 'Player id: '.$player->id.'<br/>'; 
		echo '<a href="mvmlobbyAPI.php?apitype=getRawSummary&steamid=' . $steamid . '" target=_blank>View raw summary</a><br/>';
		echo '<a href="mvmlobbyAPI.php?apitype=getRawInventory&steamid=' . $steamid . '" target=_blank>View raw inventory</a><br/>';
		echo '<a href="mvmlobbyAPI.php?apitype=getRawFriendsList&steamid=' . $steamid . '" target=_blank>View raw friends list</a><br/>';
		
		echo '<div class="divider4"></div><button class="btn btn-danger" onclick="deletePlayer();">Delete</button>';
		?>
		<script>
		function deletePlayer() {
			$.ajax({
				type: 'POST',
				url: 'mvmlobbyAPI.php',
				data: {
					'apitype':'deletePlayer',
					'steamid':'<?php echo $steamid; ?>',
				}
			})
			.done(function(response) {
				alert("Player deleted");
			})
			.fail(function(error) {
				alert("Failed to delete player");
			});
		}
		</script>
		<?php 
		
	}
	?>
		</div>	<!-- /.player-links -->
		
		<div id="player_activity" class="tab-pane tab_content">
		<div class="panel panel-default">
		<table class="table">
		<thead><tr><th>Date</th><th>Tour Name</th><th>Tours Completed</th></tr></thead>
<?php 
		$historyResults = $sqlcon->query ( 'select h.date_changed, h.tours_completed, t.short_name from player_tour_history h inner join tour t on t.id=h.tour_id and h.player_id=' . $player->id . ' order by h.date_changed desc limit 10' );
		foreach($historyResults as $historyItem) {
			echo '<tr><td>'.$historyItem['date_changed'] . '</td><td>' . $historyItem['short_name'] . '</td><td>' . $historyItem['tours_completed'] . '</td></tr>';
		}
?>
		</table>
		</div>	<!-- /.pane panel-default -->
		<span style="font-size: 10px;">*** Estimated.  The data reflects the last known 10 days played.  Only players with over 25 total tours are automatically refreshed daily. A number of factors could delay results.</span><br/><p><p/>
		</div>	<!-- /.player-activity -->
		
		
		<!--  comments -->
		<div id="player_comments" class="tab-pane tab_content">
		

<div id="newPublicCommentContainer" classDisabled="panel panel-default" <?php if ($myCommentsCount==1) {echo ' style="display: none;"'; }; ?>>
  <div class="panel-body">
		<textarea class="form-control" maxlength=512 rows=2 id="newPublicComment"></textarea>
  </div>
  <button id="newPublicCommentAddButton" type="button" class="btn btn-primary btn-sm" style="margin-left: 18px;">Add Public Comment</button>
</div>
<br/>

		<div class="panel panel-default widget">
            <div class="panel-heading">
                <span class="glyphicon glyphicon-comment"></span>
                <h3 class="panel-title" style="display: inline">
                    Recent Comments</h3>
                <span class="label label-info" style="float: right;"><?php echo $commentCount; ?></span>
            </div>
            <div class="panel-body">
                <ul class="list-group">

<?php if (isset($sessionSteamId)) : ?>
                    <li id="myPublicCommentContainer" class="list-group-item" <?php if (!$myCommentsCount) {echo ' style="display: none;"'; }; ?>>
                        <div class="row">
                            <div class="col-xs-2 col-md-1">
<?php 
	echo '<img src="'.$myComments['image_url_medium'].'" class="img-square img-responsive" alt=""></div>';
?>
                            <div class="col-xs-10 col-md-11">
                                <div class="comment-text">
<span id="myPublicComment" ><?php echo $myComments['note']; ?></span>
                                    <div class="mic-info">
<?php echo ' - <a href="'. HTTP_ROOT . 'profile/'.$myComments['steamid'] . '">'.$myComments['name'].'</a> on ' . $myComments['post_date']; ?>
                                    </div>
                                </div>
                                <div class="action">
                                    <button id="editPublicNoteButton" type="button" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#editPublicNote" title="Edit">
                                        <span class="glyphicon glyphicon-pencil"></span>
                                    </button>
                                    <button id="deletePublicNoteButton" type="button" class="btn btn-danger btn-xs" title="Delete">
                                        <span class="glyphicon glyphicon-trash"></span>
                                    </button>
                                </div>	<!--  action -->
                            </div>
                        </div>
                    </li>
<?php endif; ?>                    


<?php
	foreach($comments as $comment) { 
?>
                    <li class="list-group-item">
                        <div class="row">
                            <div class="col-xs-2 col-md-1">
<?php 
	echo '<img src="'.$comment['image_url_medium'].'" class="img-square img-responsive" alt=""></div>';
?>
                            <div class="col-xs-10 col-md-11">
                                <div class="comment-text">
<?php echo $comment['note']; ?>
                                    <div class="mic-info">
<?php echo ' - <a href="'. HTTP_ROOT . 'profile/'.$comment['steamid'] . '">'.$comment['name'].'</a> on ' . $comment['post_date']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
<?php
	} 
?>
                </ul>	<!--  panel body -->
            </div>
        </div>
		</div>	<!-- /.player-comments -->

		
				
		<?php if ($isLoggedInUser) : ?>
		<!--  my comments -->
		<div id="my_comments" class="tab-pane tab_content">

		<div class="panel panel-default widget">
            <div class="panel-heading">
                <h3 class="panel-title" style="display: inline">My Comments</h3>
                <span class="label label-info" style="float: right;"><?php echo $myCommentsAllPlayersCount; ?></span>
            </div>
            <div class="panel-body">
                <ul class="list-group">


<?php
	foreach($myCommentsAllPlayers as $comment) { 
?>
                    <li class="list-group-item">
                        <div style="width: 100%;">
                            <div style="width: 40px; display: inline-block;">
<?php 
	echo '<img src="'.$comment['image_url_small'].'" class="img-square img-responsive" alt="">';
?>
							</div>
                            <div style="display: inline-block;">
                                <div class="comment-text">
<?php
	echo '<a href="'. HTTP_ROOT . 'profile/'.$comment['steamid'] . '">'.$comment['name'].'</a> on ' . $comment['post_date'];
?>
                                    <div class="mic-info">
<?php
	if ($comment['is_public'] == 0) {
		echo '<span style="color: #f00; padding-right: 5px;">[Private]</span>';
	}
	echo $comment['note'];
?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
<?php
	} 
?>
                </ul>	<!--  panel body -->
            </div>
        </div>
		</div>	<!-- /.my_comments -->
		<?php endif; ?>
		
	</div> <!-- /.tab-content -->
</div>	<!-- /playerdetails -->
	
<div id="editPrivateNote" class="modal fade">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
        <h4 class="modal-title">Edit Private Comments</h4>
      </div>
      <div class="modal-body">
		<textarea class="form-control" maxlength=512 rows=5 id="updated_private_comment" placeholder="The text you enter here is only visible by you."></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button id="privateNoteSaveButton" type="button" data-dismiss="modal" class="btn btn-primary">Save</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div id="editPublicNote" class="modal fade">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
        <h4 class="modal-title">Edit Comments</h4>
      </div>
      <div class="modal-body">
		<textarea class="form-control" maxlength=512 rows=5 id="updated_public_note"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button id="publicNoteSaveButton" type="button" data-dismiss="modal" class="btn btn-primary">Save</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>



$(document).ready(function() {

	// The header tours and tickets are from cache for faster loading, so refresh them after the page loads
	// get_player_data from mvmlobby.js is apparently not loaded at this point and fails
	var pdc = new PlayerDetailController('<?php echo $player->steamid; ?>');
	pdc.refreshData();
});

</script>

<?php include ('server_details.php'); ?>

