<?php

class Tests
{
	public static function whitelist()
	{
		$folders = Kohana::config('phpunit.whitelist_folders');

		foreach ($folders as $folder)
		{
			$files = Kohana::list_files($folder, TRUE);
			foreach ($files as $file)
			{
				if (is_file($file))
				{
					if ($file == __FILE__)
					{
						continue;
					}
					else
					{
						PHPUnit_Util_Filter::addFileToWhitelist($file);
					}
				}
			}
		}
	}

	public static function suite()
	{
		if ( ! class_exists('Kohana'))
		{
			throw new Exception('Please include the kohana bootstrap file.');
		}

		spl_autoload_unregister(array('Kohana', 'auto_load'));
		spl_autoload_register(array('Tests', 'auto_load'));

		$files = Kohana::list_files('tests');

		// Files to include in code coverage
		self::whitelist();

		$suite = new PHPUnit_Framework_TestSuite();

		$folders = Kohana::config('phpunit.filter_folders');

		foreach ($folders as $folder)
		{
			PHPUnit_Util_Filter::addDirectoryToFilter($folder);
		}

		self::addTests($suite, $files);

		return $suite;
	}

	public static function addTests($suite, $files)
	{
		foreach($files as $file)
		{
			if(is_array($file))
			{
				self::addTests($suite, $file);
			}
			else
			{
				if(is_file($file))
				{
					// The default PHPUnit TestCase extension
					if ( ! strpos($file, 'TestCase'.EXT))
					{
						$suite->addTestFile($file);
					}
					else
					{
						require_once($file);
					}
					PHPUnit_Util_Filter::addFileToFilter($file);
				}
			}
		}
	}

	/**
	 * Provides class auto-loading.
	 *
	 * @throws  Kohana_Exception
	 * @param   string  name of class
	 * @return  bool
	 */
	public static function auto_load($class)
	{
		if (class_exists($class, FALSE) OR interface_exists($class, FALSE))
			return TRUE;

		if (($suffix = strrpos($class, '_')) > 0)
		{
			// Find the class suffix
			$suffix = substr($class, $suffix + 1);
		}
		else
		{
			// No suffix
			$suffix = FALSE;
		}

		if ($suffix === 'Core')
		{
			$type = 'libraries';
			$file = substr($class, 0, -5);
		}
		elseif ($suffix === 'Controller')
		{
			$type = 'controllers';
			// Lowercase filename
			$file = strtolower(substr($class, 0, -11));
		}
		elseif ($suffix === 'Model')
		{
			$type = 'models';
			// Lowercase filename
			$file = strtolower(substr($class, 0, -6));
		}
		elseif ($suffix === 'Driver')
		{
			$type = 'libraries/drivers';
			$file = str_replace('_', '/', substr($class, 0, -7));
		}
		else
		{
			// This could be either a library or a helper, but libraries must
			// always be capitalized, so we check if the first character is
			// uppercase. If it is, we are loading a library, not a helper.
			$type = ($class[0] < 'a') ? 'libraries' : 'helpers';
			$file = $class;
		}

		if ($filename = Kohana::find_file($type, $file))
		{
			// Load the class
			require_once $filename;
		}
		else
		{
			// The class could not be found
			return FALSE;
		}

		if ($filename = Kohana::find_file($type, Kohana::config('core.extension_prefix').$class))
		{
			// Load the class extension
			require_once $filename;
		}
		elseif ($suffix !== 'Core' AND class_exists($class.'_Core', FALSE))
		{
			// Class extension to be evaluated
			$extension = 'class '.$class.' extends '.$class.'_Core { }';

			// Start class analysis
			$core = new ReflectionClass($class.'_Core');

			if ($core->isAbstract())
			{
				// Make the extension abstract
				$extension = 'abstract '.$extension;
			}

			// Transparent class extensions are handled using eval. This is
			// a disgusting hack, but it gets the job done.
			eval($extension);
		}

		return TRUE;
	}
}
