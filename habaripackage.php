<?php

/*

package types will be:
	- plugin
	- theme
	- system

packages will depend on "hooks" and satisfy "hooks"


feilds needed:
	package_guid ? do we need a unique id for packages?
		- right now we use package_name
	
	category
		- or maybe tag?
*/

class HabariPackage extends QueryRecord
{
	public $readme_doc;
	
	public static function default_fields()
	{
		return array(
			'id' => 0,
			'name' => '',
			'package_name' => '',
			'version' => '',
			'description' => '',
			'author' => '',
			'author_url' => '',
			'max_habari_version' => '',
			'min_habari_version' => '',
			'archive_md5' => '',
			'archive_url' => '',
			'type' => '',
			'status' => '',
			'depends' => '',
			'provides' => '',
			'signature' => '',
			'archive' => '',
			'install_profile' => ''
		);
	}
	
	public static function get( $package_name )
	{
		$package= DB::get_row( 'SELECT * FROM ' . DB::table('packages') . ' WHERE package_name = ?',
			array( $package_name ), 'HabariPackage' );
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
	
	private function get_archive()
	{
		if ( ! $this->archive = @ unserialize($this->archive) ) {
			$this->archive= new PackageArchive( $this->package_name, $this->archive_url );
			$this->archive->fetch();
			$this->archive->set_archive_reader();
		}
		$this->verify_md5sum();
	}
	
	public function install()
	{
		$this->get_archive();
		$this->build_install_profile();
		$this->check_existing_files();
		$this->install_files();
		
		$this->status= 'installed';
		$this->install_profile= serialize( $this->install_profile );
		$this->archive= serialize( $this->archive );
		$this->update();
	}
	
	public function remove()
	{
		$this->install_profile= unserialize( $this->install_profile );
		$dirs= array();
		foreach ( array_reverse($this->install_profile) as $file => $location ) {
			$location= HABARI_PATH . '/' . $location;
			if ( is_dir($location) ) {
				$dirs[]= $location;
			}
			else {
				unlink( $location );
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
	
	private function verify_md5sum()
	{
		if ( $this->archive->md5 != $this->archive_md5 ) {
			throw new Exception( "Archive MD5 ({$this->archive->md5}) at {$this->archive_url} does
				 not match the package MD5 ({$this->archive_md5}). Archive may be corrupt." );
		}
	}
	
	private function check_existing_files()
	{
		$msg='';
		foreach ( $this->install_profile as $file => $location ) {
			if ( file_exists($location) ) {
				$msg .= "$file already exists, overwriting.<br />";
			}
		}
		if ( $msg ) {
			echo "<h3>Warnings</h3><pre style=\"overflow:auto; border:1px dotted #cc0;\">$msg</pre>";
		}
	}
	
	private function install_files()
	{
		foreach ( $this->archive->get_file_list() as $file ) {
			if ( array_key_exists( $file, $this->install_profile ) ) {
				$install_location= $this->install_profile[$file];
				$this->archive->unpack( $file, HABARI_PATH . '/' . $install_location, 0777 );
			}
			else {
				//log files that were not installed
			}
		}
	}
	
	private function build_install_profile()
	{
		$install_profile= array();
		foreach ( $this->archive->get_file_list() as $file ) {
			if ( basename($file) == 'package.xml' ) {
				$this->info= simplexml_load_string( $this->archive->read_file($file) );
				continue;
			}
			if ( basename($file) == 'README' ) {
				$this->readme_doc=  $this->archive->read_file($file);
			}
			if ( strpos( $file, '__MACOSX' ) === 0 ) {
				// stoopid mac users!
				continue;
			}
			
			$install_profile[$file]= HabariPackages::type_location( $this->type ) . '/' . $file;
		}
		
		if ( $this->info && $this->info->filelist ) {
			foreach ( $this->info->filelist->file as $file ) {
				$install_profile[$file['name']]=  HabariPackages::type_location( $this->type ) . '/' . $file['install_location'];
			}
		}
		
		$this->install_profile= $install_profile;
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
