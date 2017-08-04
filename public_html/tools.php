<?php
	require_once('..'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'config.php');
	require_once('header.php');
?>

<div class="container">

	<h3>Create links to your party</h3>
	<p>Allow others to directly join your party!  You can share the lobby links through group chat, group events, the web, Mumble, etc.</p>
	There are 2 ways to do it:<br>
	a) Cut & paste your lobby ID below <a id="show_instructions_link" href='#'>(show instructions)</a><br>
	b) Invite the <a href="http://steamcommunity.com/id/lobbyid/" target=_blank>Lobby ID Bot</a> to your party.<br>
	<div id="instructions" style="display: none; margin-top: 8px;">
	To use it:<br>
	1) Click the Invite button and close it.<br>
	2) Open the developer console.<br>
	3) Copy and paste the lobby ID below<br>
	&nbsp;&nbsp;&nbsp;Example: "steam lobby [L:1:1444365316]", the text around it is okay too.<br>
	4) Use the links provided to share with others.<br>
	</div>
	<form id="lobbyid_input_form" role="form" style="margin-top: 8px;">
		<div class="form-inline">
			<div class="form-horizontal input-append" style="height: 100%; vertical-align: top;">
				<div class="btn-group">
					<input id="lobbyidinput" class="form-control" type="text" style="width: 380px;">
					<span id="searchclear" class="glyphicon glyphicon-remove-circle"></span>
				</div>
				<button type="submit" class="btn btn-default">Convert</button>
			</div>
		</div>
	</form>
	<div id="lobbyidresults" style="margin: 10px 0;"></div>
	
	<hr style="padding: 10px 0"/>
	
	<h3>Servers</h3>
	
	<a href="servers.php">List of active MvM servers</a><br>
	<a href="server_statistics.php">Server statistics</a><br>
	
</div> <!-- container -->

<script>
$(document).ready(function() {
	$("#lobbyid_input_form").submit(function(e){
		convertLobbyId();
	    return false;
	});

	$('#show_instructions_link').on('click', function(e) {
		$('#instructions').slideDown(200);
		e.preventDefault();
		return false;
	});

	$('#searchclear').on('click', function(e) {
		$('#lobbyidinput').val('');
	});
});

function convertLobbyId() {
	var lobbyidinput = $('#lobbyidinput').val();

	if (!lobbyidinput) {
		alert('The lobby ID is missing.\n');
	} else {
		$.ajax({
			url: 'mvmlobbyAPI.php',
			type: 'POST',
			data: {
				'apitype': 'convertLobbyID',
				'lobbyidinput': lobbyidinput,
			}
		})
		.done(function(lobbyIDResults) {
			if ('lobby64id' in lobbyIDResults) {
				var lobbyId = lobbyIDResults.lobby64id;
				var html = '<strong>Your lobby ID is ' + lobbyId + '</strong><p>';
				html += 'Players can direct connect with this console command:<br>';
				html += 'connect_lobby ' + lobbyId + '<p>';
				html += 'Or share the following link:<br>';
				html += '<a href="steam://joinlobby/440/'+lobbyId+'">steam://joinlobby/440/'+lobbyId+'</a>';
				$('#lobbyidresults').empty().append(html);
			} else if ('error' in lobbyIDResults) {
				alert(lobbyIDResults.error);
			} else {
				alert("unexpected result");
			}
		})
		.fail(function(error) {
			alert('Failed to call the API.');
		});
	}
}
</script>

<?php include('footer.php'); ?>