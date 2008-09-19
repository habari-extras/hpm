<?php

// for single file packages

class TxtReader implements ArchiveReader
{
	
	private $filelist = array();
	
	public function __construct( $filename )
	{
		$this->filelist = array( $filename => $filename );
	}
	
	public function get_file_list()
	{
		return $this->filelist;
	}
	
	public function unpack( $file, $location, $perms = 0777 )
	{
		if ( ! is_dir( dirname($location) ) ) {
			mkdir( dirname($location), $perms, true );
		}
		copy( $file, $location );
		chmod( $location, $perms );
		
		return true;
	}
	
	public function read_file( $file )
	{
		return file_get_contents( $file );
	}
}

?>