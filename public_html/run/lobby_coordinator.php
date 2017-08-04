<?php

if (isset($_SERVER['HTTP_USER_AGENT'])) {
	die('Invalid request');		// no direct access
}
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'config.php');
require_once(OS_ROOT_DIR.DS.'MySQL.php');
$host = WEBSOCKET_HOST;
$port = WEBSOCKET_PORT;
$null = NULL; //null var

//Create TCP/IP sream socket
// $domainType = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' ? AF_INET : AF_UNIX);	AF_UNIX is not working on Mint(?!)
$socket = socket_create(AF_INET, SOCK_STREAM, getprotobyname('tcp'));
if (!$socket) { die(); }
//reuseable port
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

//bind socket to specified host
socket_bind($socket, 0, $port);

//listen to port
socket_listen($socket);

//create & add listening socket to the list
// array looks like $clients[0] = "Resource id #4"
$clients = array($socket);

$sqlcon = new MySQL(MYSQL_DB, MYSQL_USERID, MYSQL_PW);
$playerList = array();	// key=socket.toString(), value=array(steamid, name, etc.)

//start endless loop, so that our script doesn't stop
while (true) {
	//manage multiple connections
	$changed = $clients;
	
	//returns the socket resources in $changed array
	socket_select($changed, $null, $null, 0, 10);
	
	//check for new socket
	if (in_array($socket, $changed)) {
		$newSocket = socket_accept($socket); //accept new socket
		$clients[] = $newSocket; //add socket to client array
		
		$header = socket_read($newSocket, 1024); //read data sent by the socket
		perform_handshaking($header, $newSocket, $host, $port); //perform websocket handshake
		
		//socket_getpeername($newSocket, $ip); //get ip address of connected socket
		
		//make room for new socket
		$found_socket = array_search($socket, $changed);
		unset($changed[$found_socket]);
	}

	//loop through all connected sockets
	foreach ($changed as $changed_socket) {	
		
		$socketId = (string)$changed_socket;		// i.e. "Resource #4".  TODO: Is there a better ID?
		
		$steamid = NULL;
		if (isset($playerList[$socketId]) && isset($playerList[$socketId]['steamid'])) {
			$steamid = $playerList[$socketId]['steamid'];
		}
		// devlog(print_r($playerList,true));
		
		//check for any incoming data
		while(@socket_recv($changed_socket, $buf, 1024, 0) >= 1)
		{
			//$received_text = unmask($buf); //unmask data
			$action = NULL;
			$msg = json_decode(unmask($buf)); //json decode
			
			if (isset($msg->type) && isset($msg->action) && isset($msg->steamid) && isset($msg->phpsessid)) {
				$type = $msg->type;
				$action = $msg->action;
				$inputSteamid = $msg->steamid;
				$phpsessid = $msg->phpsessid;
				
				devlog(print_r($msg,true));
				
				// Ignore if the steamid does not match the verified steamid
				if (!is_null($steamid) && $steamid != $inputSteamid) {
					continue;			// manipulated message?  skip to the next message
				}
				
				if ($type == 'player') {
					
					if ($action === 'add') {
						$steamid = $inputSteamid;
						//socket_getpeername($changed_socket, $ip, $port); //get ip address of connected socket
						
						$playerArray = verifyPlayer($sqlcon, $inputSteamid, $phpsessid);

						if (!empty($playerArray)) {

							// new player need the full player and team list (before updating the list)
							sendMessageToSocket($changed_socket, array('type'=>'player', 'action'=>'showlist', 'playerlist'=>$playerList));
								
							$playerArray['socketid'] = $socketId;
							$playerList[$socketId] = $playerArray;
										
							broadcastToAllSockets(array('type'=>'player', 'action'=>'connect', 'playerdata'=>$playerArray));
							
						}
					}
				} else if ($type == 'chat') {
					
					if ($action == 'add') {
						
						if (isset($msg->message) && !empty($msg->message)) {
							$name = $playerList[$socketId]['name'];
							broadcastToAllSockets(array('type'=>'chat', 'action'=>'add', 'name'=>$name, 'message'=>$msg->message));
						}
					}
					
				}				 
			}
			
			break 2; //exist this loop
		}

		// check for a disconnect
		$buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
		if ($buf === FALSE) { // check disconnected client
			// remove client for $clients array
			$socketKey = array_search($changed_socket, $clients);	// socketKey = numeric key
			if ($socketKey != FALSE) {
				$player = $playerList[$socketId];
	
				//notify all users about disconnected connection
				if (!is_null($steamid)) {
				}
				broadcastToAllSockets(array('type'=>'player', 'action'=>'disconnect', 'playerdata'=>$player));
				//sendUpdatedPlayerList();
				
				unset($playerList[$socketId]);
				unset($clients[$socketKey]);
			
				// if (!($socketKey===FALSE)) {
				// socket_close($changed_socket);
				// }
			}
			
		}
		
	}	// end of changed socket loop
}
// close the listening socket
socket_close($socket);

function disconnectPlayer($socketId) {
	$player = $playerList[$socketId];
	
	//notify all users about disconnected connection
	broadcastToAllSockets(array('type'=>'player', 'action'=>'disconnect', 'playerdata'=>$player));
	//sendUpdatedPlayerList();
	
	unset($playerList[$socketId]);
	unset($clients[$socketKey]);
}


/**
 * Send a message to all clients
 * 
 * @param array $dataArray array('type'=>'player', 'action'=>'add', 'player'=>array(xxx))
 */
function broadcastToAllSockets($dataArray)
{
	global $clients;
	$msg = mask(json_encode($dataArray));
	foreach($clients as $clientSocket)
	{
		@socket_write($clientSocket,$msg,strlen($msg));
	}
}


/**
 * Send a message to a specific client
 * 
 * @param Object $socket - the client socket
 * @param array $dataArray - array('type'=>'player', 'action'=>'add', 'player'=>array(xxx))
 */
function sendMessageToSocket($socket, $dataArray)
{
	$msg = mask(json_encode($dataArray));
	@socket_write($socket,$msg,strlen($msg));
}


/**
 * Return an error message to the client
 *  
 * @param Object $socket - the client socket resource
 * @param String $errorMsg - the error message
 */
function sendErrorToSocket($socket, $errorMsg) {
	sendMessageToSocket($socket, array('error' => $errorMsg));
}


//Unmask incoming framed message
function unmask($text) {
	$length = ord($text[1]) & 127;
	if($length == 126) {
		$masks = substr($text, 4, 4);
		$data = substr($text, 8);
	}
	elseif($length == 127) {
		$masks = substr($text, 10, 4);
		$data = substr($text, 14);
	}
	else {
		$masks = substr($text, 2, 4);
		$data = substr($text, 6);
	}
	$text = "";
	for ($i = 0; $i < strlen($data); ++$i) {
		$text .= $data[$i] ^ $masks[$i%4];
	}
	return $text;
}

//Encode message for transfer to client.
function mask($text)
{
	$b1 = 0x80 | (0x1 & 0x0f);
	$length = strlen($text);

	if($length <= 125)
		$header = pack('CC', $b1, $length);
	elseif($length > 125 && $length < 65536)
	$header = pack('CCn', $b1, 126, $length);
	elseif($length >= 65536)
	$header = pack('CCNN', $b1, 127, $length);
	return $header.$text;
}



//handshake new client.
function perform_handshaking($receved_header,$client_conn, $host, $port)
{
	$headers = array();
	$lines = preg_split("/\r\n/", $receved_header);
	foreach($lines as $line)
	{
		$line = chop($line);
		if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
		{
			$headers[$matches[1]] = $matches[2];
		}
	}

	$secKey = $headers['Sec-WebSocket-Key'];
	$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	//hand shaking header
	$upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
	"Upgrade: websocket\r\n" .
	"Connection: Upgrade\r\n" .
	"WebSocket-Origin: $host\r\n" .
	"WebSocket-Location: '.WEBSOCKET_URI.'\r\n".
	"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
	socket_write($client_conn,$upgrade,strlen($upgrade));
}

/**
 * Verify that a player has an existing session.
 * 
 * Note: This process must be running as a user with access to the PHP session file (i.e. /var/lib/php5/sesxxx).
 * Otherwise it will return null. 
 * 
 * @param string $jsSteamId = the Javascript cookie steamid
 * @param string @phpsessid = the Javascript cookie PHPSESSID variable
 * 
 * @return $playerArray('id'=>xx, 'steamid'=>'7656xxx', etc.)
 */
function verifyPlayer($sqlcon, $jsSteamId, $phpsessid) {
	$playerArray = NULL;
	
	if (!empty($jsSteamId) && !empty($phpsessid)) {
		
		$sessionSteamid = NULL;
		session_id($phpsessid);
		@session_start();
		if(isset($_SESSION['steamid'])){
			if ($jsSteamId === $_SESSION['steamid']) {
				$playerArray = $sqlcon->one('select id, steamid, name, region, total_tours from player where steamid="'.$jsSteamId.'"');
			}
		}
		session_write_close();
	}
	return $playerArray;
}



function devlog($msg) {
	if (!IS_PROD) {
		print_r($msg."\n");
	}
}