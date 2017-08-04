<div id="serverDetailsModal" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
				<h4 class="modal-title" id="current_mvm_mission_name" style="font-size: 22px; color: #060;"></h4>
			</div>
			<div class="modal-body">
				<div id="serverDetailsLoadingDialog" style="height: 240px; width: 100%;">
					<img src="images/ajax-loader.gif" />
				</div>
				<table id="serverDetailsTable" class="table" style="display: none;">
					<tr><th class="no-border">Player Name*</th><th class="no-border">Time</th><th class="no-border">Kills</th><th class="no-border">Steam name search</th></tr>
				</table>
				<span style="font-size: 10px">*** Player Name link is a best guess based on name, it may not be accurate.</span><br/>
				<?php if (isset($user) && isset($player) && $user->id == $player->id) : ?>
				<span style="font-size: 10px;">Click <a href="#" onclick="$('#statusInstructions').slideToggle(); return false;">here</a> to get tour data on all players.</span>
				<div id="statusInstructions" style="display: none;">
				<hr/>
				1) Type <strong>status</strong> in your console.<br/>
				2) Copy all of the ID's (does not have to be an exact area, just get all the ID's you want in the highlighted area)<br/>
				3) Paste into <a href="<?php echo HTTP_ROOT.'search.php'; ?>"> <?php echo HTTP_ROOT.'search.php'; ?></a><br/>
				</div>	<!-- /statusInstructions -->
				<?php endif; ?>
			</div>
			<div class="modal-footer">
				<button id="serverDetailsModalRefresh" type="button" class="btn btn-success">Refresh</button>
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
function displayServerInfo(ipport, current_mvm_mission_name, mission_wiki_url) {
	var $modal = $('#serverDetailsModal');
	var $serverDetailsLoadingDialog = $('#serverDetailsLoadingDialog');
	var $serverDetailsTable = $('#serverDetailsTable');

	if (!$modal.hasClass('in')) { $modal.modal('show') };
	$serverDetailsLoadingDialog.show();
	$serverDetailsTable.hide();

	var missionHTML = current_mvm_mission_name;
	if (mission_wiki_url) {
		missionHTML += ' <a href="'+mission_wiki_url+'" style="font-size: 18px" target=_blank>(wiki)</a>'; 
	}
	$('#current_mvm_mission_name').html(missionHTML);


	$.ajax({
		type: 'GET',
		url: 'mvmlobbyAPI.php',
		data: {
			'apitype': 'serverPlayerList',
			'ipport': ipport
		}
	})
	.fail(function(error) {
	})
	.done(function(jsonResponse) {
		var $table = $('#serverDetailsTable');
		$table.find('tr[playerdata]').remove();
		
		// var playerArray = JSON.parse(jsonResponse);
		var playerArray = jsonResponse;
		var html = '';
		
		for (var i=0; i<playerArray.length; i++) {
			var player = playerArray[i];	// name, time, score, steamid
			if (player.steamid) {
				nameHTML = '<a href="profile/'+player.steamid+'">'+player.name+'</a>';
			} else {
				nameHTML = player.name;
			}
			var steamSearch = '<a href="http://steamcommunity.com/search/users/#filter=users&text='+encodeURIComponent(player.name)+'" target=_blank>Name search</a>'; 
			html += '<tr playerdata=1><td>'+nameHTML+'</td><td>'+player.time+'</td><td>'+player.score+'</td><td>'+steamSearch+'</td></tr>';
		}
		$table.append(html);
		$serverDetailsLoadingDialog.hide();
		$serverDetailsTable.show();
		$refreshButton = $('#serverDetailsModalRefresh');
		$refreshButton.off('click');
		$refreshButton.on('click', function() { displayServerInfo(ipport); });
	});
}
</script>