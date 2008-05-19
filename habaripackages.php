<?php

/*

update() updates list
upgrage($package_name) upgrades given package
install($package_name) installs given package
remove($package_name) removes given package
clean() cleans out the local packages archive cache dir

*/

class HabariPackages
{
	
	private static $types= array();
	private static $types_location= array();
	
	// this will be like in Posts::get
	public static function get( $param_array )
	{
	}
	
	public static function update()
	{
		foreach ( HabariPackageRepo::repos() as $repo ) {
			$repo->update_packages();
		}
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
		if ( $package->status == 'installed' ) {
			$package->remove();
		}
		
		return $package;
	}
	
	// clean out the tmp /user/packages dir.
	// but really we should Habari's cahce code.
	public static function clean()
	{
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
}

?>
