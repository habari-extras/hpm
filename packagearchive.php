<?php

/*

NOTES

we should use Habari's file cahce for caching tmp archive files.

*/

class PackageArchive
{
	public $md5;
	
	private $url;
	private $reader;
	private static $archive_readers= array();
	
	private $content_type;
	private $filename;
	
	public function __construct( $package_name, $url )
	{
		$this->filename= HPMHandler::$PACKAGES_PATH . '/' . $package_name . '.' . time() . '.hpm';
		$this->url= $url;
	}
	
	public function __sleep()
	{
		return array( 'url', 'content_type', 'filename' );
	}
	
	public function __wakeup()
	{
		$this->set_archive_reader();
		$this->md5= md5_file( $this->filename );
	}
	
	public function fetch()
	{
		$remote_archive = new RemoteRequest( $this->url );
		if ( Error::is_error( $remote_archive->execute() ) ) {
			throw new Exception( 'Could not fetch archive at ' . $this->url );
		}
		
		// we should also check content-disposition for filename and the url as fallbacks.
		// some crazy people like to send application/octet-stream, weirdos!
		
		foreach ( split( "\n", $remote_archive->get_response_headers() ) as $line ) {
			if ( substr_compare( $line, 'Content-Type', 0, 12, true ) == 0 ) {
				$content_type = $line;
				break;
			}
		}
		
		/* Get the MIME type and character set */
		preg_match( '@Content-Type:\s+([\w/\-+]+)(;\s+charset=(\S+))?@i', $content_type, $matches );
		if ( isset( $matches[1] ) )
			$mime = $matches[1];
		if ( isset( $matches[3] ) )
			$charset = $matches[3];
		
		$this->content_type= $mime;
		file_put_contents( $this->filename, $remote_archive->get_response_body() );
		$this->md5= md5_file( $this->filename );
		
		unset( $remote_archive );
	}
	
	public function set_archive_reader( $archive_reader= null )
	{
		if ( $archive_reader ) {
			$this->reader= new $archive_reader( $this->filename );
		}
		elseif ( array_key_exists( $this->content_type, self::$archive_readers ) ) {
			$this->reader= new self::$archive_readers[$this->content_type]( $this->filename );
		}
		else {
			throw new Exception( "No Archive reader available for type {$this->content_type}" );
		}
	}
	
	private function get_content_type( $headers )
	{
		if ( preg_match('/content-type: (.*)/si', $headers, $matches) ) {
			$this->content_type= $matches[1];
			return true;
		}
		
		if ( preg_match('/content-disposition: (.*)/si', $headers, $matches) ) {
			if ( preg_match('/filename=(.*)/si', $matches[1], $match) ) {
					$this->content_type= $this->match_filename( $match[1] );
			}
		}
	}
	
	private function match_filename( $file_name ) {
		$mimes_filetype= array (
		'tar|tar.gz|tgz' => 'application/x-tar',
		'zip' => 'application/zip',
		'gz|gzip' => 'application/x-gzip',
		'php' => 'text/php',
		'txt' => 'text/plain');
		
		foreach ($exts as $ext_preg => $type) {
			$ext_preg= '![^.]\.(' . $ext_preg . ')$!i';
			if ( preg_match( $ext_preg, $file_name, $matches ) ) {
				return $type;
			}
		}
		return false;
	}
	
	// helper functions
	public function get_file_list()
	{
		return $this->reader->get_file_list();
	}
	
	public function unpack( $file, $location, $perms= 0777 )
	{
		return $this->reader->unpack( $file, $location, $perms );
	}
	
	public function read_file( $file )
	{
		return $this->reader->read_file( $file );
	}
	
	// archive readers should be registered with content-type and file extension! but we don't :(
	public static function register_archive_reader( $content_type, $class )
	{
		self::$archive_readers[$content_type]= $class;
	}
}

?>