
	
	<!-- Latest compiled and minified JavaScript -->
	<script src="js/bootstrap.3.2.0.min.js"></script>
	<?php
		if (IS_PROD===TRUE) {
			echo '<script src="js/mvmlobby.min.js" type="text/javascript"></script>';
		} else { 
			echo '<script src="js/mvmlobby.js" type="text/javascript"></script>';
		}
	?>
	
	<?php if ($user != null) :?> 
	<script>
		$(document).ready(function() {

			var headerController = new PlayerHeaderController('<?php echo $user->steamid; ?>');
			headerController.refreshData();

 		});

	</script>
	<?php endif; ?>
	
</body>
</html>