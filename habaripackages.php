<?php

/**
* 
* update() updates list
* upgrage($package_name) upgrades given package
* install($package_name) installs given package
* remove($package_name) removes given package
* clean() cleans out the local packages archive cache dir
* 
* Server methods:
* 
* packages.
* 	list -> list all package_names for habari_version given
* 	update -> return list of packages to update/add/remov
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
		return ( time() > ( Options::get( 'hpm:last_update' ) + self::UPDATE_INTERVAL ) );
	}
	
	public static function update()
	{
		foreach ( self::get_repos() as $repo ) {
			if ( !self::update_packages( $repo ) ) {
				Session::notice( sprintf( "Could not update packages from %s", $repo ) );
			}
		}
		Options::set( 'hpm:last_update', time() );
	}
	
	public static function upgrade( $package_name )
	{
		$package= HabariPackage::get( $package_name );
		if ( $package->status == 'upgrade' ) {
			$package->upgrade();
		}
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
	
	// clean the packages list of any orphaned packges.
	public static function clean()
	{
		$packages= array();
		foreach ( self::get_repos() as $repo ) {
			$packages = array_merge( $packages, (array) self::list_packages( $repo ) );
		}
		
		DB::query(
			'DELETE FROM {packages} WHERE guid NOT IN (' . Utils::placeholder_string( count($packages) ) . ')',
			$packages
			);
	}
	
	public static function get_repos()
	{
		$repos= array_map( 'trim', (array) explode( ',', Options::get( 'hpm:repos' ) ) );
		return $repos;
	}
	
	/**
	 * @todo the server should return all versions and let hpm decide which version to take
	 */
	public static function update_packages( $repo )
	{
		$params= array(
			'action' => 'update',
			'last_update' => Options::get( 'hpm:last_update' ),
			'habari_version' => Version::get_habariversion(),
			);
		
		$client = new RemoteRequest( $repo, 'POST' );
		$client->set_params( $params );
		if ( Error::is_error( $client->execute() ) ) {
			return false;
		}
		
		try {
			$packages = $client->get_response_body();
			$packages = new SimpleXMLElement( $packages );
			foreach ( $packages->package as $package ) {
				if ( ! $package['guid'] || ! $package->versions ) {
					continue;
				}
				
				$new_package = (array) $package->attributes();
				$new_package = $new_package['@attributes'];
				$new_package['description'] = strval( $package->description );
				
				$versions = array();
				foreach( $package->versions->version as $version ) {
					$version = (array) $version->attributes();
					$version = $version['@attributes'];
					if ( isset( $version['habari_version'] ) && version_compare( Version::get_habariversion(), $version['habari_version'], 'eq' ) ) {
						$versions[$version['version']] = $version;
					}
				}
				if ( count($versions) ) {
					uksort( $versions, create_function('$a,$b','return version_compare($b,$a);') );
					$new_package = array_merge( (array) current($versions), $new_package );
					
					if ( $old_package = HabariPackage::get( $new_package['guid'] ) ) {
						if ( version_compare( $new_package['version'], $old_package->version, '>=' ) ) {
							if ( $old_package->status == 'installed' ) {
								$new_package['status'] = 'upgrade';
							}
							DB::update( DB::table('packages'), $new_package, array('guid'=>$new_package['guid']) );
						}
						else {
							continue;
						}
					}
					else {
						DB::insert( DB::table('packages'), $new_package );
					}
				}
			}
			return true;
		}
		catch ( Exception $e ) {
			Utils::debug( $e );
			return false;
		}
	}
	
	/**
	 * @todo the server should return all packages with just the guid 
	 *       like: <package>$guid</package>
	 */
	public static function list_packages( $repo )
	{
		$params= array(
			'action' => 'list',
			'last_update' => Options::get( 'hpm:last_update' ),
			'habari_version' => Version::get_habariversion(),
			);
		
		$client = new RemoteRequest( $repo, 'POST' );
		$client->set_params( $params );
		if ( Error::is_error( $client->execute() ) ) {
			return array();
		}
		
		$packages = array();
		try {
			$response= $client->get_response_body();
			$response= new SimpleXMLElement( $response );
			
			foreach ( $response->package as $package ) {
				$packages[] = strval( $package['guid'] );
			}
		}
		catch ( Exception $e ) {
			Session::notice( "could get packages from $repo" );
		}
		return $packages;
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
		return tempnam( HABARI_PATH . '/system/cache', 'HPM' );
	}
}

?>
