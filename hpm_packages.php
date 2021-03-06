<?php foreach ( $packages as $package) : ?>
		<div class="item clear" id="package_<?php echo $package->id; ?>">
			<div class="head clear">
				<?php echo "{$package->name} {$package->version}"; ?>
			</div>
			<span class="content"><?php echo $package->description ?></span>
			<?php if ( !$package->is_compatible() ) : ?>
			<div class="content"><?php echo "{$package->name} {$package->version} is not compatible with Habari " . Version::get_habariversion(); ?></div>
			<?php endif; ?>
			
			<ul class="dropbutton<?php echo ($package->status == 'upgrade' || !$package->is_compatible())?' alert':''; ?>">
				<?php if ( !$package->is_compatible() ) : ?>
				<li>not compatible!</li>
				<?php elseif ( $package->status == 'installed' ) : ?>
				<li><a href=" <?php Site::out_url('admin') ?>/hpm?action=uninstall&guid=<?php echo $package->guid ?>">uninstall</a></li>
				<?php elseif ( $package->status == 'upgrade' ) : ?>
				<li><a href=" <?php Site::out_url('admin') ?>/hpm?action=upgrade&guid=<?php echo $package->guid ?>">upgrade</a></li>
				<?php else : ?>
				<li><a href=" <?php Site::out_url('admin') ?>/hpm?action=install&guid=<?php echo $package->guid ?>">install</a></li>
				<?php endif; ?>
			</ul>
		</div>
<?php endforeach; ?>
