<?php
	define('IS_PROD', FALSE);						// FALSE=local, TRUE=production
	define('HOST_NAME', 'localhost');
	define('MYSQL_DB', 'mvmlobby');
	define('MYSQL_USERID', 'mysql_user_name_here');
	define('MYSQL_PW', '#####################');	// Add your MySQL password here
	define('STEAM_API_KEY', '########################');	// register at http://steamcommunity.com/dev/apikey
	define('SUMMARY_REFRESH_MINUTES', 1);
	define('INVENTORY_REFRESH_MINUTES', 15);
	define('SITE_NAME', 'MvM Lobby');
	define('AUTH_SALT', '###############################');	
	define('STEAM_ID_OFFSET', 76561197960265728);					// the number added to the Steam 3 ID
	define('SESSION_DAYS', 60);
	
 	define('DS', DIRECTORY_SEPARATOR);
 	define('OS_ROOT_DIR', '/var/www/html/eclipse/mvmlobby/public_html');
 	
	define('HTTP_ROOT', 'http://'.HOST_NAME.'/eclipse/mvmlobby/public_html/');
	
	// Not used - this was working for a sort of "lobby" that never caught on 
	define('WEBSOCKET_HOST', HOST_NAME);
	define('WEBSOCKET_PORT', '9001');
	define('WEBSOCKET_URI', 'ws://'.HOST_NAME.':9001/eclipse/mvmlobby/public_html/websocket_listener.php');
	
	//define('ROOT_DIR', dirname(__FILE__));	// D:\Development\web\xampp\htdocs\mvmlobby
	//define('ROOT_UNIX', $_SERVER['DOCUMENT_ROOT']);	// D:/Development/web/xampp/htdocs
?>
