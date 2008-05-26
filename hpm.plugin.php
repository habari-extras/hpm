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
			'author' => 'Habari Community',
			'license' => 'Apache License 2.0',
		);
	}
	
	public function action_init()
	{
		Plugins::act( 'hpm_init' );
		
		$this->add_template( 'hpm', dirname(__FILE__) . '/view.php' );
		$this->add_template( 'hpm_packages', dirname(__FILE__) . '/hpm_packages.php' );
	}

	public function action_plugin_activation( $file )
	{
		if ( $file == str_replace( '\\','/', $this->get_file() ) ) {
			DB::register_table('package_repos');
			DB::register_table('packages');
			DB::query("DROP TABLE " . DB::table('package_repos') );
			DB::query("DROP TABLE " . DB::table('packages') );
			switch( DB::get_driver_name() ) {
				case 'sqlite':
					$schema= 'CREATE TABLE ' . DB::table('packages') . ' (
						id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
						name VARCHAR(255) NOT NULL,
						guid VARCHAR(255) NOT NULL,
						version VARCHAR(255) NOT NULL,
						description LONGTEXT ,
						author VARCHAR(255) ,
						author_url VARCHAR(255) ,
						max_habari_version VARCHAR(255) ,
						min_habari_version VARCHAR(255) ,
						archive_md5 VARCHAR(255) ,
						archive_url VARCHAR(255) ,
						archive LONGTEXT ,
						type VARCHAR(255) ,
						status VARCHAR(255) ,
						requires VARCHAR(255) ,
						provides VARCHAR(255) ,
						recomends VARCHAR(255) ,
						signature VARCHAR(255) ,
						install_profile LONGTEXT
					);
					CREATE TABLE  ' . DB::table('package_repos') . ' (
						id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
						name VARCHAR(255) NOT NULL,
						url VARCHAR(255) NOT NULL,
						browser_url VARCHAR(255) NOT NULL,
						description TEXT NOT NULL,
						signature VARCHAR(255) NOT NULL,
						version VARCHAR(255) NOT NULL,
						owner VARCHAR(255) NOT NULL
					);';
				break;
				
				default:
				case 'mysql':
					$schema= 'CREATE TABLE ' . DB::table('packages') . ' (
						id INT UNSIGNED NOT NULL AUTO_INCREMENT,
						name VARCHAR(255) NOT NULL,
						guid VARCHAR(255) NOT NULL,
						version VARCHAR(255) NOT NULL,
						description LONGTEXT ,
						author VARCHAR(255) ,
						author_url VARCHAR(255) ,
						max_habari_version VARCHAR(255) ,
						min_habari_version VARCHAR(255) ,
						archive_md5 VARCHAR(255) ,
						archive_url VARCHAR(255) ,
						archive LONGTEXT ,
						type VARCHAR(255) ,
						status VARCHAR(255) ,
						requires VARCHAR(255) ,
						provides VARCHAR(255) ,
						recomends VARCHAR(255) ,
						signature VARCHAR(255) ,
						install_profile LONGTEXT,
						PRIMARY KEY (id)
					);
					CREATE TABLE  ' . DB::table('package_repos') . ' (
						id INT UNSIGNED NOT NULL AUTO_INCREMENT,
						name VARCHAR(255) NOT NULL,
						url VARCHAR(255) NOT NULL,
						browser_url VARCHAR(255) NOT NULL,
						description TEXT NOT NULL,
						signature VARCHAR(255) NOT NULL,
						version VARCHAR(255) NOT NULL,
						owner VARCHAR(255) NOT NULL,
						PRIMARY KEY (id)
					);';
				break;
			}
			if ( DB::dbdelta( $schema ) ) {
				Session::notice( 'Updated the HPM database tables.' );
			}
			
			// insert default repo
			DB::query("INSERT INTO " . DB::table('package_repos') . " (name, url, browser_url, description, owner, signature, version) VALUES('Wicket', 'http://mattread.com/packages/', 'null', 'A package repo for testing purposes only!', 'Matt Read', 'Awsom3', '1');");
		}
	}

	public function filter_adminhandler_post_loadplugins_main_menu( $menus )
	{
		$menus['hpm'] =  array( 'url' => URL::get( 'admin', 'page=hpm'), 'title' => _t('Habari Package Manager'), 'text' => _t('HPM'), 'selected' => false, 'hotkey' => 'H' );
		return $menus;
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

		if ( HabariPackageRepo::require_updates() ) {
			Session::notice( "The packages list is out of date." );
		}
		
		$theme->packages= DB::get_results('SELECT * FROM ' . DB::table('packages') . ' LIMIT 20', array(), 'HabariPackage');
		$theme->types= HabariPackages::list_package_types();
		$theme->out= $theme->fetch('hpm_packages');
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
			$package= HabariPackages::install( $handler->handler_vars['guid'] );
			Session::notice( "{$package->name} {$package->version} was installed." );
			if ( $package->readme_doc ) {
				$out= '<h2>'. $package->name .'</h2></h2><h3>Readme Instructions</h3><pre style="overflow:auto; border:1px dotted #ccc;">' . $package->readme_doc
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
			$package= HabariPackages::remove( $handler->handler_vars['guid'] );
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

	public function action_auth_ajax_hpm_packages( $handler )
	{
		$theme= Themes::create( 'admin', 'RawPHPEngine', dirname(__FILE__) .'/' );
		$search= isset( $handler->handler_vars['search'] ) ? $handler->handler_vars['search'] : '';
		$where= "(name LIKE CONCAT('%',?,'%') OR description LIKE CONCAT('%',?,'%'))";
		$theme->packages= DB::get_results('SELECT * FROM ' . DB::table('packages') . " WHERE $where LIMIT 30", array($search, $search), 'HabariPackage');
		echo json_encode( array( 'items' =>  $theme->fetch('hpm_packages') ) );
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
		
		PackageArchive::register_archive_reader( 'application/x-zip', 'ZipReader' );
		PackageArchive::register_archive_reader( 'application/zip', 'ZipReader' );
		PackageArchive::register_archive_reader( 'application/x-tar', 'TarReader' );
		PackageArchive::register_archive_reader( 'application/tar', 'TarReader' );
		PackageArchive::register_archive_reader( 'application/x-gzip', 'TarReader' );
		PackageArchive::register_archive_reader( 'text/plain', 'TxtReader' );
	}
}


?>
