<?php include HABARI_PATH . '/system/admin/header.php'; ?>

<div class="container navigator">
	<span class="search pct100"><input id="search" type="search" placeholder="Type and wait to search for any entry component" autosave="habaricontent" results="100" value=""></span>
</div>

<div class="container">

	<div class="navigator pct15 first" style="overflow:auto; height:400px;">
		
		<h3>Quick Searches</h3>
		<ul class="special_search pct100">
			<li><a href="#plugin">Plugins</a></li>
			<li><a href="#theme">Themes</a></li>
			<li><a href="#admin">Administration</a></li>
			<li><a href="#spam">Spam Filter</a></li>
			<li><a href="#service">3rdparty Services</a></li>
			<li><a href="#two column">Two Column</a></li>
			<li><a href="#blue">Blue</a></li>
			<li><a href="#pink">Pink</a></li>
		</ul>
		
	</div>
	
	<div class="navigator pct10">&nbsp;</div>
	
	<div class="items entries pct75 last" style="overflow:auto; height:400px;">
		<?php $theme->display( 'hpm_packages' ); ?>
	</div>

</div>

<div class="container clear">
	<div class="pct75">Powered by HPM <?php echo HPM::VERSION; ?> and a Pony</div>
	<div class="pct25 item">
		<ul class="dropbutton<?php echo ( HabariPackages::require_updates() )?' alert':''; ?>">
			<li><a href="<?php URL::out('admin', 'page=hpm&action=update'); ?>">Update Package List</a></li>
		</ul>
	</div>
</div>

<script type="text/javascript">
liveSearch.search= function() {
	spinner.start();

	$.post(
		'<?php echo URL::get('auth_ajax', array('context' => 'hpm_packages')) ?>',
		'&search=' + liveSearch.input.val(),
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
