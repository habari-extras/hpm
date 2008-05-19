<?php

interface ArchiveReader
{
	public function __construct( $filename );
	public function get_file_list();
	public function unpack( $file, $location, $perms= 0777 );
	public function read_file( $file );
}

?>