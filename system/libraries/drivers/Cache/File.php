<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * File-based Cache driver.
 *
 * $Id: File.php 4046 2009-03-05 19:23:29Z Shadowhand $
 *
 * @package    Cache
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Cache_File_Driver implements Cache_Driver {

	protected $directory = '';

	// Have to be very careful: need the functionality whereby we can reset the timeout on a cache entry.
	// But if the cache is under multiple heavy use, deleting a file could be bad news.
	
	/**
	 * Tests that the storage location is a directory and is writable.
	 */
	public function __construct($directory)
	{
		// Find the real path to the directory
		$directory = str_replace('\\', '/', realpath($directory)).'/';

		// Make sure the cache directory is writable
		if ( ! is_dir($directory) OR ! is_writable($directory))
			throw new Kohana_Exception('cache.unwritable', $directory);
			
		// Directory is valid
		$this->directory = $directory;
	}

	/**
	 * Finds an array of all files matching the given id or tag.
	 * Will include multiple entries for multiple lifetime values.
	 *
	 * @param  string  cache id or tag
	 * @param  bool    search for tags
	 * @return array   of filenames matching the id or tag
	 */
	private function all_exists($id, $tag)
	{
		if ($id === TRUE)
		{
			// Find all the files
			return glob($this->directory.'*~*~*');
		}
		elseif ($tag === TRUE)
		{
			// Find all the files that have the tag name
			$paths = glob($this->directory.'*~*'.$id.'*~*');

			// Find all tags matching the given tag
			$files = array();
			foreach ($paths as $path)
			{
				// Split the files
				$tags = explode('~', basename($path));

				// Find valid tags
				if (count($tags) !== 3 OR empty($tags[1]))
					continue;

				// Split the tags by plus signs, used to separate tags
				$tags = explode('+', $tags[1]);

				if (in_array($tag, $tags))
				{
					// Add the file to the array, it has the requested tag
					$files[] = $path;
				}
			}

			return $files;
		}
		else
		{
			// Find the file matching the given id
			return glob($this->directory.$id.'~*');
		}
	}

	/**
	 * Finds an array of files matching the given id or tag.
	 * Only includes the most recent file.
	 *
	 * @param  string  cache id or tag
	 * @param  bool    search for tags
	 * @return array   of filenames matching the id or tag
	 */
	public function exists($id, $tag = FALSE)
	{
		$paths = $this->all_exists($id, $tag);
		$tmpfiles = array();
		foreach ($paths as $path)
		{
			// Split the files
			$expires = (int) substr($path, strrpos($path, '~') + 1);
			$theRest = substr($path, 0, strrpos($path, '~'));
			if(array_key_exists($theRest, $tmpfiles)){
				if($expires > $tmpfiles[$theRest])
					$tmpfiles[$theRest] = $expires;
			} else
				$tmpfiles[$theRest] = $expires;
		}
		$files = array();
		foreach ($tmpfiles as $theRest => $expires)
		{
			$files[] = $theRest.'~'.$expires;
		}
		return $files;
	}

	/**
	 * Sets a cache item to the given data, tags, and lifetime.
	 *
	 * @param   string   cache id to set
	 * @param   string   data in the cache
	 * @param   array    cache tags
	 * @param   integer  lifetime
	 * @return  bool
	 */
	public function set($id, $data, array $tags = NULL, $lifetime)
	{
		// Cache File driver expects unix timestamp: this is in seconds
		if ($lifetime !== 0)
		{
			$lifetime += time();
		}
		if ( ! empty($tags))
		{
			// Convert the tags into a string list
			$tags = implode('+', $tags);
		}
		$files = $this->all_exists($id, false);
		$newFile = $id.'~'.$tags.'~'.$lifetime;
		// create new file before deleting old ones.
		$retVal = file_put_contents($this->directory.$newFile, serialize($data));
		// because this can be referenced many times in parallel, eg by AJAX service calls,
		// we can not just delete all old files, as they may be being used by another call at the time.
		// the strategy then becomes do not delete anything less than 5 seconds old, and do not delete the youngest
		// file more than 5 seconds old. 5 seconds is chosen as it is reasonable to assume that whatever process
		// has asked for the list of files will be complete in that time.
		if (!empty($files)){
			// Disable all error reporting while deleting
			$ER = error_reporting(0);
			$age=-1;
			foreach ($files as $file)
			{
				$oldfile = basename($file);
				$oldlifetime = (int) substr($file, strrpos($file, '~')+1);
				if($lifetime == 0 && $oldlifetime !=0){
					if ( ! unlink($file))
						Kohana::log('error', 'Cache: Unable to delete cache file: '.$file);
				} else if ($lifetime-$oldlifetime > 5) {
					if($oldlifetime > $age) {
						if($age >= 0) {
							if ( ! unlink($agefile))
								Kohana::log('error', 'Cache: Unable to delete cache file: '.$agefile);
						}
						$age = $oldlifetime;
						$agefile = $file;
					} else {
						if ( ! unlink($file))
							 Kohana::log('error', 'Cache: Unable to delete cache file: '.$file);
					}
				}
			}
			// Turn on error reporting again
			error_reporting($ER);
		}	
		
		// Write out a serialized cache
		return (bool) $retVal;
	}

	/**
	 * Finds an array of ids for a given tag.
	 *
	 * @param  string  tag name
	 * @return array   of ids that match the tag
	 */
	public function find($tag)
	{
		// An array will always be returned
		$result = array();
		// use the most recent only.
		if ($paths = $this->exists($tag, TRUE))
		{
			// Find all the files with the given tag
			foreach ($paths as $path)
			{
				// Get the id from the filename
				list($id, $junk) = explode('~', basename($path), 2);

				if (($data = $this->get($id)) !== FALSE)
				{
					// Add the result to the array
					$result[$id] = $data;
				}
			}
		}

		return $result;
	}

	/**
	 * Fetches a cache item. This will delete the item if it is expired or if
	 * the hash does not match the stored hash.
	 *
	 * @param   string  cache id
	 * @return  mixed|NULL
	 */
	public function get($id)
	{
		if ($file = $this->exists($id))
		{
			// Use the first file
			$file = current($file);

			// Validate that the cache has not expired
			if ($this->expired($file))
			{
				// Remove this cache, it has expired
				$this->delete($id);
			}
			else
			{
				// Turn off errors while reading the file
				$ER = error_reporting(0);

				if (($data = file_get_contents($file)) !== FALSE)
				{
					// Unserialize the data
					$data = unserialize($data);
				}
				else
				{
					// Delete the data
					unset($data);
				}

				// Turn errors back on
				error_reporting($ER);
			}
		}

		// Return NULL if there is no data
		return isset($data) ? $data : NULL;
	}

	/**
	 * Deletes a cache item by id or tag
	 *
	 * @param   string   cache id or tag, or TRUE for "all items"
	 * @param   boolean  use tags
	 * @return  boolean
	 */
	public function delete($id, $tag = FALSE)
	{
		$files = $this->all_exists($id, $tag);

		if (empty($files))
			return FALSE;

		// Disable all error reporting while deleting
		$ER = error_reporting(0);

		foreach ($files as $file)
		{
			// Remove the cache files
			if ( ! unlink($file))
				Kohana::log('error', 'Cache: Unable to delete cache file: '.$file);
		}

		// Turn on error reporting again
		error_reporting($ER);

		return TRUE;
	}

	/**
	 * Deletes all cache files that are older than the current time.
	 *
	 * @return void
	 */
	public function delete_expired()
	{
		if ($files = $this->all_exists(TRUE))
		{
			// Disable all error reporting while deleting
			$ER = error_reporting(0);

			foreach ($files as $file)
			{
				if ($this->expired($file))
				{
					// The cache file has already expired, delete it
					if ( ! unlink($file))
						Kohana::log('error', 'Cache: Unable to delete cache file: '.$file);
				}
			}

			// Turn on error reporting again
			error_reporting($ER);
		}
	}

	/**
	 * Check if a cache file has expired by filename.
	 *
	 * @param  string  filename
	 * @return bool
	 */
	protected function expired($file)
	{
		// Get the expiration time
		$expires = (int) substr($file, strrpos($file, '~') + 1);

		// Expirations of 0 are "never expire"
		return ($expires !== 0 AND $expires <= time());
	}

} // End Cache File Driver