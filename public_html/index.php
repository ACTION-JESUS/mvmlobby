<?php
	require_once('header.php');
	echo '<div class="content">';
	if ($user != null) {
		$_REQUEST['steamid'] = $user->steamid;
		include('get_player_details.php');
	} else {
		echo 'Welcome to '.SITE_NAME.'.  Please sign in through Steam.';
	}
	echo '</div>';
	
	include('footer.php');
?>