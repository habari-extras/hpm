<?php

include 'dunzip2.php';

class ZipReader extends dUnzip2 implements ArchiveReader
{
	const DUNZIP2_VERSION= 2.6;
	
	private $filelist= array();
	
	public function __construct( $filename )
	{
		parent::__construct( $filename );
	}
	
	public function get_file_list()
	{
		if ( ! $this->filelist ) {
			$this->filelist= array_map( create_function('$a', 'return $a["file_name"];'), $this->getList() );
		}
		
		return $this->filelist;
	}
	
	public function unpack( $file, $location, $perms= 0777 )
	{
		// dUnzip2 includes folders in it's file list.
		if ( substr($file, -1) == "/" ) {
			return true;
		}
		// if the dir doesn't exist, make it, recursively
		if ( ! is_dir( dirname($location) ) ) {
			mkdir( dirname($location), $perms, true );
		}
		return $this->unzip( $file, $location, $perms );
	}
	
	public function read_file( $file )
	{
		return $this->unzip( $file, false );
	}
}

?>
