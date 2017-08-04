<?php
	require_once('header.php');

	$display_type = 'total_tours';
	$filterTourId = '5';
	$mySteamId = 'x';
	$limit = 500;
	
	if (isset($_REQUEST['display_type'])) {
		$value = $_REQUEST['display_type'];
		if ($value === 'total_tours_completed' || $value==='botskilled' || $value=='most_completed_by_tour' || $value==='most_handsome') {
			$display_type = $value;
		}
	}
	
	if ($display_type == 'most_completed_by_tour' && isset($_REQUEST['tour_id'])) {
		$filterTourId = $_REQUEST['tour_id'];
	}
	
	if (isset($_SESSION) && isset($_SESSION['steamid'])) {
		$mySteamId = $_SESSION['steamid'];
	}
	
	echo '<h5 style="color: red;">mvmlobby.com will be permantently deactivated on August 1st, 2017</h5>';
	echo '<div class="content"><div class="halloffamecontent">';
	
	echo '<ul class="halloffame">';
	echo '<li><a href="halloffame.php?display_type=total_tours">Tours Completed</a></li> | ';
	echo '<li><a href="halloffame.php?display_type=most_completed_by_tour">By Tour</a></li> |';
	echo '<li><a href="halloffame.php?display_type=botskilled">Robots Killed</a></li>';
	echo '</ul>';

	// --- Total Tours --------------------------------------------------------------------------
	if ($display_type === 'total_tours') {
		$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
		$results = $sqlcon->query('select p.id, p.steamid, p.name, p.total_tours from player p where p.total_tours>0 order by p.total_tours desc limit 0,'.$limit);

		echo '<div class="halloffametitle">* highest total tours completed *</div>';
		echo '<table id="halloffametable"><tr><th class="alignright">Rank</td><th class="alignright">Tours</th><th class="alignleft">Player Name</th></tr>';
		foreach($results as $key=>$result) {
			$name = $result['name'];
			$total_tours = $result['total_tours'];
			$steamid = $result['steamid'];
			
			echo '<tr'.($steamid==$mySteamId?' class="thisisme"':'').'><td class="alignright">'.($key+1).'</td><td class="alignright">'.$total_tours.'</td><td class="alignleft"><a href="profile/'.$steamid.'">'.$name.'</a></td></tr>';
		}
		echo '</table>';
	}
	
	
	// --- Tours Completed by Tour ----------------------------------------------------------
	if ($display_type === 'most_completed_by_tour') {
		$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
		$results = $sqlcon->query('select p.id, p.steamid, p.name, pt.tours_completed from player_tour pt inner join player p on p.id=pt.player_id where p.total_tours>0 and pt.tour_id = '.$filterTourId.' order by pt.tours_completed desc limit 0,'.$limit);
		
		$tourName = NULL;
		$tourArray = $sqlcon->query('select id, short_name from tour order by sortorder');
		echo '<div style="text-align: center;"><select id="mvmtour">';
		foreach($tourArray as $tour) {
			$tourId = $tour['id'];
			echo '<option value="'.$tourId.'"'.($tourId==$filterTourId?' selected':'').'>'.$tour['short_name'].'</option>';
			if ($tourId==$filterTourId) {
				$tourName = $tour['short_name'];
			}
		}
		echo '</select></div>';

		if (empty($tourName)) {
			echo 'Invalid tour ID';
		} else {
			echo '<div class="halloffametitle">* highest '.$tourName.' tours *</div>';
			echo '<table id="halloffametable"><tr><th class="alignright">Rank</td><th class="alignright">Tours</th><th class="alignleft">Player Name</th></tr>';
			foreach($results as $key=>$result) {
				$name = $result['name'];
				$tours_completed = $result['tours_completed'];
				$steamid = $result['steamid'];
					
				echo '<tr'.($steamid==$mySteamId?' class="thisisme"':'').'><td class="alignright">'.($key+1).'</td><td class="alignright">'.$tours_completed.'</td><td class="alignleft"><a href="profile/'.$steamid.'">'.$name.'</a></td></tr>';
			}
			echo '</table>';
		}
	}
	
	
	// --- Robots killed -------------------------------------------------------------------
	if ($display_type === 'botskilled') {
		$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
		$results = $sqlcon->query('SELECT p.id, p.steamid, p.name, p.robots_killed FROM player p WHERE p.robots_killed >0 ORDER BY p.robots_killed DESC LIMIT 0,'.$limit);
	
		echo '<div class="halloffametitle">* robots killed *</div>';
		echo '<table id="halloffametable"><tr><th class="alignright">Rank</td><th class="alignright">Robots Killed</th><th class="alignleft">Player Name</th></tr>';
		foreach($results as $key=>$result) {
			$name = $result['name'];
			$robotsKilled = $result['robots_killed'];
			$steamid = $result['steamid'];
	
			echo '<tr'.($steamid==$mySteamId?' class="thisisme"':'').'><td class="alignright">'.($key+1).'</td><td class="alignright">'.$robotsKilled.'</td><td class="alignleft"><a href="profile/'.$steamid.'">'.$name.'</a></td></tr>';
		}
		echo '</table>';
	}
	
	
	// --- Most Handsome -------------------------------------------------------------------
	if ($display_type === 'most_handsome') {
	
		echo '<div class="halloffametitle">* Most Handsome *</div>';
		echo '<table id="halloffametable"><tr><th class="alignright">Rank</td><th class="alignleft">Player Name</th></tr>';
		$name = 'A Cosby Sweater';
		$steamid = '76561197996903184';

		echo '<tr'.($steamid==$mySteamId?' class="thisisme"':'').'><td class="alignright">1</td><td class="alignleft"><a href="profile/'.$steamid.'">'.$name.'</a></td></tr>';
		echo '</table>';
	}
	
	
	echo '</div></div>';	// div.halloffamecontent / .content
?>
<script>
$(document).ready(function() {
	// Change the mvm tour option if passed in the request
	$('#mvmtour').change(function() {
		var tour_id = $('#mvmtour').val();
		location.href='halloffame.php?display_type=most_completed_by_tour&tour_id='+tour_id;
	});
});

</script>

<?php
	include_once('footer.php');
?>