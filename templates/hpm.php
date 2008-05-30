<?php include HABARI_PATH . '/system/admin/header.php'; ?>

<div class="container navigator">
	<span class="search pct75"><input type="search" placeholder="Type and wait to search for any entry component" autosave="habaricontent" results="10" value=""></span>
	<div class="pct25 item">
		<ul class="dropbutton<?php echo ( HabariPackages::require_updates() )?' alert':''; ?>">
			<li><a href="<?php URL::out('admin', 'page=hpm&action=update'); ?>">Update Package List</a></li>
		</ul>
	</div>

</div>

<div class="container wideitems entries plugins">
	<?php $theme->display( 'hpm_packages' ); ?>
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
