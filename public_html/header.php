<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
	if (session_id()=='' || !isset($_SESSION)) {
		@session_start();
	}
	require_once('Player.php');
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="description" content="Team Fortress 2 Mann Vs Machine">
<meta name="author" content="ACTION JESUS">
<!-- <link rel="icon" href="../../favicon.ico"> -->

<title><?php echo SITE_NAME; ?></title>

<base href="<?php echo HTTP_ROOT; ?>">

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="css/bootstrap.3.2.0.min.css">

<!-- Optional theme -->
<!-- <link rel="stylesheet" href="css/bootstrap-theme.3.2.0.min.css"> -->

	<?php
		if (IS_PROD===TRUE) {
			echo '<link rel="stylesheet" type="text/css" href="css/mvmlobby.min.css" />';
		} else {
			echo '<link rel="stylesheet/less" type="text/css" href="css/mvmlobby.less" />';
			echo '<script src="js/less-1.6.0.min.js" type="text/javascript"></script>';
		}
	?>

	<script src="js/jquery-2.1.0.min.js" type="text/javascript"></script>

</head>

<?php 
	$user = NULL;
	
	// Check if a current session already exists
	if (isset ( $_SESSION ['steamid'] )) {
		$user = new Player ( $_SESSION ['steamid'], Player::REFRESH_FORCE_OFF);
	}
	
	// If no session exists, authenticate using cookies first
	if ($user == NULL && isset ( $_COOKIE ['user_steam_id'] ) && isset ( $_COOKIE ['session_key'] )) {
		$steamid = $_COOKIE ['user_steam_id'];
		$cookieSessionKey = $_COOKIE ['session_key'];
		
		$tempUser = new Player ( $steamid, Player::REFRESH_FORCE_OFF );
		if ($tempUser != NULL && isset($tempUser->id)) {
			if ($tempUser->cookieIsValid($cookieSessionKey)) {
				$user = $tempUser;
				$_SESSION ['steamid'] = $user->steamid;
				$user->userLoggedIn();
			}
		}
	}
?>
<body>
	<div class="navbar navbar-default" role="navigation">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="<?php echo HTTP_ROOT; ?>"><?php echo SITE_NAME; ?></a>
			</div>
			<div class="navbar-collapse collapse">
				<ul class="nav navbar-nav">
					<li><a href="index.php">My Profile</a></li>
					<li><a href="friends.php">Friends</a></li>
					<?php
// 						if (isset($user) && $user->isAdmin()) {
// 							echo '<li><a href="lobby.php">Lobby</a></li>';
// 						} 
// 						if (isset($user) && sizeof($user->groupArray)>0) {
// 							echo '<li><a href="teams.php">Teams</a></li>';
// 						} 
					?>
					<li><a href="tools.php">Tools</a></li>
					<li><a href="search.php">Search</a></li>
					<li><a href="halloffame.php">Hall of Farmers</a></li>
					<li><a href="help.php">Help</a></li>
				</ul>
				<ul class="nav navbar-nav navbar-right">
				<?php
				
				if ($user != NULL) {
					echo '<div style="padding-top: 4px">';
					echo '<a class="center-vertical" href="'.HTTP_ROOT.'profile/'.$user->steamid.'"><img src="' . $user->image_url_small . '"/></a>';
					echo '<div class="center-vertical" style="padding-left: 8px;">';
					echo $user->name . '<br/>';
					// echo 'Steam: <a href="http://steamcommunity.com/profiles/' . $user->steamid . '" target=_blank>' . $user->steamid . '</a><br/>';
					echo 'Tickets: <span id="headerTicketsOwned" style="font-weight: bold">' . $user->ticketCount . '</span>&nbsp;&nbsp;&nbsp;&nbsp;';
					echo 'Tours: <span id="headerTotalTours" style="font-weight: bold">' . $user->totalTours . '</span><br/>';
					echo '</div>';	// text info
					echo '</div>';	// container for image and text
				} else {
					echo '<div style="padding-top: 3px;">';
					echo '<a href="authenticate.php"><img src="images/sits_large_noborder.png"></a>';
					echo '</div>';	// container for image and text
				}
				?>
				</ul>
			</div>	<!--/.nav-collapse -->
		</div>	<!--/.container-fluid -->
	</div>	<!-- navbar -->