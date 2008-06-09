<?php include HABARI_PATH . '/system/admin/header.php'; ?>

<div class="container">
	<?php switch ( $notice ) :
		case 'permissions' : ?>
			<h2>Incorrect Permission</h2>
			<p>HPM requires the temporary folder and install location to be writable by the server. Please make the following changes.</p>
			
			<?php if ( !is_writable( HabariPackages::tempnam() ) ) : ?>
				<h3>Temporary Folder</h3>
				<p>Please make <?php echo HabariPackages::tempnam(); ?> writable by the server</p>
				<p>...explain the process here...</p> 
			<?php endif; ?>
			
			<?php if ( !is_writable( HABARI_PATH . '/3rdparty' ) ) : ?>
				<h3>Install Folder</h3>
				<p>Please make <?php echo  HABARI_PATH . '/3rdparty'; ?> writable by the server</p>
				<p>...explain the process here...</p> 
			<?php endif; ?>
			
			<?php break; ?>
			
		<?php case 'readme' : ?>
			<h2><?php echo $package->name; ?></h2>
				<h3>Readme Instructions</h3>
					<pre><?php echo $package->readme_doc; ?></pre>
					<p>
						<a href="' . URL::get('admin', 'page=hpm' ) . '">Return to Packages List</a>';
					</p>
			<?php break; ?>
			
	<?php endswitch; ?>
</div>


<?php include HABARI_PATH . '/system/admin/footer.php'; ?>
