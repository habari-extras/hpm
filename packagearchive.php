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
	private static $archive_readers = array();
	
	public function __construct( $url )
	{
		$this->url = $url;
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
		if ( isset( $matches[1] ) ) {
			$mime = $matches[1];
		}
		else {
			throw new Exception( 'Could not determine archive type' );
		}
		
		$file = HabariPackages::tempnam();
		if ( ! file_put_contents( $file, $remote_archive->get_response_body(), LOCK_EX ) ) {
			throw new Exception( 'Please make the directory ' . dirname( $file ) . ' writeable by the server' );
		}
		
		$this->md5 = md5_file( $file );
		$this->set_archive_reader( $mime, $file );
		
		unset( $remote_archive );
	}
	
	public function set_archive_reader( $mime, $file )
	{
		if ( isset( self::$archive_readers[$mime] ) ) {
			$this->reader = new self::$archive_readers[$mime]( $file );
		}
		else {
			throw new Exception( "No Archive reader available for type {$mime}" );
		}
	}
	
	// helper functions
	public function get_file_list()
	{
		return $this->reader->get_file_list();
	}
	
	public function unpack( $file, $location, $perms = 0777 )
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
