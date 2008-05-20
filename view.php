<?php include HABARI_PATH . '/system/admin/header.php'; ?>

<div class="container navigator">
	<span class="search pct75"><input type="search" placeholder="Type and wait to search for any entry component" autosave="habaricontent" results="10" value=""></span>
	<span class="pct25"><a href="<?php echo Site::get_url('habari'); ?>/admin/hpm?action=update">Update Package List</a></span>

</div>

<div class="container manage">
	
	<div class="pct100 clear">
		<?php echo $out; ?>
	</div>
	
</div>

<?php include HABARI_PATH . '/system/admin/footer.php'; ?>
