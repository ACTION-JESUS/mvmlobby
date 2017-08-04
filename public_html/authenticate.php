<?php
if (session_id()=='' || !isset($_SESSION)) {
	@session_start();
}

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'config.php');
require_once('openid'.DS.'openid.php');
require_once('MySQL.php');
require_once('Player.php');

$provider = 'http://steamcommunity.com/openid';
// $openid->identity = "http://steamcommunity.com/openid/id/76561197973743060"

try {
	$openid = new LightOpenID(HOST_NAME);
	if (!$openid->mode) {
		$openid->identity = $provider;
		header('Location: ' . $openid->authUrl());
	} else {
		if ($openid->mode == 'cancel') {
			$outputMsg='User has canceled authentication';
		} else {
			if ($openid->validate()) {
				if(preg_match("/\/(\d+)$/",$openid->identity,$matches))
				{
					$steamid=$matches[1];
					//$_SESSION['steam_identity'] = $openid->identity;
					$player = new Player($steamid);
					if ($player) {
						if ($player->site_status == 'Banned') {
							$outoutMsg = 'player has been banned';
						} else {
							$outputMsg = 'validation successful';
							$player->onLoginSuccess();
						}
					}
				}
			} else {
				$outputMsg =  'User has not logged in.';
			}
		}
		// TODO: pass the error here

		header('Location: index.php');
	}
} catch (ErrorException $e) {
	// echo $e->getMessage(); // TODO:  pass the error back here
	header('index.php');
}

?>
