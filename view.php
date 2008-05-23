<?php include HABARI_PATH . '/system/admin/header.php'; ?>

<div class="container navigator">
	<span class="search pct75"><input type="search" placeholder="Type and wait to search for any entry component" autosave="habaricontent" results="10" value=""></span>
	<div class="pct25 item"><ul class="dropbutton<?php echo ( HabariPackageRepo::require_updates() )?' alert':''; ?>"><li><a href="<?php echo Site::get_url('habari'); ?>/admin/hpm?action=update">Update Package List</a></li></ul></div>

</div>

<div class="container manage entries plugins">
	<?php echo $out; ?>

	
</div>

<script type="text/javascript">
liveSearch.search= function() {
	spinner.start();

	$.post(
		'<?php echo URL::get('auth_ajax', array('context' => 'hpm_packages')) ?>',
		'&search=' + liveSearch.input.val() + '&limit=20',
		function(json) {
			$('.entries').html(json.items);
			spinner.stop();
			itemManage.initItems();
			findChildren()
		},
		'json'
		);
};
</script>

<?php include HABARI_PATH . '/system/admin/footer.php'; ?>
