<div id="wrap_rating">
	<div id="wrap_pros" class='pros'>
		<?php
		$pros = get_post_custom_values("pros");
		if(!empty($pros)){
			echo "<ul>";
			foreach ($pros as $key => $value) {
				echo "<li>" .$value. "</li>";
			}
			echo "</ul>";
		}
		?>
	</div>
	<div id="wrap_graph">

		<?php
		$note = get_post_custom_values("note");
		if(isset($note[0]))
		{
			echo "<input type='text' value='".$note[0]."' class='dial' data-skin='tron'>";
		}
		?>
		<script>
		jQuery(function() {
			jQuery(".dial").knob({
				'readOnly' : true,
				'fgColor' : "#3B3B3B",
				'thickness' : ".3"
			});
		});
		</script>
	</div>
	<div id="wrap_cons" class='cons'>
		<?php
		$cons = get_post_custom_values("cons");
		if(!empty($cons)){
			echo "<ul>";
			foreach ($cons as $key => $value) {
				echo "<li>" .$value. "</li>";
			}
			echo "</ul>";
		}
		?>
	</div>
</div>