<?php foreach ( $packages as $package) : ?>
		<div class="item clear" id="package_<?php echo $package->package_name; ?>">
			<div class="head clear">
				<?php echo "{$package->name} {$package->version}"; ?>
			</div>
			<span class="content"><?php echo $package->description ?></span>
			<ul class="dropbutton<?php echo ($package->status == 'upgrade')?' alert':''; ?>">
				<?php if ( $package->status == 'installed' ) : ?>
				<li><a href=" <?php Site::out_url('admin') ?>/hpm?action=uninstall&name=<?php echo $package->package_name ?>">uninstall</a></li>
				<?php elseif ( $package->status == 'upgrade' ) : ?>
				<li><a href=" <?php Site::out_url('admin') ?>/hpm?action=upgrade&name=<?php echo $package->package_name ?>">upgrade</a></li>
				<?php else : ?>
				<li><a href=" <?php Site::out_url('admin') ?>/hpm?action=install&name=<?php echo $package->package_name ?>">install</a></li>
				<?php endif; ?>
			</ul>
		</div>
<?php endforeach; ?>
