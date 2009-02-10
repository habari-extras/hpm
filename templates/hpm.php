<?php include HABARI_PATH . '/system/admin/header.php'; ?>

<?php if (HabariPackages::require_updates()) : ?>
	<div class="container">
<span class="pct25">&nbsp;</span>
		<strong class="pct50"><a href="<?php URL::out('admin', 'page=hpm&action=update' . ($type?"&type=$type":'')); ?>">Update Package List</a></strong>
	</div>
<?php endif; ?>

<div class="container navigator">
		<div class="search pct100"><input id="search" type="search" placeholder="Type and wait to search for any entry component" autosave="habaricontent" results="100" value=""></div>

		<a class="pct50" href="#" onclick="hpm.previous(); return false;">&lt; previous</a>
		<a class="pct50" style="text-align:right;" href="#" onclick="hpm.next(); return false;">next &gt;</a>
</div>

<div id="comments" class="container manage comments">
	<?php $theme->display( 'hpm_packages' ); ?>
</div>


<script type="text/javascript">
itemManage.fetchURL = "<?php echo URL::get('auth_ajax', array('context' => 'hpm_packages')) ?>";
itemManage.fetchReplace = $('#comments');

var hpm = {
	offset: 0,
	limit: 20,

	next: function() {
		hpm.offset += hpm.limit;
		itemManage.fetch(hpm.offset);
	},

	previous: function() {
		if (hpm.offset != 0) {
			hpm.offset -= hpm.limit;
			itemManage.fetch(hpm.offset);
		}
	}
}
</script>


<?php include HABARI_PATH . '/system/admin/footer.php'; ?>
