<?php include HABARI_PATH . '/system/admin/header.php'; ?>

<div class="container navigator">
	<div class="pct85">
		<span class="search pct100"><input id="search" type="search" placeholder="Type and wait to search for any entry component" autosave="habaricontent" results="100" value=""></span>

		<ul class="dropbutton special_search">
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

	<div class="pct5">&nbsp;</div>

	<div class="pct10 item">
		<ul class="dropbutton <?php echo ( HabariPackages::require_updates() )?'alert':''; ?>">
			<li><a href="<?php URL::out('admin', 'page=hpm&action=update'); ?>">Update Package List</a></li>
		</ul>
	</div>
</div>

<div id="comments" class="container manage comments">
	<?php $theme->display( 'hpm_packages' ); ?>
</div>

<script type="text/javascript">
itemManage.fetchURL = "<?php echo URL::get('auth_ajax', array('context' => 'hpm_packages')) ?>";
itemManage.fetchReplace = $('#comments');
</script>

<?php include HABARI_PATH . '/system/admin/footer.php'; ?>
