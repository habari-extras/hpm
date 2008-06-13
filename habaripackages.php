<?php

/**
* 
* update() updates list
* upgrage($package_name) upgrades given package
* install($package_name) installs given package
* remove($package_name) removes given package
* clean() cleans out tmp files
* 
* 
*/

class HabariPackages
{
	
	private static $types= array();
	private static $types_location= array();
	
	const UPDATE_INTERVAL = 259000;
	
	// this will be like in Posts::get
	public static function get( $param_array )
	{
	}
	
	public static function require_updates()
	{
		return ( 
			time() > ( Options::get( 'hpm__last_update' ) + self::UPDATE_INTERVAL )
			|| version_compare( Options::get( 'hpm__repo_version' ), Version::get_habariversion() ) != 0
			);
	}
	
	public static function update()
	{
		$package_list = array();
		$packages = array();
		
		foreach ( self::get_repos() as $repo ) {
			$packages =  self::update_packages( $repo );
			if ( $packages === false ) {
				Session::notice( sprintf( "Could not update packages from %s", $repo ) );
			}
			else {
				$package_list = array_merge( $package_list, $packages );
			}
		}
		Options::set( 'hpm__last_update', time() );
		
		// get rid of orphaned/incompatible packages
		if ( $package_list ) {
			DB::query(
				'DELETE FROM {packages} WHERE id NOT IN (' . Utils::placeholder_string( count($package_list) ) . ')',
				$package_list
				);
		}
		else {
			// there are no compatible packages, so crap 'em all
			DB::query( 'DELETE FROM {packages}' );
		}
	}
	
	public static function upgrade( $package_name )
	{
		$package= HabariPackage::get( $package_name );
		if ( $package->status == 'upgrade' ) {
			$package->upgrade();
		}
		
		return $package;
	}
	
	public static function install( $package_name )
	{
		$package= HabariPackage::get( $package_name );
		$package->install();
		
		return $package;
	}
	
	public static function remove( $package_name )
	{
		$package= HabariPackage::get( $package_name );
		if ( $package->status != '' ) {
			$package->remove();
		}
		
		return $package;
	}
	
	// clean tmp files left behind
	public static function clean()
	{
		// remove all tmp files and dirs starting with "HPM-"
	}
	
	public static function get_repos()
	{
		$repos= array_map( 'trim', (array) explode( ',', Options::get( 'hpm__repos' ) ) );
		return $repos;
	}
	
	/**
	 * @todo the server should return all versions and let hpm decide which version to take
	 */
	public static function update_packages( $repo )
	{
		$client = new RemoteRequest( $repo, 'GET' );
		if ( Error::is_error( $client->execute() ) ) {
			return false;
		}
		
		try {
			$packages = $client->get_response_body();
			//Utils::debug( $packages );
			$packages = new SimpleXMLElement( $packages );
			$package_list = array();
			foreach ( $packages->package as $package ) {
				if ( ! $package['guid'] || ! $package->versions ) {
					continue;
				}
				
				$new_package = (array) $package->attributes();
				$new_package = $new_package['@attributes'];
				$new_package['description'] = strval( $package->description );
				
				$versions = array();
				//Utils::debug($package->versions);
				foreach( $package->versions->version as $version ) {
					$version = (array) $version->attributes();
					$version = $version['@attributes'];
					if ( isset( $version['habari_version'] ) && self::is_compatible( $version['habari_version'] ) ) {
						$versions[$version['version']] = $version;
					}
				}
				//Utils::debug( $new_package, $versions );
				
				uksort( $versions, create_function('$a,$b','return version_compare($b,$a);') );
				$version = current($versions);
				
				if ( $version ) {
					$new_package = array_merge( $version, $new_package );
					
					if ( $old_package = HabariPackage::get( $new_package['guid'] ) ) {
						if ( isset($new_package['version']) && version_compare( $new_package['version'], $old_package->version, '>' ) ) {
							if ( $old_package->status == 'installed' ) {
								$new_package['status'] = 'upgrade';
							}
							DB::update( DB::table('packages'), $new_package, array('guid'=>$new_package['guid']) );
							$package_list[] = $old_package->id;
						}
						else {
							$package_list[] = $old_package->id;
						}
					}
					else {
						DB::insert( DB::table('packages'), $new_package );
						$package_list[] = DB::last_insert_id();
					}
				}
			}
			
			Options::set( 'hpm__repo_version', Version::get_habariversion() );
			
			return $package_list;
		}
		catch ( Exception $e ) {
			Utils::debug( $e );
			return false;
		}
	}
	
	public static function is_compatible( $ver )
	{
		$habari_ver = explode( '.', str_replace( array('-','_',' '), '.', Version::get_habariversion() ) );
		$ver = explode( '.', str_replace( array('-','_',' '), '.', $ver ) );
		$habari_ver = array_pad( $habari_ver, count($ver), '0' );
		
		foreach ( $habari_ver as $i => $el ) {
			if ( isset( $ver[$i] ) ) {
				if ( $ver[$i] != 'x' && version_compare( $el, $ver[$i] ) != 0 ) {
					return false;
				}
			}
			else {
				return false;
			}
		}
		return true;
	}
	
	public static function list_package_types()
	{
		if ( self::$types ) {
			return self::$types;
		}
		self::$types= array(
			'system',
			'plugin',
			'theme'
			);
		self::$types= array_merge( Plugins::filter( 'package_types', array() ), self::$types );
		return self::$types;
	}
	
	public static function type_location( $type )
	{
		$types= self::list_package_types();
		if ( is_numeric( $type ) ) {
			$type= $types[$type];
		}
		$type_locations= array(
			'system'=>'',
			'plugin'=>'/3rdparty/plugins',
			'theme'=>'/3rdparty/themes',
			);
		return $type_locations[$type];
	}
	
	public static function tempnam()
	{
		return tempnam( HABARI_PATH . '/system/cache', 'HPM-' );
	}
	
	public static function tempdir()
	{
		$tmp_dir = 'HPM-' . md5( UUID::get() );
		if ( is_writable( HABARI_PATH . "/system/cache" ) ) {
			$tmp_dir = HABARI_PATH . "/system/cache/$tmp_dir";
		}
		else {
			$tmp_dir = sys_get_temp_dir() . "/$tmp_dir";
		}
		mkdir( $tmp_dir, 0777 );
		return $tmp_dir;
	}
}

?>
