<?php include HABARI_PATH . '/system/admin/header.php'; ?>

<div class="container navigator">
	<div class="pct85">
		<span class="search pct100"><input id="search" type="search" placeholder="Type and wait to search for any entry component" autosave="habaricontent" results="100" value=""></span>
		
		<span class="special_search pct100">
			<a href="#plugin">Plugins</a>
			<a href="#theme">Themes</a>
			<a href="#admin">Administration</a>
			<a href="#spam">Spam Filter</a>
			<a href="#service">3rdparty Services</a>
			<a href="#two column">Two Column</a>
			<a href="#blue">Blue</a>
			<a href="#pink">Pink</a>
		</span>
	</div>
	
	<div class="pct5">&nbsp;</div>
	
	<div class="pct10 item">
		<ul class="dropbutton <?php echo ( HabariPackages::require_updates() )?'alert':''; ?>">
			<li><a href="<?php URL::out('admin', 'page=hpm&action=update'); ?>">Update Package List</a></li>
		</ul>
	</div>
</div>

<div id="comments">
<div class="container items entries">
	<?php $theme->display( 'hpm_packages' ); ?>
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
