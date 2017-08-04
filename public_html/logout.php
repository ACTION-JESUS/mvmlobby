<?php
	// Remove all session related data
	// $_SESSION['steamid'] 
	// $_COOKIE ['user_steam_id'];
	// $_COOKIE ['session_key'];
	// setcookie('user_steam_id', $player->steamid, time()+86400*SESSION_DAYS);
	// setcookie('session_key', $player->createSessionKey(), time()+86400*SESSION_DAYS);

	require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'config.php');
	
	session_start();
	session_unset();	// removes 'steamid'
	session_destroy();
	
	setcookie('user_steam_id', '', time()-3600);
	setcookie('session_key', '', time()-3600);
	
	header('Location: '.HTTP_ROOT);
?>