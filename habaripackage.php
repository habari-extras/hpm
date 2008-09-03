<?php

/*

package types will be:
	- plugin
	- theme
	- system

packages will depend on "hooks" and satisfy "hooks"

need a clean_up() routine to clean tmp files serialize certain feilds and call update() to save.

*/

class HabariPackage extends QueryRecord
{
	public $readme_doc;
	private $archive;
	
	public static function default_fields()
	{
		return array(
			'id' => 0,
			'name' => '',
			'guid' => '',
			'version' => '',
			'description' => '',
			'author' => '',
			'author_url' => '',
			'habari_version' => '',
			'archive_md5' => '',
			'archive_url' => '',
			'type' => '',
			'status' => '',
			'requires' => '',
			'provides' => '',
			'recomends' => '',
			'tags' => '',
			'install_profile' => ''
		);
	}
	
	public static function get( $guid )
	{
		$package= DB::get_row( 'SELECT * FROM ' . DB::table('packages') . ' WHERE guid = ?',
			array( $guid ), 'HabariPackage' );
		
		return $package;
	}
	
	public function __construct( $paramarray = array() )
	{
		$this->fields = array_merge(
			self::default_fields(),
			$this->fields 
		);
		
		parent::__construct( Utils::get_params( $paramarray ) );
		$this->exclude_fields( 'id' );
	}
	
	public function install()
	{
		if ( ! $this->is_compatible() ) {
			throw new Exception( "{$this->name} {$this->version} is not compatible with Habari " . Version::get_habariversion() );
		}
		$this->get_archive();
		$this->build_install_profile();
		$this->unpack_files();
		
		$this->status= 'installed';
		$this->trigger_hooks( 'install' );
		
		$this->install_profile= serialize( $this->install_profile );
		$this->update();
	}
	
	public function remove()
	{
		$this->install_profile = unserialize( $this->install_profile );
		$this->trigger_hooks( 'remove' );
		
		$dirs= array();
		foreach ( array_reverse($this->install_profile) as $file => $location ) {
			$location= HABARI_PATH . '/' . ltrim( $location, '/\\' );
			if ( is_dir($location) ) {
				$dirs[]= $location;
			}
			elseif ( is_file( $location ) ) {
				if ( !@unlink( $location ) ) {
					Session::error( "could not remove file, $location" );
				}
				@rmdir( dirname($location) ); // DANGER WILL ROBINSON!!
			}
		}
		foreach ( $dirs as $dir ) {
			rmdir( $dir );
		}
		$this->install_profile= '';
		$this->status= '';
		$this->update();
	}
	
	public function upgrade()
	{
		if ( ! $this->is_compatible() ) {
			throw new Exception( "{$this->name} {$this->version} is not compatible with Habari " . Version::get_habariversion() );
		}
		$this->install_profile = unserialize( $this->install_profile );
		$current_install_profile = $this->install_profile;
		
		$bad_perms = array_filter(
			array_map(
				create_function( '$a', 'return ! is_writable(HABARI_PATH . "/$a");'),
				$current_install_profile
				)
			);
		if ( $bad_perms ) {
			throw new Exception( "incorrect permission settings. Please make all files for {$this->name} writeable by the server, and try again." );
		}
		
		// move the current version to tmp dir
		$tmp_dir = HabariPackages::tempdir();
		$dirs = array();
		foreach( $current_install_profile as $file => $location ) {
			if ( is_dir( HABARI_PATH . $location ) ) {
				$dirs[] = HABARI_PATH . $location;
				continue;
			}
			else {
				mkdir( dirname( $tmp_dir . $location ), 0777, true );
				rename( HABARI_PATH . $location, $tmp_dir . $location );
			}
		}
		foreach( $dirs as $dir ) {
			@rmdir( $dir );
		}
		
		// try and install new version
		try {
			$this->get_archive();
			$this->build_install_profile();
			$this->unpack_files();
		
			$this->status= 'installed';
			$this->trigger_hooks( 'upgrade' );
			
			$this->install_profile= serialize( $this->install_profile );
			$this->update();
		}
		// revert to old version if new install failed
		catch( Exception $e ) {
			foreach( $current_install_profile as $file => $location ) {
				rename( $tmp_dir . '/' . $location, HABARI_PATH . '/' . $location );
			}
			/**
			 * @todo this needs to be a recursive rmdir
			 */
			@rmdir( $tmp_dir );
			throw new Exception( $e->getMessage() );
		}
		/**
		 * @todo this needs to be a recursive rmdir
		 */
		// clean up tmp files
		@rmdir( $tmp_dir );
	}
	
	private function get_archive()
	{
		$this->archive = new PackageArchive( $this->archive_url );
		$this->archive->fetch();
		
		if ( $this->archive->md5 != $this->archive_md5 ) {
			throw new Exception( "Archive MD5 ({$this->archive->md5}) at {$this->archive_url} does
				 not match the package MD5 ({$this->archive_md5}). Archive may be corrupt." );
		}
	}
	
	private function unpack_files()
	{
		foreach ( $this->archive->get_file_list() as $file ) {
			if ( array_key_exists( $file, $this->install_profile ) ) {
				$this->archive->unpack( $file, HABARI_PATH . '/' . $this->install_profile[$file], 0777 );
			}
			else {
				//log files that were not installed
			}
		}
	}
	
	private function build_install_profile()
	{
		if ( ! $this->archive->get_file_list() ) {
			throw new Exception( "Archive does not contain any files" );
		}
		
		$install_profile= array();
		foreach ( $this->archive->get_file_list() as $file ) {
			if ( basename($file) == 'README' ) {
				$this->readme_doc=  $this->archive->read_file($file);
			}
			if ( strpos( $file, '__MACOSX' ) === 0 ) {
				// stoopid mac users!
				continue;
			}
			
			$install_profile[$file]= HabariPackages::type_location( $this->type ) . '/' . $file;
		}
		
		$this->install_profile= $install_profile;
	}
	
	private function trigger_hooks( $hook )
	{
		switch ( $this->type ) {
			case 'plugin':
				foreach( $this->install_profile as $file => $install_location ) {
					if ( strpos( basename($file), '.plugin.php' ) !== false ) {
						$plugin_file = HABARI_PATH . $install_location;
					}
				}
				if ( isset( $plugin_file ) ) {
					switch ( $hook ) {
						case 'install':
							Plugins::activate_plugin( $plugin_file );
							Session::notice( "{$this->name} {$this->version} Activated." );
						break;
						case 'remove':
							Plugins::deactivate_plugin( $plugin_file );
							Session::notice( "{$this->name} {$this->version} Dectivated." );
						break;
						case 'upgrade':
							Plugins::act('plugin_upgrade', $plugin_file); // For the plugin to upgrade itself
							Plugins::act('plugin_upgraded', $plugin_file); // For other plugins to react to a plugin upgrade
							Session::notice( "{$this->name} {$this->version} Upgraded." );
						break;
					}
				}
			break;
			
			case 'theme':
				// there are no activation/deactivation hooks for themes
			break;
			
			case 'system':
				// there are no activation/deactivation hooks for system
			break;
		}
	}
	
	public function is_compatible()
	{
		return HabariPackages::is_compatible( $this->habari_version );
	}
	
	/**
	 * Saves a new package to the packages table
	 */
	public function insert()
	{
		return parent::insertRecord( DB::table('packages') );
	}
	
	/**
	 * Updates an existing package to the packages table
	 */
	public function update()
	{
		return parent::updateRecord( DB::table('packages'), array('id'=>$this->id) );
	}
	
	/**
	 * Deletes an existing package
	 */
	public function delete()
	{
		return parent::deleteRecord( DB::table('packages'), array('id'=>$this->id) );
	}
}

?>
