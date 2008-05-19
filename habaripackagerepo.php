<?php

/*

CREATE TABLE  habari__package_repos (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL,
  url VARCHAR(255) NOT NULL,
  browser_url VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  signature VARCHAR(255) NOT NULL,
  version VARCHAR(255) NOT NULL,
  owner VARCHAR(255) NOT NULL
);

Server methods:

packages.
	list -> list all package_names for habari_version given
	update -> return list of packages to update/add/remove 
	get -> get single package
	check_update -> check to see if updates are available, return boolean

server.
	getInfo
	setInfo
	addPackage
	upgradePackage
	removePackage

test

INSERT INTO habari__package_repos (name,url,browser_url,description,owner,signature,version) VALUES('Wicket','http://natasha/habari/trunk/htdocs/packages/','null','A package repo for testing purposes only!','Matt Read','Awsom3','1');

INSERT INTO habari__package_repos (name,url,browser_url,description,owner,signature,version) VALUES('WicketRemote','http://mattread.com/packages/','null','A package repo for testing purposes only! internet!','Matt Read','Awsom3','1');

*/

class HabariPackageRepo extends QueryRecord
{
	private $data= array();
	private $client;
	
	public static function default_fields()
	{
		return array(
			'id' => 0,
			'name' => '',
			'url' => '',
			'browser_url' => '',
			'description' => '',
			'owner' => '',
			'signature' => '',
			'version' => ''
		);
	}
	
	public function __construct( $paramarray = array() )
	{
		// I AM TEH REPO MAN !!!
		$this->fields = array_merge(
			self::default_fields(),
			$this->fields 
		);
		
		parent::__construct( Utils::get_params( $paramarray ) );
		$this->exclude_fields( 'id' );
		
		$this->client= new XMLRPCClient( $this->url );
	}
	
	public static function add( $url )
	{
		$repo= new HabariPackageRepo( "url=$url" );
		$repo->insert();
		$repo->get_server_info();
		$repo->update_packages();
	}
	
	public static function repo( $name )
	{
		$repo= DB::get_row( 'SELECT * FROM ' . DB::table('package_repos') . ' WHERE name = ?', array($name), 'HabariPackageRepo' );
		return $repo;
	}
	
	public static function repos()
	{
		$repos= DB::get_results( 'SELECT * FROM ' . DB::table('package_repos') . '', array(), 'HabariPackageRepo' );
		return $repos;
	}
	
	public function get_server_info()
	{
		$response= $this->client->server->getInfo();
		$info= array_map( 'strval', (array) $response->info );
		foreach ( $info as $key => $value ) {
			$this->$key= $value;
		}
		$this->update();
		Utils::debug( $response );
	}
	
	public function update_packages()
	{
		$response= $this->client->packages->update( $this->version, Version::HABARI_VERSION );
		//Utils::debug( $response, $this->client->packages->update() );
		$response= new SimpleXMLElement( $response );
		
		// this check would be done against the "AuthServer" by "AuthClient"
		if ( strval($response->signature) != $this->signature ) {
			throw new Exception( 'Invalid server signature.' );
		}
		
		//Utils::debug( $response->package );
		
		//TODO: check for packages to remove/add/update.
		foreach ( $response->package as $package ) {
			$package= array_map( 'strval', (array) $package );
			unset( $package['signaure'] ); // compat fix
			//Utils::debug( (array) $package );
			DB::update( DB::table('packages'), $package, array('package_name'=>$package['package_name']) );
		}
		
		$this->version= $response->version;
		$this->update();
		
		return $this->name;
	}
	
	public function update()
	{
		return parent::updateRecord( DB::table('package_repos'), array('id'=>$this->id) );
	}
	
	public function insert()
	{
		return parent::insertRecord( DB::table('package_repos') );
	}
	
	public function delete()
	{
		return parent::deleteRecord( DB::table('package_repos'), array('id'=>$this->id) );
	}
}

?>