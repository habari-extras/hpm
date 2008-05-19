<?php

/*

CREATE TABLE habari__packages_repo (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	name VARCHAR(255) NOT NULL,
	package_name VARCHAR(255) NOT NULL,
	version VARCHAR(255) NOT NULL,
	description LONGTEXT NOT NULL,
	author VARCHAR(255) NOT NULL,
	author_url VARCHAR(255) NOT NULL,
	max_habari_version VARCHAR(255) NOT NULL,
	min_habari_version VARCHAR(255) NOT NULL,
	archive_md5 VARCHAR(255) NOT NULL,
	archive_url VARCHAR(255) NOT NULL,
	type SMALLINT UNSIGNED NOT NULL,
	depends VARCHAR(255) NOT NULL,
	signature VARCHAR(255) NOT NULL,
	pubdate VARCHAR(255) NOT NULL,
	PRIMARY KEY (id)
);


TEST

INSERT INTO habari__packages_repo (
	name,
	package_name,
	version,
	description,
	author,
	author_url,
	max_habari_version,
	min_habari_version,
	archive_md5,
	archive_url,
	type,
	depends,
	signature
)
VALUES (
	'Tabasamu Smilies',
	'tabasamu',
	'1.0',
	'Smilies for Habari',
	'Drunken Monkey Labs',
	'http://drunkenmonkey.org',
	'0.5',
	'0.3',
	'd8540d6faa356e7c9a8989b2988e7e8d',
	'http://drunkenmonkey.org/user/files/tabasamu.0.5.tar.gz',
	1,
	' ',
	' '
);

INSERT INTO habari__packages_repo (
	name,
	package_name,
	version,
	description,
	author,
	author_url,
	max_habari_version,
	min_habari_version,
	archive_md5,
	archive_url,
	type,
	depends,
	signature
)
VALUES (
	'Habari Markdown',
	'habari-markdown',
	'0.1',
	'Markdown formatting for Habari',
	'Drunken Monkey Labs',
	'http://drunkenmonkey.org',
	'0.5',
	'0.3',
	'cff56be62fdc1a17a30c72276e3b3bc9',
	'http://drunkenmonkey.org/user/files/habari-markdown-0.1.tar.gz',
	1,
	' ',
	' '
);

INSERT INTO habari__packages_repo (
	name,
	package_name,
	version,
	description,
	author,
	author_url,
	max_habari_version,
	min_habari_version,
	archive_md5,
	archive_url,
	type,
	depends,
	signature
)
VALUES (
	'Harvestfield',
	'harvestfield',
	'0.1',
	'harvestfield Theme for Habari',
	'miklb',
	'http://bloggingmeta.com',
	'0.5',
	'0.3',
	'3f2c29cca3439e6ee8212322888a5817',
	'http://bloggingmeta.com/user/downloads/Harvest_Field.zip',
	2,
	' ',
	' '
);

INSERT INTO habari__packages_repo (
	name,
	package_name,
	version,
	description,
	author,
	author_url,
	max_habari_version,
	min_habari_version,
	archive_md5,
	archive_url,
	type,
	depends,
	signature
)
VALUES (
	'NicEdit',
	'nicedit',
	'1.0',
	'NicEdit WYSIWYG editor for Habari',
	'h0bbel',
	'http://adjungo.net/',
	'0.5',
	'0.3',
	'9dbfb0ff8df32cc18c026829ed54c154',
	'http://adjungo.net/NicEdit.tar',
	1,
	' ',
	' '
);

INSERT INTO habari__packages_repo (
	name,
	package_name,
	version,
	description,
	author,
	author_url,
	max_habari_version,
	min_habari_version,
	archive_md5,
	archive_url,
	type,
	depends,
	signature
)
VALUES (
	'TinyMCE',
	'tinymce',
	'1.0',
	'TinyMCE WYSIWYG editor for Habari',
	'michaeltwofish',
	'http://www.twofishcreative.com/',
	'0.5',
	'0.3',
	'9dbfb0ff8df32cc18c026829ed54c154',
	'http://www.twofishcreative.com/michael/blog/tinymce.zip',
	1,
	' ',
	' '
);

*/

class HabariPackageRepo_Server extends XMLRPCServer
{
	public function act_xmlrpc_call()
	{
		Plugins::register(array($this, 'packages_update'), 'xmlrpc', 'packages.update');
		//Plugins::register(array($this, 'packages_list'), 'xmlrpc', 'packages.list');
		//Plugins::register(array($this, 'packages_get'), 'xmlrpc', 'packages.get');
		
		Plugins::register(array($this, 'server_getInfo'), 'xmlrpc', 'server.getInfo');
		
		parent::act_xmlrpc_call();
	}
	
	public function packages_update( $returnvalue, $params )
	{
		DB::register_table('packages_repo');
		$packages= DB::get_results('SELECT * FROM '. DB::table('packages_repo') .' WHERE 1=1', array());
		$xml= new SimpleXMLElement('<packages/>');
		$xml->addChild( 'version', time() );
		$xml->addChild( 'signature', 'Awsom3' );
		
		foreach ( $packages as $package ) {
			$package_xml= $xml->addChild('package');
			
			foreach ( $package->to_array() as $key => $value ) {
				if ( $key == 'id' ) continue;
				if ( $key == 'pubdate' ) continue;
				$package_xml->addChild( $key, utf8_encode($value) );
			}
		}
		
		return $xml->asXml();
	}
	
	public function getInfo()
	{
		$xml= new SimpleXMLElement('<server/>');
		$info= $xml->addChild('info');
		$info->addChild('name', 'Wicket');
		$info->addChild('url', 'http://packages.drunkenmonkey.org/repo/');
		$info->addChild('browser_url', 'http://packages.drunkenmonkey.org/repo/');
		$info->addChild('description', 'Package repo for testing purposes.');
		$info->addChild('owner', 'Matt Read');
		$info->addChild('signature', 'Awsom3');
		return $xml->asXml();
	}
	
	private function to_xml( SimpleXMLElement $parent, $data )
	{
		foreach ( $data as $key => $value ) {
			$parent->addChild( $key, utf8_encode($value) );
		}
		return $parent;
	}
}

?>