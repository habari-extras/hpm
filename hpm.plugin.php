<?php

class HPM extends Plugin
{
	static $PACKAGES_PATH;
	const VERSION= '0.1';
	
	function info()
	{
		return array (
			'name' => 'HPM',
			'version' => '0.1',
			'author' => 'Drunken Monkey Labs',
			'license' => 'Apache License 2.0',
		);
	}
	
	public function action_init()
	{
		Plugins::act( 'hpm_init' );
		
		$this->add_template( 'hpm', dirname(__FILE__) . '/view.php' );
	}

	public function action_admin_theme_get_hpm( $handler, $theme )
	{
		if ( isset( $handler->handler_vars['action'] ) ) {
			$action= $handler->handler_vars['action'];
			if ( method_exists( $this, "act_$action" ) ) {
				$this->{"act_$action"}( $handler, $theme );
			}
			else {
				Plugins::act( "hpm_$action" );
			}
		}

		$packages= DB::get_results('SELECT * FROM ' . DB::table('packages') . '', array(), 'HabariPackage');
		$out= '';
		
		foreach ( $packages as $package) {
			$name= strval($package->name);
			$style= ($package->status=='installed')?'background:#cdde87;':'';
			$out.= "
			<div class=\"item clear\">
				<div class=\"head clear\">
					$name {$package->version}
				</div>
				<span class=\"content\"> {$package->description} </span>
				<ul class=\"dropbutton\">
					<li><a href='". Site::get_url('admin') ."/hpm?action=install&name={$package->package_name}'>install</a></li>
					<li><a href='". Site::get_url('admin') ."/hpm?action=uninstall&name={$package->package_name}'>uninstall</a></li>
				</ul>
			</div>
			";
		}

		$theme->types= HabariPackages::list_package_types();
		$theme->out= $out;
		$theme->display('hpm');
		exit;
	}

	public function act_update( $handler, $theme )
	{
		foreach ( HabariPackageRepo::repos() as $repo ) {
			try {
				$repo->update_packages();
				Session::notice( $repo->name . ' Repository is now up to date.<br>' );
			}
			catch (Exception $e) {
				Session::error( $repo->name . ' Repository could not be updated. "' . $e->getMessage() . '"<br>' );
				if ( DEBUG ) {
					Session::notice( "<br />Generating debug info ...\n" );
					Utils::debug($e);
				}
			}
		}
	}

	public function act_install( $handler, $theme )
	{
		self::$PACKAGES_PATH= HABARI_PATH . '/user/packages';
		
		if ( ! is_dir(self::$PACKAGES_PATH) ) {
			mkdir( self::$PACKAGES_PATH, 0777, true );
		}
		
		try {
			$package= HabariPackages::install( $handler->handler_vars['name'] );
			Session::notice( "{$package->name} {$package->version} was installed." );
			if ( $package->readme_doc ) {
				$out= '<h3>Readme Instructions</h3><pre style="overflow:auto; border:1px dotted #ccc;">' . $package->readme_doc
					. '</pre><div><a href="/admin/hpm">Return to Packages List</a></div>';
				$theme->out= $out;
				$theme->display('hpm');
				exit;
			}
		}
		catch (Exception $e) {
			Session::error( 'Could not complete install: '.  $e->getMessage() );
			if ( DEBUG ) {
				Session::notice( "<br />Generating debug info ...\n" );
				Utils::debug($e);
			}
		}
	}

	public function act_uninstall( $handler, $theme )
	{
		self::$PACKAGES_PATH= HABARI_PATH . '/user/packages';
		
		try {
			$package= HabariPackages::remove( $handler->handler_vars['name'] );
			Session::notice( "{$package->name} {$package->version} was uninstalled." );
		}
		catch (Exception $e) {
			Session::error( 'Could not complete uninstall: '.  $e->getMessage() );
			if ( DEBUG ) {
				Session::notice( "<br />Generating debug info ...\n" );
				Utils::debug($e);
			}
		}
	}
	
	public function action_hpm_init()
	{
		DB::register_table('package_repos');
		DB::register_table('packages');
		
		include 'habaripackage.php';
		include 'packagearchive.php';
		include 'habaripackages.php';
		include 'archivereader.php';
		include 'dunzip2.php';
		include 'tarreader.php';
		include 'zipreader.php';
		include 'habaripackagerepo.php';
		include 'habaripackagerepo_server.php';
		include 'hrewriter.php';
		include 'hpmhandler.php';
		
		HRewriter::add_rule( 'hpm_server', '%^packages[/]?$%i', 'packages', 'HabariPackageRepo_Server', 'xmlrpc_call', 3 );
		
		PackageArchive::register_archive_reader( 'application/x-zip', 'ZipReader' );
		PackageArchive::register_archive_reader( 'application/zip', 'ZipReader' );
		PackageArchive::register_archive_reader( 'application/x-tar', 'TarReader' );
		PackageArchive::register_archive_reader( 'application/tar', 'TarReader' );
		PackageArchive::register_archive_reader( 'application/x-gzip', 'TarReader' );
		PackageArchive::register_archive_reader( 'text/plain', 'TxtReader' );
	}
}


?>
