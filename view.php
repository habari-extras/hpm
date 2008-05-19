<div class="container">
	<hr>
	<?php if(Session::has_messages()) {Session::messages_out();} ?>
	
	<div>
	
		<div style="width:30%; float:left; border:1px solid #bbb;">
			<b>Package Type</b>
			<ul>
			<?php foreach ( $types as $type ) {
			
			echo "<li><a href='" . Site::get_url('habari') . "/hpm/type/$type'>$type</a></li>";
			
			} ?>
			</ul>
			
			<b>More</b>
			<ul>
				<li><a href="<?php echo Site::get_url('habari'); ?>/hpm?update_test=1">Update Package List</a></li>
				<li><a href="<?php echo URL::get('hpm_installdb'); ?>">Re-Install Database</a></li>
			</ul>
		</div>
		
		<div style="width:60%; float:right;">
		<?php echo $out; ?>
		</div>
		
		<br style="clear:both;" />
	</div>
	
</div>