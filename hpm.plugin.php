<?php

class HPM extends Plugin
{
	const VERSION = '0.2';
	const DB_VERSION = 001;
	
	function info()
	{
		return array (
			'name' => 'HPM',
			'version' => '0.2.alpha',
			'author' => 'Habari Community',
			'license' => 'Apache License 2.0',
		);
	}
	
	public function action_init()
	{
	}
	
	public function action_update_check()
  	{
    	Update::add( 'hpm', '693E59D6-2B5F-11DD-A23A-9E6C56D89593',  $this->info->version );
  	}
	
	/**
	 * @todo fix this schema!!!!
	 */
	public function action_plugin_activation( $file )
	{
		if ( $file == str_replace( '\\','/', $this->get_file() ) ) {
			DB::register_table('packages');
			
			switch( DB::get_driver_name() ) {
				case 'sqlite':
					$schema = 'CREATE TABLE ' . DB::table('packages') . ' (
						id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
						name VARCHAR(255) NOT NULL,
						guid VARCHAR(255) NOT NULL,
						version VARCHAR(255) NOT NULL,
						description TEXT ,
						author VARCHAR(255) ,
						author_url VARCHAR(255) ,
						habari_version VARCHAR(255) ,
						archive_md5 VARCHAR(255) ,
						archive_url VARCHAR(255) ,
						type VARCHAR(255) ,
						status VARCHAR(255) ,
						requires VARCHAR(255) ,
						provides VARCHAR(255) ,
						recomends VARCHAR(255) ,
						tags TEXT ,
						install_profile LONGTEXT
					);';
				break;
				
				default:
				case 'mysql':
					$schema = 'CREATE TABLE ' . DB::table('packages') . ' (
						id INT UNSIGNED NOT NULL AUTO_INCREMENT,
						name VARCHAR(255) NOT NULL,
						guid VARCHAR(255) NOT NULL,
						version VARCHAR(255) NOT NULL,
						description TEXT ,
						author VARCHAR(255) ,
						author_url VARCHAR(255) ,
						habari_version VARCHAR(255) ,
						archive_md5 VARCHAR(255) ,
						archive_url VARCHAR(255) ,
						type VARCHAR(255) ,
						status VARCHAR(255) ,
						requires VARCHAR(255) ,
						provides VARCHAR(255) ,
						recomends VARCHAR(255) ,
						tags TEXT ,
						install_profile LONGTEXT,
						PRIMARY KEY (id)
					)
					CHARACTER SET ' . MYSQL_CHAR_SET . ' ;';
				break;
			}
			if ( DB::dbdelta( $schema ) ) {
				Session::notice( 'Updated the HPM database tables.' );
			}
			Options::set( 'hpm__last_update', 1 );
			Options::set( 'hpm__repos', 'http://habariproject.org/en/packages' );
		}
	}
	public function action_plugin_deactivation( $file )
	{
		if ( $file == str_replace( '\\','/', $this->get_file() ) ) {
			DB::register_table('packages');
			DB::register_table('package_repos');
			
			DB::query( 'DROP TABLE IF EXISTS {packages} ' );
			DB::query( 'DROP TABLE IF EXISTS {package_repos} ' );
		}
	}
	
	public function filter_plugin_config( $actions, $plugin_id )
	{
		if ( $plugin_id == $this->plugin_id() ) {
			$actions[]= _t('Add Sources', 'hpm');
		}
		return $actions;
	}
	
	public function action_plugin_ui( $plugin_id, $action )
	{
		if ( $plugin_id == $this->plugin_id() ) {
			switch ( $action ) {
				case _t('Add Sources', 'hpm') :
					$ui = new FormUI( 'hpm' );
					
					$api_key = $ui->append( 'textarea', 'repos', 'option:hpm__repos', _t('HPM Repositories (comma seperated): ', 'hpm') );
					$api_key->add_validator( 'validate_required' );
					
					$ui->append( 'submit', 'save', _t( 'Save', 'hpm' ) );
					$ui->set_option( 'success_message', _t( 'Configuration saved', 'hpm' ) );
					$ui->out();
					break;
			}
		}
	}

	public function filter_adminhandler_post_loadplugins_main_menu( $menus )
	{
		$menus['hpm'] =  array( 'url' => URL::get( 'admin', 'page=hpm'), 'title' => _t('Habari Package Manager'), 'text' => _t('HPM'), 'selected' => false, 'hotkey' => 'H' );
		return $menus;
	}
	
	public function action_admin_theme_get_hpm( $handler, $theme )
	{
		Plugins::act( 'hpm_init' );
		
		if ( isset( $handler->handler_vars['action'] ) ) {
			$action = $handler->handler_vars['action'];
			if ( method_exists( $this, "act_$action" ) ) {
				$this->{"act_$action"}( $handler, $theme );
			}
			else {
				Plugins::act( "hpm_act_$action", $handler, $theme );
			}
		}

		if ( HabariPackages::require_updates() ) {
			Session::notice( "The packages list is out of date. " );
		}
		
		if ( !is_writable( HabariPackages::tempdir() ) || !is_writable( HABARI_PATH . '/3rdparty' ) ) {
			$theme->notice = 'permissions';
			$theme->display('hpm_notice');
			exit;
		}
		
		$theme->packages = DB::get_results('SELECT * FROM ' . DB::table('packages') . ' LIMIT 20', array(), 'HabariPackage');
		$theme->display('hpm');
		exit;
	}
	
	public function act_update( $handler, $theme )
	{
		try {
			HabariPackages::update();
			Session::notice( 'Package List is now up to date.' );
		}
		catch (Exception $e) {
			Session::error( 'Could not update Package List' );
			if ( DEBUG ) {
				Utils::debug($e);
			}
		}
	}
	
	public function act_upgrade( $handler, $theme )
	{
		try {
			$package = HabariPackages::upgrade( $handler->handler_vars['guid'] );
			Session::notice( "{$package->name} was upgraded to version {$package->version}." );
			if ( $package->readme_doc ) {
				$theme->notice = 'readme';
				$theme->package = $package;
				$theme->display('hpm_notice');
				exit;
			}
		}
		catch (Exception $e) {
			Session::error( 'Could not complete upgrade: '.  $e->getMessage() );
			if ( DEBUG ) {
				Utils::debug($e);
			}
		}
	}
	
	public function act_install( $handler, $theme )
	{
		try {
			$package = HabariPackages::install( $handler->handler_vars['guid'] );
			Session::notice( "{$package->name} {$package->version} was installed." );
			if ( $package->readme_doc ) {
				$theme->notice = 'readme';
				$theme->package = $package;
				$theme->display('hpm_notice');
				exit;
			}
		}
		catch (Exception $e) {
			Session::error( 'Could not complete install: '.  $e->getMessage() );
			if ( DEBUG ) {
				Utils::debug($e);
			}
		}
	}

	public function act_uninstall( $handler, $theme )
	{
		try {
			$package = HabariPackages::remove( $handler->handler_vars['guid'] );
			Session::notice( "{$package->name} {$package->version} was uninstalled." );
		}
		catch (Exception $e) {
			Session::error( 'Could not complete uninstall: '.  $e->getMessage() );
			if ( DEBUG ) {
				Utils::debug($e);
			}
		}
	}

	public function action_auth_ajax_hpm_packages( $handler )
	{
		Plugins::act('hpm_init');
		$theme = Themes::create( 'admin', 'RawPHPEngine', dirname(__FILE__) .'/' );
		$search = isset( $handler->handler_vars['search'] ) ? $handler->handler_vars['search'] : '';
		$search = explode( ' ', $search );
		$where = array();
		$vals = array();
		
		foreach ( $search as $term ) {
			$where[] = "(name LIKE CONCAT('%',?,'%') OR description LIKE CONCAT('%',?,'%') OR tags LIKE CONCAT('%',?,'%') OR type LIKE CONCAT('%',?,'%'))";
			$vals = array_pad( $vals, count($vals) + 4, $term );
		}
		$theme->packages = DB::get_results('SELECT * FROM ' . DB::table('packages') . " WHERE " . implode( ' AND ', $where ), $vals, 'HabariPackage');
		echo json_encode( array( 'items' =>  $theme->fetch('hpm_packages') ) );
	}
	
	public function action_hpm_init()
	{
		DB::register_table('packages');
		
		include 'habaripackage.php';
		include 'habaripackages.php';
		include 'packagearchive.php';
		include 'archivereader.php';
		include 'tarreader.php';
		include 'zipreader.php';
		include 'txtreader.php';
		
		PackageArchive::register_archive_reader( 'application/x-zip', 'ZipReader' );
		PackageArchive::register_archive_reader( 'application/zip', 'ZipReader' );
		PackageArchive::register_archive_reader( 'application/x-tar', 'TarReader' );
		PackageArchive::register_archive_reader( 'application/tar', 'TarReader' );
		PackageArchive::register_archive_reader( 'application/x-gzip', 'TarReader' );
		PackageArchive::register_archive_reader( 'text/plain', 'TxtReader' );
		PackageArchive::register_archive_reader( 'text/php', 'TxtReader' );
		PackageArchive::register_archive_reader( 'application/php', 'TxtReader' );
		
		$this->add_template( 'hpm', dirname(__FILE__) . '/templates/hpm.php' );
		$this->add_template( 'hpm_packages', dirname(__FILE__) . '/templates/hpm_packages.php' );
		$this->add_template( 'hpm_notice', dirname(__FILE__) . '/templates/hpm_notice.php' );
	}
}


?>
