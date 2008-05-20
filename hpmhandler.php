<?php

/* NOTES

- for now we ignore windows DIRECTORY_SEPERATOR!!
- only install files into /3rdparty!

- verify min/max versions from server info. notice if NOK.
- get plugin archive (.tgz)
- verify md5's (from server, and generate from file). notice if NOK. (maybe refuse)
- read archive
- build install profile from info/archive.
- check existing files.
- write files to dirs based on install profile.
- grab README for display (maybe).

*/

class HPMHandler extends ActionHandler
{
	static $PACKAGES_PATH;
	const VERSION= '0.1';
	
	public function __construct()
	{
		/*$user= User::identify();
		if ( !$user ) {
			Session::add_to_set( 'login', $_SERVER['REQUEST_URI'], 'original' );
			Utils::redirect( URL::get( 'user', array( 'page' => 'login' ) ) );
			exit;
		}
		if ( !$user->can( 'admin' ) ) {
			die( _t( 'Permission denied.' ) );
		}
		$user->remember();*/
		
		Plugins::act('hpm_init');
	}
	
	public function act_installdb()
	{
		switch( DB::get_driver_name() ) {
			case 'sqlite':
				DB::dbdelta('CREATE TABLE ' . DB::table('packages') . ' (
					id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
					name VARCHAR(255) NOT NULL,
					package_name VARCHAR(255) NOT NULL,
					version VARCHAR(255) NOT NULL,
					description LONGTEXT ,
					author VARCHAR(255) ,
					author_url VARCHAR(255) ,
					max_habari_version VARCHAR(255) ,
					min_habari_version VARCHAR(255) ,
					archive_md5 VARCHAR(255) ,
					archive_url VARCHAR(255) ,
					archive LONGTEXT ,
					type SMALLINT UNSIGNED ,
					status VARCHAR(255) ,
					depends VARCHAR(255) ,
					provides VARCHAR(255) ,
					signature VARCHAR(255) ,
					install_profile LONGTEXT
				);');
				DB::dbdelta('CREATE TABLE  ' . DB::table('package_repos') . ' (
					id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
					name VARCHAR(255) NOT NULL,
					url VARCHAR(255) NOT NULL,
					browser_url VARCHAR(255) NOT NULL,
					description TEXT NOT NULL,
					signature VARCHAR(255) NOT NULL,
					version VARCHAR(255) NOT NULL,
					owner VARCHAR(255) NOT NULL
				);');
			break;
			
			default:
			case 'mysql':
				DB::dbdelta('CREATE TABLE ' . DB::table('packages') . ' (
					id INT UNSIGNED NOT NULL AUTO_INCREMENT,
					name VARCHAR(255) NOT NULL,
					package_name VARCHAR(255) NOT NULL,
					version VARCHAR(255) NOT NULL,
					description LONGTEXT ,
					author VARCHAR(255) ,
					author_url VARCHAR(255) ,
					max_habari_version VARCHAR(255) ,
					min_habari_version VARCHAR(255) ,
					archive_md5 VARCHAR(255) ,
					archive_url VARCHAR(255) ,
					archive LONGTEXT ,
					type SMALLINT UNSIGNED ,
					status VARCHAR(255) ,
					depends VARCHAR(255) ,
					provides VARCHAR(255) ,
					signature VARCHAR(255) ,
					install_profile LONGTEXT,
					PRIMARY KEY (id)
				);');
				DB::dbdelta('CREATE TABLE  ' . DB::table('package_repos') . ' (
					id INT UNSIGNED NOT NULL AUTO_INCREMENT,
					name VARCHAR(255) NOT NULL,
					url VARCHAR(255) NOT NULL,
					browser_url VARCHAR(255) NOT NULL,
					description TEXT NOT NULL,
					signature VARCHAR(255) NOT NULL,
					version VARCHAR(255) NOT NULL,
					owner VARCHAR(255) NOT NULL,
					PRIMARY KEY (id)
				);');
			break;
		}
		
		// insert default repo
		DB::query("INSERT INTO " . DB::table('package_repos') . " (name, url, browser_url, description, owner, signature, version) VALUES('Wicket', 'http://mattread.com/packages/', 'null', 'A package repo for testing purposes only!', 'Matt Read', 'Awsom3', '1');");
		
		
		$this->theme= Themes::create( 'hpm', 'RawPHPEngine', realpath(dirname(__FILE__)) . '/' );
		
		$this->theme->out= '<p>The Database was installed. Happy trails.</p>';
		
		$this->theme->types= HabariPackages::list_package_types();
		$this->theme->mainmenu= '';
		$this->theme->display('view');
	}
	
	public function act_view()
	{
		$this->theme= Themes::create( 'hpm', 'RawPHPEngine', realpath(dirname(__FILE__)) . '/' );
		
		if ( array_key_exists( 'update_test', $_GET ) ) {
			foreach ( HabariPackageRepo::repos() as $repo ) {
				try {
					$repo->update_packages();
					Session::notice( $repo->name . ' Repository is now up to date.<br>');
				}
				catch (Exception $e) {
					Session::error( $repo->name . ' Repository could not be updated. "' . $e->getMessage() . '"<br>');
					if ( DEBUG ) {
						$out= "<br />Generating debug info ...\n";
						Utils::debug($e);
					}
				}
			}
		}
		
		if ( array_key_exists('type', $this->handler_vars) ) {
			$packages= DB::get_results('SELECT * FROM ' . DB::table('packages') . '', array(), 'HabariPackage');
			$packagess= array();
			
			foreach ( $packages as $package) {
				$packagess[$package->type][]= $package;
			}
			$types= array_flip(HabariPackages::list_package_types());
			$type= $types[$this->handler_vars['type']];
			
			$out= '<h3>'. $this->handler_vars['type'] .'</h3>
			<table>';
			
			if ( array_key_exists($type, $packagess) ) {
			foreach ( $packagess[$type] as $package) {
				$name= strval($package->name);
				$style= ($package->status=='installed')?'background:#cdde87;':'';
				$out.= "
				<tr style=\"$style\">
					<td>$name</td>
					<td>{$package->version}</td>
					<td><a href='".URL::get('hpm_install',array('name'=>strval($package->package_name)))."'>install</a></td>
					<td><a href='".URL::get('hpm_package',array('name'=>strval($package->package_name)))."'>more info</a></td>
					<td><a href='".URL::get('hpm_remove',array('name'=>strval($package->package_name)))."'>remove</a></td>
				</tr>
				";
			}
			}
			$out.= '</table>';
		}
		else {
			$out= '<p>Choose package type on the side.</p>';
		}
		$this->theme->out= $out;
		
		
		$this->theme->types= HabariPackages::list_package_types();
		$this->theme->mainmenu= '';
		$this->theme->display('view');
	}
	
	public function act_install()
	{
		Plugins::act('hpm_install');
		
		self::$PACKAGES_PATH= HABARI_PATH . '/user/packages';
		
		if ( ! is_dir(self::$PACKAGES_PATH) ) {
			mkdir( self::$PACKAGES_PATH, 0777, true );
		}
		
		$this->theme= Themes::create( 'hpm', 'RawPHPEngine', realpath(dirname(__FILE__)) . '/' );
		
		try {
			$package= HabariPackages::install( $this->handler_vars['name'] );
			$out= "{$package->name} {$package->version} was installed.";
			if ( $package->readme_doc ) {
				$out.= '<h3>Readme Instructions</h3><pre style="overflow:auto; border:1px dotted #ccc;">' . $package->readme_doc . '</pre>';
			}
		}
		catch (Exception $e) {
			$out= 'Could not complete install: '.  $e->getMessage();
			if ( DEBUG ) {
				$out.= "<br />Generating debug info ...\n";
				Utils::debug($e);
			}
		}
		
		
		$this->theme->out= $out;
		$this->theme->types= HabariPackages::list_package_types();
		$this->theme->mainmenu= '';
		$this->theme->display('view');
	}
	
	public function act_remove()
	{	
		self::$PACKAGES_PATH= HABARI_PATH . '/user/packages';
		
		$this->theme= Themes::create( 'hpm', 'RawPHPEngine', realpath(dirname(__FILE__)) . '/' );
		
		try {
			$package= HabariPackages::remove( $this->handler_vars['name'] );
			$out= "{$package->name} {$package->version} was removes.";
			if ( $package->readme_doc ) {
				$out.= '<h3>Readme Instructions</h3><pre style="overflow:auto; border:1px dotted #ccc;">' . $package->readme_doc . '</pre>';
			}
		}
		catch (Exception $e) {
			$out= 'Could not remove package: '.  $e->getMessage();
			if ( DEBUG ) {
				$out.= "<br />Generating debug info ...\n";
				Utils::debug($e);
			}
		}
		
		
		$this->theme->out= $out;
		$this->theme->types= HabariPackages::list_package_types();
		$this->theme->mainmenu= '';
		$this->theme->display('view');
	}
	
	public function act_package()
	{
		$this->theme= Themes::create( 'hpm', 'RawPHPEngine', realpath(dirname(__FILE__)) . '/' );
		
		try {
			$package= HabariPackage::get( $this->handler_vars['name'] );
		}
		catch (Exception $e) {
			$out= 'Could not get package: '.  $e->getMessage();
			if ( DEBUG ) {
				$out.= "<br />Generating debug info ...\n";
				Utils::debug($e);
			}
		}
		
		$out= "<h3>{$package->name} ({$package->status})</h3>
			<p>{$package->description}</p>
			<p>by <a href='{$package->author_url}'>{$package->author}</a></p>
			";
		
		$this->theme->out= $out;
		$this->theme->types= HabariPackages::list_package_types();
		$this->theme->mainmenu= '';
		$this->theme->display('view');
	}
}


?>