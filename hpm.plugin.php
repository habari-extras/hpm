<?php

class HPM extends Plugin
{
	
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
		CRewriter::add_rule( 'hpm', '/^hpm\\/?$/i', 'hpm', 'HPMHandler', 'view', 3 );
		CRewriter::add_rule( 'hpm_type', '%^hpm/type/(?P<type>.+)(?:/page/(?P<page>\\d+))?[/]?$%i', 'hpm/type/{$type}(/page/{$page}/)', 'HPMHandler', 'view', 3 );
		CRewriter::add_rule( 'hpm_installdb', '%^hpm/installdb[/]?$%i', 'hpm/installdb', 'HPMHandler', 'installdb', 3 );
		CRewriter::add_rule( 'hpm_install', '%^hpm/install/(?P<name>.+)[/]?$%i', 'hpm/install/{$name}', 'HPMHandler', 'install', 3 );
		CRewriter::add_rule( 'hpm_remove', '%^hpm/remove/(?P<name>.+)[/]?$%i', 'hpm/remove/{$name}', 'HPMHandler', 'remove', 3 );
		CRewriter::add_rule( 'hpm_package', '%^hpm/package/(?P<name>.+)[/]?$%i', 'hpm/package/{$name}', 'HPMHandler', 'package', 3 );
		CRewriter::add_rule( 'hpm_server', '%^packages[/]?$%i', 'packages', 'HabariPackageRepo_Server', 'xmlrpc_call', 3 );
		
		include 'hpmhandler.php';
		include 'habaripackagerepo_server.php';
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
	
	public function filter_rewrite_rules( $rules )
	{
		/*$rules[] = new RewriteRule(array(
			'name' => 'hpm',
			'parse_regex' => '%^hpm/(?P<action>.+)?[/]?(?P<name>.+)?[/]?$%i',
			'build_str' => 'hpm/({$action}/)({$name})',
			'handler' => 'HPMHandler',
			'action' => '{$action}',
			'priority' => 2,
			'rule_class' => RewriteRule::RULE_PLUGIN,
			'is_active' => 1,
			'description' => 'Rewrite HPM Packages.'
		));
		
		$rules[] = new RewriteRule(array(
			'name' => 'hpm_server',
			'parse_regex' => '%^packages[/]?$%i',
			'build_str' => 'packages',
			'handler' => 'HabariPackageRepo_Server',
			'action' => 'xmlrpc_call',
			'priority' => 2,
			'rule_class' => RewriteRule::RULE_PLUGIN,
			'is_active' => 1,
			'description' => 'Rewrite for HPM Server.'
		));*/
		
		return $rules;
	}
}


?>