<?php

class HPM extends Plugin
{
	const VERSION = '0.2';
	const DB_VERSION = 001;

	public function action_init()
	{
	}

	public function action_update_check()
  	{
    	Update::add( 'hpm', '693E59D6-2B5F-11DD-A23A-9E6C56D89593',  $this->info->version );
  	}

	/**
	 * @todo fix the tokens
	 */
	public function action_plugin_activation()
	{
			# create default access tokens for: 'system', 'plugin', 'theme', 'class'
			ACL::create_token( 'install_new_system', _t('Install System Updates', 'hpm'), 'hpm', false );
			ACL::create_token( 'install_new_plugin', _t('Install New Plugins', 'hpm'), 'hpm', false );
			ACL::create_token( 'install_new_theme', _t('Install New Themes', 'hpm'), 'hpm', false );
			ACL::create_token( 'install_new_class', _t('Install New Classes', 'hpm'), 'hpm', false );
	}
	public function action_plugin_deactivation( $file )
	{
			# delete default access tokens for: 'system', 'plugin', 'theme', 'class'
			ACL::destroy_token( 'install_new_system' );
			ACL::destroy_token( 'install_new_plugin' );
			ACL::destroy_token( 'install_new_theme' );
			ACL::destroy_token( 'install_new_class' );
	}

	public function act_install( $handler, $theme )
	{
		try {
			$addons = json_decode(stripslashes($_POST['payload']));
			foreach ( $addons as $addon ) {
				$package = new HabariPackage($addon);
				$package->install();
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
