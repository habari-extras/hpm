<?php
/*

** modified by Matt Read <matt@mattread.com> for use in Habari HPM

=======================================================================
Name:
	tar Class

Author:
	Josh Barger <joshb@npt.com>

Description:
	This class reads and writes Tape-Archive (TAR) Files and Gzip
	compressed TAR files, which are mainly used on UNIX systems.
	This class works on both windows AND unix systems, and does
	NOT rely on external applications!! Woohoo!

Usage:
	Copyright (C) 2002  Josh Barger

	This library is free software; you can redistribute it and/or
	modify it under the terms of the GNU Lesser General Public
	License as published by the Free Software Foundation; either
	version 2.1 of the License, or (at your option) any later version.

	This library is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
	Lesser General Public License for more details at:
		http://www.gnu.org/copyleft/lesser.html

	If you use this script in your application/website, please
	send me an e-mail letting me know about it :)

Bugs:
	Please report any bugs you might find to my e-mail address
	at joshb@npt.com.  If you have already created a fix/patch
	for the bug, please do send it to me so I can incorporate it into my release.

Version History:
	1.0	04/10/2002	- InitialRelease

	2.0	04/11/2002	- Merged both tarReader and tarWriter
				  classes into one
				- Added support for gzipped tar files
				  Remember to name for .tar.gz or .tgz
				  if you use gzip compression!
				  :: THIS REQUIRES ZLIB EXTENSION ::
				- Added additional comments to
				  functions to help users
				- Added ability to remove files and
				  directories from archive
	2.1	04/12/2002	- Fixed serious bug in generating tar
				- Created another example file
				- Added check to make sure ZLIB is
				  installed before running GZIP
				  compression on TAR
	2.2	05/07/2002	- Added automatic detection of Gzipped
				  tar files (Thanks go to Jrgen Falch
				  for the idea)
				- Changed "private" functions to have
				  special function names beginning with
				  two underscores
=======================================================================
*/

class TarReader implements ArchiveReader
{
	private $filename;
	private $tar_file;
	private $filelist = array();
	private $files = array();
	private $directories;
	
	public $numFiles;
	public $numDirectories;
	
	public function __construct( $filename )
	{
		$this->tar_file = file_get_contents( $filename );
		$this->parse();
	}
	
	public function get_file_list()
	{
		if ( ! $this->filelist ) {
			$this->filelist = array_map( create_function('$a', 'return $a["name"];'), $this->files );
		}
		
		return $this->filelist;
	}
	
	public function unpack( $file, $location, $perms = 0777 )
	{
		// if the dir doesn't exist, make it, recursively
		if ( ! is_dir( dirname($location) ) ) {
			mkdir( dirname($location), $perms, true );
		}
		return file_put_contents( $location, $this->files[$file] );
	}
	
	public function read_file( $file )
	{
		return $this->files[$file]['file'];
	}
	
	private function compute_unsigned_checksum($bytestring) {
		$unsigned_chksum ='';
		for($i =0; $i<512; $i++)
			$unsigned_chksum += ord($bytestring[$i]);
		for($i =0; $i<8; $i++)
			$unsigned_chksum -= ord($bytestring[148 + $i]);
		$unsigned_chksum += ord(" ") * 8;
		
		return $unsigned_chksum;
	}
	
	private function parse_null_padded_string($string)
	{
		$position = strpos($string,chr(0));
		return substr($string,0,$position);
	}
	
	private function parse()
	{
		if ( $this->tar_file[0] == chr(31) && $this->tar_file[1] == chr(139) && $this->tar_file[2] == chr(8) ) {
			if( !function_exists("gzinflate") ) {
				return false;
			}
			$this->tar_file = $this->gzdecode( $this->tar_file );
		}
		
		// Read Files from archive
		$tar_length = strlen($this->tar_file);
		$main_offset = 0;
		while($main_offset < $tar_length) {
			// If we read a block of 512 nulls, we are at the end of the archive
			if(substr($this->tar_file,$main_offset,512) == str_repeat(chr(0),512))
				break;

			// Parse file name
			$file_name		= $this->parse_null_padded_string(substr($this->tar_file,$main_offset,100));

			// Parse the file mode
			$file_mode		= substr($this->tar_file,$main_offset + 100,8);

			// Parse the file user ID
			$file_uid		= octdec(substr($this->tar_file,$main_offset + 108,8));

			// Parse the file group ID
			$file_gid		= octdec(substr($this->tar_file,$main_offset + 116,8));

			// Parse the file size
			$file_size		= octdec(substr($this->tar_file,$main_offset + 124,12));

			// Parse the file update time - unix timestamp format
			$file_time		= octdec(substr($this->tar_file,$main_offset + 136,12));

			// Parse Checksum
			$file_chksum		= octdec(substr($this->tar_file,$main_offset + 148,6));

			// Parse user name
			$file_uname		= $this->parse_null_padded_string(substr($this->tar_file,$main_offset + 265,32));

			// Parse Group name
			$file_gname		= $this->parse_null_padded_string(substr($this->tar_file,$main_offset + 297,32));

			// Make sure our file is valid
			if($this->compute_unsigned_checksum(substr($this->tar_file,$main_offset,512)) != $file_chksum)
				return false;

			// Parse File Contents
			$file_contents		= substr($this->tar_file,$main_offset + 512,$file_size);

			/*	### Unused Header Information ###
				$activeFile["typeflag"]		= substr($this->tar_file,$main_offset + 156,1);
				$activeFile["linkname"]		= substr($this->tar_file,$main_offset + 157,100);
				$activeFile["magic"]		= substr($this->tar_file,$main_offset + 257,6);
				$activeFile["version"]		= substr($this->tar_file,$main_offset + 263,2);
				$activeFile["devmajor"]		= substr($this->tar_file,$main_offset + 329,8);
				$activeFile["devminor"]		= substr($this->tar_file,$main_offset + 337,8);
				$activeFile["prefix"]		= substr($this->tar_file,$main_offset + 345,155);
				$activeFile["endheader"]	= substr($this->tar_file,$main_offset + 500,12);
			*/

			if($file_size > 0) {
				// Increment number of files
				$this->numFiles++;

				// Create us a new file in our array
				$activeFile = &$this->files[$file_name];

				// Asign Values
				$activeFile["name"]		= $file_name;
				$activeFile["mode"]		= $file_mode;
				$activeFile["size"]		= $file_size;
				$activeFile["time"]		= $file_time;
				$activeFile["user_id"]		= $file_uid;
				$activeFile["group_id"]		= $file_gid;
				$activeFile["user_name"]	= $file_uname;
				$activeFile["group_name"]	= $file_gname;
				$activeFile["checksum"]		= $file_chksum;
				$activeFile["file"]		= $file_contents;

			} else {
				// Increment number of directories
				$this->numDirectories++;

				// Create a new directory in our array
				$activeDir = &$this->directories[];

				// Assign values
				$activeDir["name"]		= $file_name;
				$activeDir["mode"]		= $file_mode;
				$activeDir["time"]		= $file_time;
				$activeDir["user_id"]		= $file_uid;
				$activeDir["group_id"]		= $file_gid;
				$activeDir["user_name"]		= $file_uname;
				$activeDir["group_name"]	= $file_gname;
				$activeDir["checksum"]		= $file_chksum;
			}

			// Move our offset the number of blocks we have processed
			$main_offset += 512 + (ceil($file_size / 512) * 512);
		}
		return true;
	}
	
	private function gzdecode ($data)
	{
		$flags = ord(substr($data, 3, 1));
		$headerlen = 10;
		$extralen = 0;
		$filenamelen = 0;
		if ($flags & 4) {
			$extralen = unpack('v' ,substr($data, 10, 2));
			$extralen = $extralen[1];
			$headerlen += 2 + $extralen;
		}
		if ($flags & 8) // Filename
			$headerlen = strpos($data, chr(0), $headerlen) + 1;
		if ($flags & 16) // Comment
			$headerlen = strpos($data, chr(0), $headerlen) + 1;
		if ($flags & 2) // CRC at end of file
			$headerlen += 2;
		$unpacked = gzinflate(substr($data, $headerlen));
		if ($unpacked === FALSE)
			$unpacked = $data;
		return $unpacked;
	}
}

?>
